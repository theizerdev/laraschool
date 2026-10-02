<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DynamicDatabaseExport;
use App\Traits\HasDynamicLayout;
use ZipArchive;

class DatabaseExport extends Component
{
    use HasDynamicLayout;
    use WithFileUploads;

    // Pestaña activa principal: 'import' o 'export'
    public $activeTab = 'import';

    // ==========================================
    // FLUJO DE IMPORTACIÓN (4 PASOS)
    // ==========================================
    // Paso 1: Selección y Carga del Archivo (.sql, .sql.gz, .gz, .zip)
    // Paso 2: Lectura, Descompresión y Previsualización de Datos
    // Paso 3: Ingreso y Validación de Contraseña del Usuario
    // Paso 4: Barra de Progreso 0% a 100% y Ejecución de la Restauración
    public $importStep = 1;
    public $backupFile;
    public $importSessionId = '';

    public $fileInfo = [
        'name' => '',
        'sizeFormatted' => '',
        'sizeBytes' => 0,
        'extension' => '',
        'format' => '',
        'isCompressed' => false
    ];

    public $previewData = [
        'tables' => [],
        'totalStatements' => 0,
        'totalChunks' => 0,
        'uncompressedSizeFormatted' => '',
        'targetDatabase' => '',
        'existingTablesCount' => 0,
        'detectedTablesCount' => 0,
        'newTablesCount' => 0,
        'overwriteTablesCount' => 0
    ];

    // Paso 3: Contraseña de seguridad
    public $userPassword = '';
    public $passwordError = '';

    // Paso 4: Progreso y ejecución
    public $isImporting = false;
    public $importProgress = 0;
    public $importCurrentChunk = 0;
    public $importTotalChunks = 0;
    public $importStatusMessage = '';
    public $importCompleted = false;
    public $importError = '';
    public $importStats = [
        'queriesExecuted' => 0,
        'tablesRestored' => 0,
        'timeElapsed' => 0,
        'targetDatabase' => ''
    ];
    public $importStartTime = 0;

    // ==========================================
    // FLUJO DE EXPORTACIÓN
    // ==========================================
    public $exportScope = 'all'; // 'all' (Toda la BD) o 'single' (Tabla individual)
    public $selectedTable = '';
    public $selectedColumns = [];
    public $availableColumns = [];
    public $tableColumns = [];
    public $conditions = [];
    public $exportFormat = 'sql_gz'; // 'sql', 'sql_gz', 'zip', 'xlsx', 'csv', 'pdf'
    public $exportFileName = '';
    public $includeHeaders = true;
    public $availableTables = [];

    // Progreso de exportación (0% - 100%)
    public $isExporting = false;
    public $exportProgress = 0;
    public $exportStatusMessage = '';
    public $exportCompleted = false;
    public $exportError = '';
    public $exportDownloadFile = '';
    public $exportDownloadName = '';
    public $exportStats = [
        'tablesExported' => 0,
        'fileSize' => '',
        'fileName' => '',
        'format' => ''
    ];

    // Respaldos disponibles en el servidor (database/respaldos/)
    public $serverBackups = [];

    public function mount()
    {
        $this->loadAvailableTables();
        $this->loadServerBackups();
        $this->conditions = [
            ['column' => '', 'operator' => '=', 'value' => '', 'logic' => 'AND']
        ];
    }

    public function loadServerBackups()
    {
        $this->serverBackups = [];
        $path = base_path('database/respaldos');
        if (File::exists($path)) {
            $files = File::files($path);
            foreach ($files as $file) {
                $ext = strtolower($file->getExtension());
                $name = $file->getFilename();
                if (in_array($ext, ['sql', 'gz', 'zip']) || str_ends_with(strtolower($name), '.sql.gz')) {
                    $this->serverBackups[] = [
                        'name' => $name,
                        'path' => $file->getPathname(),
                        'size' => $this->formatBytes($file->getSize()),
                        'date' => date('d/m/Y h:i A', $file->getMTime()),
                    ];
                }
            }
            // Ordenar de más reciente a más antiguo
            usort($this->serverBackups, fn($a, $b) => filemtime($b['path']) <=> filemtime($a['path']));
        }
    }

    public function selectServerBackup(string $fileName)
    {
        $filePath = base_path('database/respaldos/' . $fileName);
        if (!File::exists($filePath)) {
            $this->addError('backupFile', 'El archivo no existe en el servidor: ' . $fileName);
            return;
        }

        $this->processFileAndPreview($filePath, $fileName);
    }

    public function render()
    {
        return view('livewire.admin.database-export-materialize')
            ->layout($this->getLayout(), [
                'title' => 'Gestión de Base de Datos - Exportar e Importar',
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
                    ['name' => 'Base de Datos', 'active' => true]
                ]
            ]);
    }

    public function switchTab($tab)
    {
        if (in_array($tab, ['import', 'export'])) {
            $this->activeTab = $tab;
        }
    }

    // =========================================================================
    // IMPORTACIÓN: PASO 1 -> PASO 2 (Carga, Descompresión y Previsualización)
    // =========================================================================

    public function processUploadAndPreview()
    {
        $this->validate([
            'backupFile' => 'required|file|max:65536', // 64 MB
        ], [
            'backupFile.required' => 'Debe seleccionar un archivo de respaldo (.sql, .sql.gz, .gz o .zip).',
            'backupFile.file' => 'El archivo proporcionado no es válido.',
            'backupFile.max' => 'El archivo supera el límite máximo permitido de 64MB.',
        ]);

        $this->processFileAndPreview(
            $this->backupFile->getRealPath(),
            $this->backupFile->getClientOriginalName()
        );
    }

    public function processFileAndPreview(string $rawUploadedPath, string $originalName)
    {
        $originalLower = strtolower($originalName);
        $sizeBytes = filesize($rawUploadedPath);

        // Determinar formato y compresión
        $format = '';
        $isCompressed = false;
        if (str_ends_with($originalLower, '.sql.gz')) {
            $format = 'sql.gz';
            $isCompressed = true;
        } elseif (str_ends_with($originalLower, '.gz')) {
            $format = 'gz';
            $isCompressed = true;
        } elseif (str_ends_with($originalLower, '.zip')) {
            $format = 'zip';
            $isCompressed = true;
        } elseif (str_ends_with($originalLower, '.sql')) {
            $format = 'sql';
            $isCompressed = false;
        } else {
            $this->addError('backupFile', 'Formato no soportado. Debe ser un archivo .sql, .sql.gz, .gz o .zip.');
            return;
        }

        // Crear directorio temporal único para esta sesión de importación
        $this->importSessionId = 'imp_' . uniqid() . '_' . time();
        $tempDir = storage_path('app/temp_db/' . $this->importSessionId);
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0775, true, true);
        }

        $extractedSqlPath = $tempDir . '/dump.sql';

        try {
            // Descomprimir o mover archivo a $extractedSqlPath
            if ($format === 'zip') {
                $zip = new ZipArchive();
                $res = $zip->open($rawUploadedPath);
                if ($res !== true) {
                    throw new \Exception('No se pudo abrir el archivo ZIP comprimido (Código: ' . $res . ').');
                }

                // Buscar el primer archivo con extensión .sql dentro del zip
                $sqlEntryName = null;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if (str_ends_with(strtolower($entry), '.sql')) {
                        $sqlEntryName = $entry;
                        break;
                    }
                }

                if (!$sqlEntryName) {
                    // Si no tiene extensión .sql, tomar el primer archivo que no sea directorio
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entry = $zip->getNameIndex($i);
                        if (!str_ends_with($entry, '/')) {
                            $sqlEntryName = $entry;
                            break;
                        }
                    }
                }

                if (!$sqlEntryName) {
                    $zip->close();
                    throw new \Exception('El archivo ZIP no contiene un archivo SQL válido.');
                }

                $zip->extractTo($tempDir, $sqlEntryName);
                $zip->close();

                $extractedCandidate = $tempDir . '/' . $sqlEntryName;
                if ($extractedCandidate !== $extractedSqlPath) {
                    File::move($extractedCandidate, $extractedSqlPath);
                }

            } elseif ($format === 'gz' || $format === 'sql.gz') {
                $gz = gzopen($rawUploadedPath, 'rb');
                if (!$gz) {
                    throw new \Exception('No se pudo descomprimir el archivo GZip.');
                }

                $dest = fopen($extractedSqlPath, 'wb');
                if (!$dest) {
                    gzclose($gz);
                    throw new \Exception('No se pudo crear el archivo temporal de destino.');
                }

                while (!gzeof($gz)) {
                    fwrite($dest, gzread($gz, 524288)); // 512KB chunks
                }
                gzclose($gz);
                fclose($dest);

            } else {
                // Formato .sql plano
                File::copy($rawUploadedPath, $extractedSqlPath);
            }

            if (!File::exists($extractedSqlPath) || filesize($extractedSqlPath) === 0) {
                throw new \Exception('El archivo SQL descomprimido está vacío o no se pudo generar.');
            }

            $uncompressedSizeBytes = filesize($extractedSqlPath);

            // Analizar el contenido del archivo SQL y dividirlo en lotes (chunks)
            $analysisResult = $this->parseAndChunkSqlFile($extractedSqlPath, $tempDir);

            // Obtener tablas actuales en la base de datos destino
            $targetDb = DB::getDatabaseName();
            $existingDbTables = [];
            $dbTablesRaw = DB::select('SHOW TABLES');
            $key = 'Tables_in_' . $targetDb;
            foreach ($dbTablesRaw as $row) {
                $existingDbTables[] = $row->$key;
            }

            // Cruzar tablas detectadas con las existentes
            $detectedTables = [];
            $overwriteCount = 0;
            $newCount = 0;

            foreach ($analysisResult['tables'] as $tblName => $tblMeta) {
                $exists = in_array($tblName, $existingDbTables);
                if ($exists) {
                    $overwriteCount++;
                } else {
                    $newCount++;
                }
                $detectedTables[] = [
                    'name' => $tblName,
                    'exists' => $exists,
                    'creates' => $tblMeta['creates'],
                    'inserts' => $tblMeta['inserts'],
                ];
            }

            // Ordenar tablas alfabéticamente
            usort($detectedTables, fn($a, $b) => strcmp($a['name'], $b['name']));

            // Guardar metadata en el estado del componente
            $this->fileInfo = [
                'name' => $originalName,
                'sizeFormatted' => $this->formatBytes($sizeBytes),
                'sizeBytes' => $sizeBytes,
                'extension' => pathinfo($originalName, PATHINFO_EXTENSION),
                'format' => strtoupper($format),
                'isCompressed' => $isCompressed,
            ];

            $this->previewData = [
                'tables' => $detectedTables,
                'totalStatements' => $analysisResult['statementCount'],
                'totalChunks' => $analysisResult['chunkCount'],
                'uncompressedSizeFormatted' => $this->formatBytes($uncompressedSizeBytes),
                'targetDatabase' => $targetDb,
                'existingTablesCount' => count($existingDbTables),
                'detectedTablesCount' => count($detectedTables),
                'newTablesCount' => $newCount,
                'overwriteTablesCount' => $overwriteCount,
            ];

            // Avanzar al Paso 2: Lectura y Previsualización
            $this->importStep = 2;

        } catch (\Throwable $e) {
            Log::error('Error al procesar archivo de respaldo: ' . $e->getMessage());
            $this->addError('backupFile', 'Error al procesar el archivo: ' . $e->getMessage());
            $this->cleanupImportFiles();
        }
    }

    /**
     * Parsea el archivo SQL, extrae tablas y sentencias, y crea archivos de chunks.
     */
    private function parseAndChunkSqlFile(string $sqlFilePath, string $tempDir): array
    {
        $handle = fopen($sqlFilePath, 'r');
        if (!$handle) {
            throw new \Exception('No se pudo abrir el archivo SQL para lectura.');
        }

        $tables = [];
        $statementCount = 0;
        $chunkIndex = 0;
        $chunkStatements = [];
        $chunkByteSize = 0;
        $maxStatementsPerChunk = 60; // Sentencias por lote
        $maxBytesPerChunk = 400000;  // ~400 KB por lote

        $buffer = '';
        $inString = false;
        $stringChar = null;
        $isEscaped = false;

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);

            // Detección de tablas cuando no estamos dentro de un string SQL
            if (!$inString) {
                if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                    continue;
                }
                if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $line, $matches)) {
                    $tableName = $matches[1];
                    if (!isset($tables[$tableName])) {
                        $tables[$tableName] = ['creates' => 0, 'inserts' => 0];
                    }
                    $tables[$tableName]['creates']++;
                }
                if (preg_match('/INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?/i', $line, $matches)) {
                    $tableName = $matches[1];
                    if (!isset($tables[$tableName])) {
                        $tables[$tableName] = ['creates' => 0, 'inserts' => 0];
                    }
                    $tables[$tableName]['inserts']++;
                }
            }

            // Analizador de caracteres respetando comillas y caracteres de escape
            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $char = $line[$i];

                if ($isEscaped) {
                    $isEscaped = false;
                    $buffer .= $char;
                    continue;
                }

                if ($char === '\\') {
                    $isEscaped = true;
                    $buffer .= $char;
                    continue;
                }

                if ($inString) {
                    if ($char === $stringChar) {
                        $inString = false;
                        $stringChar = null;
                    }
                    $buffer .= $char;
                    continue;
                }

                if ($char === "'" || $char === '"') {
                    $inString = true;
                    $stringChar = $char;
                    $buffer .= $char;
                    continue;
                }

                if ($char === ';') {
                    $cleanStmt = trim($buffer);
                    if (!empty($cleanStmt)) {
                        $chunkStatements[] = $cleanStmt;
                        $statementCount++;
                        $chunkByteSize += strlen($cleanStmt);

                        // Si el chunk supera el límite de sentencias o tamaño, escribir a disco
                        if (count($chunkStatements) >= $maxStatementsPerChunk || $chunkByteSize >= $maxBytesPerChunk) {
                            $chunkFile = $tempDir . '/chunk_' . $chunkIndex . '.sql';
                            file_put_contents($chunkFile, implode(";\n", $chunkStatements) . ";\n");
                            $chunkIndex++;
                            $chunkStatements = [];
                            $chunkByteSize = 0;
                        }
                    }
                    $buffer = '';
                    continue;
                }

                $buffer .= $char;
            }
        }

        // Sentencia residual si existiera
        $cleanStmt = trim($buffer);
        if (!empty($cleanStmt)) {
            $chunkStatements[] = $cleanStmt;
            $statementCount++;
        }

        // Escribir último chunk pendiente
        if (!empty($chunkStatements)) {
            $chunkFile = $tempDir . '/chunk_' . $chunkIndex . '.sql';
            file_put_contents($chunkFile, implode(";\n", $chunkStatements) . ";\n");
            $chunkIndex++;
        }

        fclose($handle);

        return [
            'tables' => $tables,
            'statementCount' => $statementCount,
            'chunkCount' => max(1, $chunkIndex)
        ];
    }

    // =========================================================================
    // IMPORTACIÓN: PASO 2 -> PASO 3 (Confirmación y Contraseña)
    // =========================================================================

    public function backToStep1()
    {
        $this->cleanupImportFiles();
        $this->importStep = 1;
        $this->backupFile = null;
        $this->userPassword = '';
        $this->passwordError = '';
    }

    public function goToStep3()
    {
        $this->userPassword = '';
        $this->passwordError = '';
        $this->importStep = 3;
    }

    public function backToStep2()
    {
        $this->userPassword = '';
        $this->passwordError = '';
        $this->importStep = 2;
    }

    // =========================================================================
    // IMPORTACIÓN: PASO 3 -> PASO 4 (Validación Contraseña e Inicio Progreso)
    // =========================================================================

    public function verifyPasswordAndProceed()
    {
        $this->passwordError = '';

        if (empty($this->userPassword)) {
            $this->passwordError = 'Por favor ingrese su contraseña para autorizar la restauración.';
            return;
        }

        if (!auth()->check() || !Hash::check($this->userPassword, auth()->user()->password)) {
            $this->passwordError = 'La contraseña ingresada es incorrecta. Verifique e intente nuevamente.';
            return;
        }

        // Limpiar la contraseña de la memoria inmediatamente por seguridad
        $this->userPassword = '';

        // Inicializar el estado de ejecución del Paso 4
        $this->importStep = 4;
        $this->isImporting = true;
        $this->importProgress = 0;
        $this->importCurrentChunk = 0;
        $this->importTotalChunks = $this->previewData['totalChunks'];
        $this->importCompleted = false;
        $this->importError = '';
        $this->importStartTime = microtime(true);
        $this->importStatusMessage = 'Iniciando proceso de restauración y deshabilitando restricciones de claves foráneas...';
    }

    // =========================================================================
    // IMPORTACIÓN: PASO 4 (Ejecución del lote con Barra de Progreso 0% a 100%)
    // =========================================================================

    public function executeImportBatch()
    {
        if (!$this->isImporting || empty($this->importSessionId)) {
            return ['finished' => true];
        }

        $tempDir = storage_path('app/temp_db/' . $this->importSessionId);
        $chunkFile = $tempDir . '/chunk_' . $this->importCurrentChunk . '.sql';

        if (!File::exists($chunkFile)) {
            $this->finalizeImport();
            return ['finished' => true];
        }

        try {
            // Deshabilitar comprobaciones de claves foráneas y modo estricto en CADA lote (cada petición HTTP tiene su propia conexión PDO)
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            DB::statement('SET UNIQUE_CHECKS = 0');
            DB::statement("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

            $sqlContent = File::get($chunkFile);
            if (!empty(trim($sqlContent))) {
                $batchSql = "SET FOREIGN_KEY_CHECKS = 0;\nSET UNIQUE_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n" . $sqlContent;
                DB::unprepared($batchSql);
            }

            $this->importCurrentChunk++;
            $total = max(1, $this->importTotalChunks);

            if ($this->importCurrentChunk >= $total) {
                $this->finalizeImport();
                return ['finished' => true, 'progress' => 100];
            }

            // Calcular porcentaje del 1% al 98%
            $this->importProgress = min(98, max(2, (int) round(($this->importCurrentChunk / $total) * 100)));
            $this->importStatusMessage = "Restaurando lote {$this->importCurrentChunk} de {$total} (" . $this->importProgress . "% completado)...";

            return ['finished' => false, 'progress' => $this->importProgress];

        } catch (\Throwable $e) {
            Log::error("Error en restauración de BD (Lote {$this->importCurrentChunk}): " . $e->getMessage());

            // Siempre rehabilitar foreign keys en caso de error
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
                DB::statement('SET UNIQUE_CHECKS = 1');
            } catch (\Throwable $re) {}

            $this->importError = 'Error al ejecutar el lote ' . ($this->importCurrentChunk + 1) . ': ' . $e->getMessage();
            $this->isImporting = false;
            return ['error' => true, 'message' => $this->importError];
        }
    }

    private function finalizeImport()
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            DB::statement('SET UNIQUE_CHECKS = 1');
        } catch (\Throwable $e) {}

        $elapsed = round(microtime(true) - ($this->importStartTime ?: microtime(true)), 2);

        $this->importProgress = 100;
        $this->isImporting = false;
        $this->importCompleted = true;
        $this->importStatusMessage = '¡Base de datos importada y restaurada exitosamente!';

        $this->importStats = [
            'queriesExecuted' => $this->previewData['totalStatements'],
            'tablesRestored' => $this->previewData['detectedTablesCount'],
            'timeElapsed' => $elapsed,
            'targetDatabase' => $this->previewData['targetDatabase']
        ];

        // Limpiar archivos temporales de chunks
        $this->cleanupImportFiles();
    }

    public function resetImport()
    {
        $this->cleanupImportFiles();
        $this->importStep = 1;
        $this->backupFile = null;
        $this->importSessionId = '';
        $this->fileInfo = [
            'name' => '',
            'sizeFormatted' => '',
            'sizeBytes' => 0,
            'extension' => '',
            'format' => '',
            'isCompressed' => false
        ];
        $this->previewData = [
            'tables' => [],
            'totalStatements' => 0,
            'totalChunks' => 0,
            'uncompressedSizeFormatted' => '',
            'targetDatabase' => '',
            'existingTablesCount' => 0,
            'detectedTablesCount' => 0,
            'newTablesCount' => 0,
            'overwriteTablesCount' => 0
        ];
        $this->userPassword = '';
        $this->passwordError = '';
        $this->isImporting = false;
        $this->importProgress = 0;
        $this->importCurrentChunk = 0;
        $this->importTotalChunks = 0;
        $this->importStatusMessage = '';
        $this->importCompleted = false;
        $this->importError = '';
    }

    private function cleanupImportFiles()
    {
        if (!empty($this->importSessionId)) {
            $dir = storage_path('app/temp_db/' . $this->importSessionId);
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }

    // =========================================================================
    // EXPORTACIÓN DE BASE DE DATOS
    // =========================================================================

    public function loadAvailableTables()
    {
        $this->availableTables = [];
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $key = 'Tables_in_' . $databaseName;

        $excludedTables = [
            'migrations', 'password_resets', 'password_reset_tokens',
            'personal_access_tokens', 'cache', 'cache_locks', 'jobs',
            'job_batches', 'failed_jobs', 'sessions', 'activity_log'
        ];

        foreach ($tables as $table) {
            $tableName = $table->$key;
            if (!in_array($tableName, $excludedTables)) {
                $this->availableTables[$tableName] = $this->formatTableName($tableName);
            }
        }

        asort($this->availableTables);
    }

    public function updatedSelectedTable($tableName)
    {
        if (empty($tableName)) {
            $this->reset(['availableColumns', 'tableColumns', 'selectedColumns']);
            return;
        }

        $this->loadTableColumns($tableName);
        $this->selectedColumns = array_keys($this->availableColumns);
        $this->generateDefaultFileName();
    }

    public function updatedExportScope($scope)
    {
        if ($scope === 'all') {
            $this->selectedTable = '';
            $this->selectedColumns = [];
        }
        $this->generateDefaultFileName();
    }

    public function updatedExportFormat($format)
    {
        $this->generateDefaultFileName();
    }

    public function loadTableColumns($tableName)
    {
        $this->availableColumns = [];
        $this->tableColumns = [];

        try {
            $columns = Schema::getColumnListing($tableName);
            foreach ($columns as $column) {
                $this->availableColumns[$column] = [
                    'name' => $this->formatColumnName($column),
                    'original' => $column
                ];
                $this->tableColumns[$column] = $this->formatColumnName($column);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error al cargar las columnas: ' . $e->getMessage());
        }
    }

    public function selectAllColumns()
    {
        $this->selectedColumns = array_keys($this->availableColumns);
    }

    public function deselectAllColumns()
    {
        $this->selectedColumns = [];
    }

    public function addCondition()
    {
        $this->conditions[] = ['column' => '', 'operator' => '=', 'value' => '', 'logic' => 'AND'];
    }

    public function removeCondition($index)
    {
        unset($this->conditions[$index]);
        $this->conditions = array_values($this->conditions);
        if (!empty($this->conditions)) {
            $this->conditions[0]['logic'] = 'AND';
        }
    }

    public function startExport()
    {
        $this->isExporting = true;
        $this->exportProgress = 10;
        $this->exportStatusMessage = 'Iniciando preparación del respaldo...';
        $this->exportCompleted = false;
        $this->exportError = '';
        $this->exportDownloadFile = '';
        $this->exportDownloadName = '';

        // Formatos tabulares especiales (Excel, CSV, PDF) solo aplican a tabla individual
        if ($this->exportScope === 'single' && in_array($this->exportFormat, ['xlsx', 'csv', 'pdf', 'html'])) {
            if (empty($this->selectedTable) || empty($this->selectedColumns)) {
                $this->exportError = 'Seleccione una tabla y al menos una columna para exportar en este formato.';
                $this->isExporting = false;
                return;
            }

            try {
                $this->exportProgress = 50;
                $exportData = [
                    'table' => $this->selectedTable,
                    'columns' => $this->selectedColumns,
                    'conditions' => array_filter($this->conditions, fn($c) => !empty($c['column']) && !empty($c['value'])),
                    'empresa_id' => auth()->user()->empresa_id ?? null,
                    'sucursal_id' => auth()->user()->sucursal_id ?? null
                ];

                $fileName = ($this->exportFileName ?: $this->generateDefaultFileName()) . '.' . $this->exportFormat;
                $this->exportProgress = 100;
                $this->isExporting = false;
                $this->exportCompleted = true;

                return Excel::download(new DynamicDatabaseExport($exportData), $fileName);
            } catch (\Exception $e) {
                $this->exportError = 'Error al exportar: ' . $e->getMessage();
                $this->isExporting = false;
                return;
            }
        }

        // Exportación de SQL (Plano, Comprimido GZip o ZIP)
        try {
            $tempDir = storage_path('app/temp_db/exports');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0775, true, true);
            }

            $dateSuffix = now()->format('Ymd_His');
            $baseName = $this->exportFileName ?: ($this->exportScope === 'all'
                ? 'backup_db_' . DB::getDatabaseName() . '_' . $dateSuffix
                : 'backup_tabla_' . str_replace('_', '-', $this->selectedTable) . '_' . $dateSuffix);

            $rawSqlFile = $tempDir . '/' . $baseName . '.sql';

            $this->exportProgress = 25;
            $this->exportStatusMessage = 'Generando estructura y datos SQL...';

            // Generar el archivo SQL
            $this->generateSqlExportFile($rawSqlFile);

            $this->exportProgress = 70;
            $finalFile = $rawSqlFile;
            $finalDownloadName = $baseName . '.sql';
            $finalFormatLabel = 'SQL (.sql)';

            // Compresión
            if ($this->exportFormat === 'sql_gz') {
                $this->exportStatusMessage = 'Comprimiendo respaldo con algoritmo GZip...';
                $this->exportProgress = 85;

                $gzFile = $tempDir . '/' . $baseName . '.sql.gz';
                $this->compressToGzip($rawSqlFile, $gzFile);
                @unlink($rawSqlFile);

                $finalFile = $gzFile;
                $finalDownloadName = $baseName . '.sql.gz';
                $finalFormatLabel = 'GZip (.sql.gz)';

            } elseif ($this->exportFormat === 'zip') {
                $this->exportStatusMessage = 'Comprimiendo respaldo en archivo ZIP...';
                $this->exportProgress = 85;

                $zipFile = $tempDir . '/' . $baseName . '.zip';
                $zip = new ZipArchive();
                if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                    $zip->addFile($rawSqlFile, basename($rawSqlFile));
                    $zip->close();
                    @unlink($rawSqlFile);
                } else {
                    throw new \Exception('No se pudo generar el archivo ZIP comprimido.');
                }

                $finalFile = $zipFile;
                $finalDownloadName = $baseName . '.zip';
                $finalFormatLabel = 'ZIP (.zip)';
            }

            $this->exportProgress = 100;
            $this->exportStatusMessage = '¡Respaldo generado con éxito!';
            $this->exportCompleted = true;
            $this->isExporting = false;

            $this->exportDownloadFile = $finalFile;
            $this->exportDownloadName = $finalDownloadName;

            $this->exportStats = [
                'tablesExported' => $this->exportScope === 'all' ? count($this->availableTables) : 1,
                'fileSize' => $this->formatBytes(filesize($finalFile)),
                'fileName' => $finalDownloadName,
                'format' => $finalFormatLabel
            ];

            // Retornar descarga inmediata
            return response()->download($finalFile, $finalDownloadName);

        } catch (\Throwable $e) {
            Log::error('Error en exportación de BD: ' . $e->getMessage());
            $this->exportError = 'Error durante la exportación: ' . $e->getMessage();
            $this->isExporting = false;
            $this->exportProgress = 0;
        }
    }

    public function downloadGeneratedExport()
    {
        if (!empty($this->exportDownloadFile) && File::exists($this->exportDownloadFile)) {
            return response()->download($this->exportDownloadFile, $this->exportDownloadName);
        }
        $this->exportError = 'El archivo generado ya no se encuentra en el servidor. Por favor genere uno nuevo.';
    }

    private function generateSqlExportFile(string $destFilePath)
    {
        $handle = fopen($destFilePath, 'w');
        if (!$handle) {
            throw new \Exception('No se pudo crear el archivo temporal de exportación.');
        }

        // Encabezado
        fwrite($handle, "-- ============================================================\n");
        fwrite($handle, "-- RESPALDO DE BASE DE DATOS\n");
        fwrite($handle, "-- Sistema: U.E. José María Vargas / SGA\n");
        fwrite($handle, "-- Fecha: " . now()->format('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Base de Datos: " . DB::getDatabaseName() . "\n");
        fwrite($handle, "-- Usuario Generador: " . (auth()->check() ? auth()->user()->name : 'Sistema') . "\n");
        fwrite($handle, "-- ============================================================\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($handle, "SET UNIQUE_CHECKS = 0;\n");
        fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tablesToDump = [];
        if ($this->exportScope === 'single' && !empty($this->selectedTable)) {
            $tablesToDump[] = $this->selectedTable;
        } else {
            $tablesToDump = array_keys($this->availableTables);
        }

        foreach ($tablesToDump as $tbl) {
            fwrite($handle, "--\n-- Estructura y datos de tabla: `{$tbl}`\n--\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$tbl}`;\n");

            // CREATE TABLE
            try {
                $createObj = DB::selectOne("SHOW CREATE TABLE `{$tbl}`");
                $createSql = $createObj->{'Create Table'};
                fwrite($handle, $createSql . ";\n\n");
            } catch (\Throwable $e) {
                fwrite($handle, "-- Error al obtener estructura de {$tbl}: " . $e->getMessage() . "\n\n");
                continue;
            }

            // INSERT DATA
            try {
                $query = DB::table($tbl);

                // Si es tabla individual con filtros aplicados
                if ($this->exportScope === 'single' && !empty($this->conditions)) {
                    $validConds = array_filter($this->conditions, fn($c) => !empty($c['column']) && !empty($c['value']));
                    foreach ($validConds as $cond) {
                        if ($cond['operator'] === 'LIKE') {
                            $query->where($cond['column'], 'LIKE', '%' . $cond['value'] . '%');
                        } elseif (in_array($cond['operator'], ['IS NULL', 'IS NOT NULL'])) {
                            if ($cond['operator'] === 'IS NULL') {
                                $query->whereNull($cond['column']);
                            } else {
                                $query->whereNotNull($cond['column']);
                            }
                        } else {
                            $query->where($cond['column'], $cond['operator'], $cond['value']);
                        }
                    }
                }

                $query->chunk(500, function($records) use ($handle, $tbl) {
                    if ($records->isEmpty()) return;

                    foreach ($records as $record) {
                        $data = (array) $record;
                        $columns = array_keys($data);
                        $escapedValues = array_map(function($val) {
                            if ($val === null) return 'NULL';
                            if (is_numeric($val) && !str_starts_with((string)$val, '0')) return $val;
                            return "'" . addslashes((string)$val) . "'";
                        }, array_values($data));

                        $sql = "INSERT INTO `{$tbl}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
                        fwrite($handle, $sql);
                    }
                });

                fwrite($handle, "\n");

            } catch (\Throwable $e) {
                fwrite($handle, "-- Error al exportar datos de {$tbl}: " . $e->getMessage() . "\n\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fwrite($handle, "SET UNIQUE_CHECKS = 1;\n");
        fclose($handle);
    }

    private function compressToGzip(string $sourcePath, string $destPath)
    {
        $src = fopen($sourcePath, 'rb');
        $gz = gzopen($destPath, 'wb9'); // Máxima compresión
        if (!$src || !$gz) {
            throw new \Exception('No se pudo inicializar la compresión GZip.');
        }

        while (!feof($src)) {
            gzwrite($gz, fread($src, 524288));
        }

        fclose($src);
        gzclose($gz);
    }

    public function generateDefaultFileName()
    {
        $date = now()->format('Y-m-d-His');
        if ($this->exportScope === 'all') {
            $databaseName = DB::getDatabaseName();
            $this->exportFileName = 'database-' . str_replace('_', '-', $databaseName) . '-' . $date;
        } else {
            $tableName = str_replace('_', '-', $this->selectedTable ?: 'tabla');
            $this->exportFileName = 'export-' . $tableName . '-' . $date;
        }

        return $this->exportFileName;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        return round($bytes / pow(1024, $power), $precision) . ' ' . $units[$power];
    }

    private function formatTableName($tableName)
    {
        return ucwords(str_replace('_', ' ', $tableName));
    }

    private function formatColumnName($columnName)
    {
        return ucwords(str_replace('_', ' ', $columnName));
    }

    public function getAvailableOperators()
    {
        return [
            '=' => 'Igual (=)',
            '!=' => 'Diferente (!=)',
            '>' => 'Mayor que (>)',
            '<' => 'Menor que (<)',
            '>=' => 'Mayor o igual (>=)',
            '<=' => 'Menor o igual (<=)',
            'LIKE' => 'Contiene (LIKE)',
            'NOT LIKE' => 'No contiene (NOT LIKE)',
            'IS NULL' => 'Es nulo (IS NULL)',
            'IS NOT NULL' => 'No es nulo (IS NOT NULL)'
        ];
    }
}
