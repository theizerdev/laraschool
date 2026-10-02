<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Encabezado de Página -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="ri ri-database-2-line text-primary fs-3"></i>
                <span>Gestión de Base de Datos</span>
            </h4>
            <p class="text-muted mb-0">Módulo institucional para la exportación y restauración segura de la base de datos.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-info d-flex align-items-center gap-1 px-3 py-2 fs-6">
                <i class="ri ri-server-line"></i>
                <span>BD: <strong>{{ DB::getDatabaseName() }}</strong></span>
            </span>
            <span class="badge bg-label-success d-flex align-items-center gap-1 px-3 py-2 fs-6">
                <i class="ri ri-shield-check-line"></i>
                <span>MySQL / MariaDB</span>
            </span>
        </div>
    </div>

    <!-- Pestañas de Navegación Principal: Importar / Exportar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill gap-2" role="tablist">
                <li class="nav-item" role="presentation">
                    <button 
                        type="button" 
                        wire:click="switchTab('import')" 
                        class="nav-link py-3 fw-semibold d-flex align-items-center justify-content-center gap-2 {{ $activeTab === 'import' ? 'active' : '' }}"
                    >
                        <i class="ri ri-upload-cloud-2-line fs-5"></i>
                        <span>Importar Base de Datos</span>
                        <span class="badge {{ $activeTab === 'import' ? 'bg-white text-primary' : 'bg-label-primary' }} rounded-pill ms-1">
                            Asistente 4 Pasos
                        </span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button 
                        type="button" 
                        wire:click="switchTab('export')" 
                        class="nav-link py-3 fw-semibold d-flex align-items-center justify-content-center gap-2 {{ $activeTab === 'export' ? 'active' : '' }}"
                    >
                        <i class="ri ri-download-cloud-2-line fs-5"></i>
                        <span>Exportar Base de Datos</span>
                        <span class="badge {{ $activeTab === 'export' ? 'bg-white text-primary' : 'bg-label-secondary' }} rounded-pill ms-1">
                            SQL / Comprimido
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- PESTAÑA 1: IMPORTAR BASE DE DATOS (ASISTENTE DE 4 PASOS)           -->
    <!-- =================================================================== -->
    @if($activeTab === 'import')
        <!-- Stepper Visual de 4 Pasos -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="row g-2 align-items-center text-center">
                    <!-- Paso 1 -->
                    <div class="col-12 col-md-3">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 p-2 rounded {{ $importStep === 1 ? 'bg-primary text-white shadow-sm' : ($importStep > 1 ? 'bg-label-success text-success' : 'text-muted') }}">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; border: 2px solid currentColor;">
                                @if($importStep > 1)
                                    <i class="ri ri-check-line"></i>
                                @else
                                    1
                                @endif
                            </div>
                            <div class="text-start">
                                <div class="fw-bold fs-7 lh-1">Paso 1</div>
                                <small class="fs-8">Importación (.sql / comp.)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2 -->
                    <div class="col-12 col-md-3">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 p-2 rounded {{ $importStep === 2 ? 'bg-primary text-white shadow-sm' : ($importStep > 2 ? 'bg-label-success text-success' : 'text-muted') }}">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; border: 2px solid currentColor;">
                                @if($importStep > 2)
                                    <i class="ri ri-check-line"></i>
                                @else
                                    2
                                @endif
                            </div>
                            <div class="text-start">
                                <div class="fw-bold fs-7 lh-1">Paso 2</div>
                                <small class="fs-8">Lectura y Previsualización</small>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3 -->
                    <div class="col-12 col-md-3">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 p-2 rounded {{ $importStep === 3 ? 'bg-primary text-white shadow-sm' : ($importStep > 3 ? 'bg-label-success text-success' : 'text-muted') }}">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; border: 2px solid currentColor;">
                                @if($importStep > 3)
                                    <i class="ri ri-check-line"></i>
                                @else
                                    3
                                @endif
                            </div>
                            <div class="text-start">
                                <div class="fw-bold fs-7 lh-1">Paso 3</div>
                                <small class="fs-8">Contraseña de Usuario</small>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 4 -->
                    <div class="col-12 col-md-3">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 p-2 rounded {{ $importStep === 4 ? 'bg-primary text-white shadow-sm' : 'text-muted' }}">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; border: 2px solid currentColor;">
                                @if($importCompleted)
                                    <i class="ri ri-check-line"></i>
                                @else
                                    4
                                @endif
                            </div>
                            <div class="text-start">
                                <div class="fw-bold fs-7 lh-1">Paso 4</div>
                                <small class="fs-8">Restauración (0% - 100%)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- PASO 1: SELECCIÓN Y CARGA DEL ARCHIVO      -->
        <!-- ========================================== -->
        @if($importStep === 1)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle p-2"><i class="ri ri-upload-2-line fs-5"></i></span>
                        <div>
                            <h5 class="mb-0 fw-bold">Paso 1: Importación de Archivo de Base de Datos</h5>
                            <small class="text-muted">Suba el archivo de respaldo en formato .sql plano o comprimido (.sql.gz, .gz, .zip)</small>
                        </div>
                    </div>
                </div>
                <div class="card-body py-4">
                    <!-- Zona de Subida / File Upload -->
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="border border-2 border-dashed rounded-3 p-4 p-md-5 text-center bg-light position-relative mb-4 hover-shadow transition-all">
                                <input 
                                    type="file" 
                                    wire:model="backupFile" 
                                    id="backupFileInput" 
                                    accept=".sql,.gz,.zip" 
                                    class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer"
                                    style="z-index: 10;"
                                >
                                <div class="mb-3">
                                    <div class="avatar avatar-xl mx-auto bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <i class="ri ri-upload-cloud-line fs-1"></i>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-1">Arrastre su archivo de respaldo aquí o haga clic para seleccionar</h5>
                                <p class="text-muted small mb-3">Formatos aceptados: <strong>.SQL</strong>, <strong>.SQL.GZ</strong>, <strong>.GZ</strong> o <strong>.ZIP</strong> (Hasta 64 MB)</p>

                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <span class="badge bg-label-primary px-3 py-2"><i class="ri ri-file-code-line me-1"></i> .sql plano</span>
                                    <span class="badge bg-label-success px-3 py-2"><i class="ri ri-file-zip-line me-1"></i> .sql.gz / .gz (Recomendado)</span>
                                    <span class="badge bg-label-warning px-3 py-2"><i class="ri ri-folder-zip-line me-1"></i> .zip comprimido</span>
                                </div>

                                <!-- Loader mientras se sube el archivo al servidor -->
                                <div wire:loading wire:target="backupFile" class="mt-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando archivo...</span>
                                    </div>
                                    <p class="text-primary fw-semibold mt-2 mb-0">Cargando archivo al servidor... Por favor espere.</p>
                                </div>
                            </div>

                            <!-- Información del archivo seleccionado -->
                            @if($backupFile && !$errors->has('backupFile'))
                                <div class="card border border-primary bg-primary bg-opacity-10 mb-4">
                                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="ri ri-file-check-line text-primary fs-2"></i>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-primary">{{ $backupFile->getClientOriginalName() }}</h6>
                                                <small class="text-muted">Tamaño: {{ number_format($backupFile->getSize() / 1024, 2) }} KB</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-success">Listo para procesar</span>
                                    </div>
                                </div>
                            @endif

                            @error('backupFile')
                                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                                    <i class="ri ri-error-warning-line fs-5"></i>
                                    <div>{{ $message }}</div>
                                </div>
                            @enderror

                            <!-- Botón de avance para archivo subido -->
                            <div class="d-flex justify-content-end gap-2 mb-4">
                                <button 
                                    type="button" 
                                    wire:click="processUploadAndPreview" 
                                    wire:loading.attr="disabled"
                                    class="btn btn-primary btn-lg d-flex align-items-center gap-2 px-4 shadow-sm"
                                    {{ !$backupFile ? 'disabled' : '' }}
                                >
                                    <span wire:loading.remove wire:target="processUploadAndPreview">
                                        <i class="ri ri-arrow-right-line"></i> Continuar al Paso 2: Lectura y Previsualización
                                    </span>
                                    <span wire:loading wire:target="processUploadAndPreview">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                        Leyendo y analizando archivo SQL...
                                    </span>
                                </button>
                            </div>

                            <!-- Respaldos disponibles en el servidor (database/respaldos/) -->
                            @if(!empty($serverBackups) && count($serverBackups) > 0)
                                <div class="pt-4 border-top">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <h6 class="mb-0 fw-bold d-flex align-items-center gap-2 text-dark">
                                                <i class="ri ri-folder-database-line text-primary fs-5"></i>
                                                <span>Respaldos almacenados en el servidor (database/respaldos/)</span>
                                            </h6>
                                            <small class="text-muted">Haga clic en cualquiera para cargarlo directamente sin tener que subirlo</small>
                                        </div>
                                        <span class="badge bg-label-primary">{{ count($serverBackups) }} archivo(s)</span>
                                    </div>

                                    <div class="list-group shadow-sm">
                                        @foreach($serverBackups as $backup)
                                            <div class="list-group-item list-group-item-action d-flex flex-wrap align-items-center justify-content-between p-3 gap-2 {{ $backup['name'] === '021020260236PM.sql' ? 'border-primary bg-primary bg-opacity-10' : '' }}">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar bg-primary bg-opacity-10 text-primary rounded p-2">
                                                        <i class="ri ri-file-code-line fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                            <span>{{ $backup['name'] }}</span>
                                                            @if($backup['name'] === '021020260236PM.sql')
                                                                <span class="badge bg-primary fs-8">Respaldo Destacado</span>
                                                            @endif
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="ri ri-hard-drive-line me-1"></i> {{ $backup['size'] }} &nbsp;|&nbsp;
                                                            <i class="ri ri-calendar-line me-1"></i> {{ $backup['date'] }}
                                                        </small>
                                                    </div>
                                                </div>

                                                <button 
                                                    type="button" 
                                                    wire:click="selectServerBackup('{{ $backup['name'] }}')" 
                                                    wire:loading.attr="disabled"
                                                    class="btn {{ $backup['name'] === '021020260236PM.sql' ? 'btn-primary' : 'btn-outline-primary' }} btn-sm d-flex align-items-center gap-1 shadow-sm px-3"
                                                >
                                                    <span wire:loading.remove wire:target="selectServerBackup('{{ $backup['name'] }}')">
                                                        <i class="ri ri-arrow-right-circle-line"></i> Cargar este respaldo
                                                    </span>
                                                    <span wire:loading wire:target="selectServerBackup('{{ $backup['name'] }}')">
                                                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                                        Cargando y analizando...
                                                    </span>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- PASO 2: LECTURA, CARGA Y PREVISUALIZACIÓN DE LOS DATOS          -->
        <!-- ============================================================== -->
        @if($importStep === 2)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle p-2"><i class="ri ri-file-search-line fs-5"></i></span>
                        <div>
                            <h5 class="mb-0 fw-bold">Paso 2: Lectura y Previsualización de los Datos</h5>
                            <small class="text-muted">Análisis detallado de la estructura, tablas y registros del archivo cargado</small>
                        </div>
                    </div>
                    <button type="button" wire:click="backToStep1" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                        <i class="ri ri-arrow-left-line"></i> Cambiar archivo
                    </button>
                </div>
                <div class="card-body py-4">
                    <!-- Advertencia Crítica de Seguridad -->
                    <div class="alert alert-warning border-start border-4 border-warning shadow-sm mb-4">
                        <div class="d-flex gap-3 align-items-center">
                            <i class="ri ri-alert-line text-warning fs-1"></i>
                            <div>
                                <h6 class="fw-bold text-warning mb-1">¡Advertencia de Restauración de Base de Datos!</h6>
                                <p class="mb-0 small text-dark">
                                    Esta operación reemplazará o actualizará los registros de la base de datos institucional actual 
                                    (<strong>{{ $previewData['targetDatabase'] }}</strong>). 
                                    Se detectaron <strong>{{ $previewData['detectedTablesCount'] }} tablas</strong> en el archivo. 
                                    Las tablas marcadas como <span class="badge bg-label-danger">Sobrescribirá</span> reemplazarán la información existente.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Métricas del archivo analizado -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-sm-6">
                            <div class="card border border-light shadow-none bg-light h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar bg-primary bg-opacity-10 text-primary rounded p-2">
                                            <i class="ri ri-file-zip-line fs-3"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Archivo & Formato</small>
                                            <strong class="fs-6 text-truncate d-block" style="max-width: 170px;" title="{{ $fileInfo['name'] }}">{{ $fileInfo['name'] }}</strong>
                                            <span class="badge bg-label-primary fs-8">{{ $fileInfo['format'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card border border-light shadow-none bg-light h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar bg-info bg-opacity-10 text-info rounded p-2">
                                            <i class="ri ri-hard-drive-2-line fs-3"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Tamaño Descomprimido</small>
                                            <strong class="fs-5">{{ $previewData['uncompressedSizeFormatted'] }}</strong>
                                            <small class="text-muted d-block fs-8">(Subido: {{ $fileInfo['sizeFormatted'] }})</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card border border-light shadow-none bg-light h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar bg-success bg-opacity-10 text-success rounded p-2">
                                            <i class="ri ri-table-line fs-3"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Tablas Detectadas</small>
                                            <strong class="fs-5">{{ $previewData['detectedTablesCount'] }} tablas</strong>
                                            <small class="text-muted d-block fs-8">Existentes en BD: {{ $previewData['existingTablesCount'] }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="card border border-light shadow-none bg-light h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar bg-warning bg-opacity-10 text-warning rounded p-2">
                                            <i class="ri ri-terminal-box-line fs-3"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Sentencias SQL Estimadas</small>
                                            <strong class="fs-5">{{ number_format($previewData['totalStatements']) }}</strong>
                                            <small class="text-muted d-block fs-8">{{ $previewData['totalChunks'] }} lotes de ejecución</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Tablas Detectadas -->
                    <div class="card border border-light shadow-none mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                            <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                                <i class="ri ri-list-check-3 text-primary"></i>
                                <span>Previsualización de Tablas a Restaurar ({{ count($previewData['tables']) }})</span>
                            </h6>
                            <div class="d-flex gap-2">
                                <span class="badge bg-label-danger">{{ $previewData['overwriteTablesCount'] }} Sobrescriben</span>
                                <span class="badge bg-label-success">{{ $previewData['newTablesCount'] }} Nuevas</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                <table class="table table-hover table-sm align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Nombre de Tabla</th>
                                            <th class="text-center">Estructura</th>
                                            <th class="text-center">Inserciones</th>
                                            <th class="text-end">Estado en BD ({{ $previewData['targetDatabase'] }})</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($previewData['tables'] as $index => $tbl)
                                            <tr>
                                                <td class="text-muted small">{{ $index + 1 }}</td>
                                                <td>
                                                    <code class="text-dark fw-bold">{{ $tbl['name'] }}</code>
                                                </td>
                                                <td class="text-center">
                                                    @if($tbl['creates'] > 0)
                                                        <span class="badge bg-label-primary fs-8">CREATE ({{ $tbl['creates'] }})</span>
                                                    @else
                                                        <span class="text-muted fs-8">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($tbl['inserts'] > 0)
                                                        <span class="badge bg-label-info fs-8">{{ number_format($tbl['inserts']) }} insert(s)</span>
                                                    @else
                                                        <span class="text-muted fs-8">Sin datos</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($tbl['exists'])
                                                        <span class="badge bg-label-danger">
                                                            <i class="ri ri-error-warning-line me-1"></i> Sobrescribe existente
                                                        </span>
                                                    @else
                                                        <span class="badge bg-label-success">
                                                            <i class="ri ri-add-circle-line me-1"></i> Tabla nueva
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-3 text-muted">No se detectaron tablas explícitas en el volcado SQL.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Navegación -->
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" wire:click="backToStep1" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                            <i class="ri ri-arrow-left-line"></i> Atrás (Elegir otro archivo)
                        </button>
                        <button type="button" wire:click="goToStep3" class="btn btn-primary btn-lg d-flex align-items-center gap-2 px-4 shadow-sm">
                            <span>Continuar al Paso 3: Confirmación de Seguridad</span>
                            <i class="ri ri-arrow-right-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- PASO 3: INGRESO DE LA CONTRASEÑA DEL USUARIO                   -->
        <!-- ============================================================== -->
        @if($importStep === 3)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle p-2"><i class="ri ri-lock-password-line fs-5"></i></span>
                        <div>
                            <h5 class="mb-0 fw-bold">Paso 3: Verificación de Seguridad y Contraseña</h5>
                            <small class="text-muted">Autorización del usuario administrador para ejecutar la restauración</small>
                        </div>
                    </div>
                    <button type="button" wire:click="backToStep2" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                        <i class="ri ri-arrow-left-line"></i> Volver a Previsualización
                    </button>
                </div>
                <div class="card-body py-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-6 col-md-8">
                            <!-- Tarjeta de Identidad del Usuario -->
                            <div class="card border border-primary bg-primary bg-opacity-10 mb-4">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="avatar avatar-md bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fs-4">
                                        <i class="ri ri-user-settings-line"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-bold text-dark">{{ auth()->user()->name ?? 'Administrador' }}</h6>
                                        <small class="text-muted d-block">{{ auth()->user()->email ?? '' }}</small>
                                        <small class="text-primary fw-semibold">Base de datos a intervenir: <strong>{{ DB::getDatabaseName() }}</strong></small>
                                    </div>
                                    <span class="badge bg-primary">Autorización requerida</span>
                                </div>
                            </div>

                            <div class="text-center mb-4">
                                <i class="ri ri-shield-keyhole-line text-warning display-4 mb-2"></i>
                                <h5 class="fw-bold mb-1">Confirmar Restauración de Base de Datos</h5>
                                <p class="text-muted small">
                                    Por motivos de seguridad institucional y para prevenir modificaciones no deseadas, 
                                    ingrese su contraseña de acceso actual para iniciar la ejecución del proceso.
                                </p>
                            </div>

                            <!-- Campo de Contraseña con toggle show/hide -->
                            <div x-data="{ showPass: false }" class="mb-3">
                                <label for="userPasswordInput" class="form-label fw-bold">
                                    <i class="ri ri-key-2-line me-1"></i> Contraseña de Usuario Administrador *
                                </label>
                                <div class="input-group input-group-lg">
                                    <input 
                                        :type="showPass ? 'text' : 'password'" 
                                        wire:model="userPassword" 
                                        wire:keydown.enter="verifyPasswordAndProceed"
                                        id="userPasswordInput" 
                                        class="form-control @if($passwordError) is-invalid @endif" 
                                        placeholder="Ingrese su contraseña actual..."
                                        autocomplete="current-password"
                                        autofocus
                                    >
                                    <button 
                                        type="button" 
                                        @click="showPass = !showPass" 
                                        class="btn btn-outline-secondary" 
                                        tabindex="-1"
                                    >
                                        <i :class="showPass ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                                    </button>
                                </div>
                                @if($passwordError)
                                    <div class="text-danger small mt-2 d-flex align-items-center gap-1">
                                        <i class="ri ri-error-warning-line"></i>
                                        <span>{{ $passwordError }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-4">
                                <i class="ri ri-information-line fs-5"></i>
                                <span>Al validar su contraseña, el sistema procederá inmediatamente con el <strong>Paso 4</strong> ejecutando los lotes de la base de datos.</span>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <button type="button" wire:click="backToStep2" class="btn btn-outline-secondary">
                                    <i class="ri ri-arrow-left-line"></i> Cancelar y Volver
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="verifyPasswordAndProceed" 
                                    wire:loading.attr="disabled"
                                    class="btn btn-danger btn-lg d-flex align-items-center gap-2 px-4 shadow-sm"
                                >
                                    <span wire:loading.remove wire:target="verifyPasswordAndProceed">
                                        <i class="ri ri-lock-unlock-line"></i> Validar y Continuar al Paso 4
                                    </span>
                                    <span wire:loading wire:target="verifyPasswordAndProceed">
                                        <span class="spinner-border spinner-border-sm me-1"></span>
                                        Verificando credenciales...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- PASO 4: BARRA DE PROGRESO 0% AL 100% Y EJECUCIÓN               -->
        <!-- ============================================================== -->
        @if($importStep === 4)
            <div 
                x-data="{
                    isExecuting: false,
                    async runImport() {
                        if (this.isExecuting) return;
                        this.isExecuting = true;
                        try {
                            while ($wire.isImporting && $wire.importProgress < 100 && !$wire.importError) {
                                const res = await $wire.executeImportBatch();
                                if (!res || res.finished || res.error) break;
                            }
                        } catch (e) {
                            console.error('Error durante la importación:', e);
                        } finally {
                            this.isExecuting = false;
                        }
                    }
                }" 
                x-init="runImport()"
                class="card border-0 shadow-sm"
            >
                <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $importCompleted ? 'bg-success' : 'bg-primary' }} rounded-circle p-2">
                            <i class="{{ $importCompleted ? 'ri-checkbox-circle-line' : 'ri-loader-4-line' }} fs-5"></i>
                        </span>
                        <div>
                            <h5 class="mb-0 fw-bold">Paso 4: Ejecución y Progreso de la Restauración</h5>
                            <small class="text-muted">Proceso activo de ejecución por lotes de sentencias SQL</small>
                        </div>
                    </div>
                    @if($importCompleted)
                        <span class="badge bg-success px-3 py-2 fs-7">
                            <i class="ri ri-check-line me-1"></i> Completado 100%
                        </span>
                    @elseif($importError)
                        <span class="badge bg-danger px-3 py-2 fs-7">
                            <i class="ri ri-close-circle-line me-1"></i> Error en proceso
                        </span>
                    @else
                        <span class="badge bg-label-primary px-3 py-2 fs-7 d-flex align-items-center gap-1">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            <span>En ejecución...</span>
                        </span>
                    @endif
                </div>

                <div class="card-body py-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 text-center">

                            @if(!$importCompleted && !$importError)
                                <!-- Animación de Importación Activa -->
                                <div class="mb-4">
                                    <div class="avatar avatar-xl mx-auto bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                        <i class="ri ri-database-2-line fs-1 spinner-grow text-primary" style="width: 48px; height: 48px;"></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">Restaurando Base de Datos</h4>
                                    <p class="text-muted">{{ $importStatusMessage ?: 'Iniciando y ejecutando sentencias...' }}</p>
                                </div>

                                <!-- Barra de Progreso del 0% al 100% -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold text-dark">Progreso de Restauración</span>
                                        <span class="badge bg-primary fs-6">{{ $importProgress }}%</span>
                                    </div>
                                    <div class="progress" style="height: 28px; border-radius: 14px; background-color: #e9ecef;">
                                        <div 
                                            class="progress-bar progress-bar-striped progress-bar-animated bg-primary fw-bold fs-6" 
                                            role="progressbar" 
                                            style="width: {{ $importProgress }}%; transition: width 0.3s ease-in-out;" 
                                            aria-valuenow="{{ $importProgress }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100"
                                        >
                                            {{ $importProgress }}%
                                        </div>
                                    </div>
                                </div>

                                <div class="card border border-light bg-light p-3 mb-4 text-start">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Lote procesado:</span>
                                        <strong>{{ $importCurrentChunk }} de {{ $importTotalChunks }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Base de datos:</span>
                                        <strong>{{ DB::getDatabaseName() }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Estado de claves foráneas:</span>
                                        <strong class="text-warning">Deshabilitadas temporalmente (FOREIGN_KEY_CHECKS = 0)</strong>
                                    </div>
                                </div>

                                <p class="small text-muted mb-0">
                                    <i class="ri ri-information-line me-1"></i> 
                                    Por favor mantenga esta ventana abierta mientras se completa la restauración.
                                </p>
                            @endif

                            <!-- Estado: ERROR -->
                            @if($importError)
                                <div class="avatar avatar-xl mx-auto bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="ri ri-error-warning-line fs-1"></i>
                                </div>
                                <h4 class="fw-bold text-danger mb-2">Error durante la restauración</h4>
                                <div class="alert alert-danger text-start mb-4">
                                    <strong>Detalles del error:</strong>
                                    <p class="mb-0 font-monospace small mt-1">{{ $importError }}</p>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <button type="button" wire:click="resetImport" class="btn btn-outline-secondary">
                                        <i class="ri ri-refresh-line me-1"></i> Reiniciar Asistente
                                    </button>
                                </div>
                            @endif

                            <!-- Estado: COMPLETADO EXITOSAMENTE (100%) -->
                            @if($importCompleted)
                                <div class="avatar avatar-xl mx-auto bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="ri ri-checkbox-circle-line fs-1"></i>
                                </div>
                                <h3 class="fw-bold text-success mb-2">¡Base de Datos Restaurada con Éxito!</h3>
                                <p class="text-muted mb-4">
                                    Todas las sentencias y tablas del archivo de respaldo fueron ejecutadas satisfactoriamente en <strong>{{ $importStats['targetDatabase'] }}</strong>.
                                </p>

                                <!-- Barra en 100% -->
                                <div class="progress mb-4" style="height: 28px; border-radius: 14px;">
                                    <div class="progress-bar bg-success fw-bold fs-6" style="width: 100%;">
                                        100% Completado
                                    </div>
                                </div>

                                <!-- Resumen de Estadísticas -->
                                <div class="row g-3 mb-4">
                                    <div class="col-sm-4">
                                        <div class="p-3 bg-light rounded text-center">
                                            <small class="text-muted d-block">Sentencias Ejecutadas</small>
                                            <strong class="fs-4 text-dark">{{ number_format($importStats['queriesExecuted']) }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="p-3 bg-light rounded text-center">
                                            <small class="text-muted d-block">Tablas Restauradas</small>
                                            <strong class="fs-4 text-dark">{{ $importStats['tablesRestored'] }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="p-3 bg-light rounded text-center">
                                            <small class="text-muted d-block">Tiempo de Ejecución</small>
                                            <strong class="fs-4 text-dark">{{ $importStats['timeElapsed'] }}s</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center gap-3">
                                    <button type="button" wire:click="resetImport" class="btn btn-outline-primary d-flex align-items-center gap-2">
                                        <i class="ri ri-upload-cloud-2-line"></i> Realizar otra importación
                                    </button>
                                    <button type="button" onclick="window.location.reload()" class="btn btn-success d-flex align-items-center gap-2 px-4 shadow-sm">
                                        <i class="ri ri-refresh-line"></i> Finalizar y Recargar Página
                                    </button>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- =================================================================== -->
    <!-- PESTAÑA 2: EXPORTAR BASE DE DATOS                                  -->
    <!-- =================================================================== -->
    @if($activeTab === 'export')
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-circle p-2"><i class="ri ri-download-2-line fs-5"></i></span>
                    <div>
                        <h5 class="mb-0 fw-bold">Exportador de Base de Datos</h5>
                        <small class="text-muted">Genere respaldos completos o por tabla en formatos comprimidos (.sql.gz, .zip) o SQL plano</small>
                    </div>
                </div>
            </div>

            <div class="card-body py-4">
                <!-- Alcance de Exportación (Toda la BD vs Tabla Individual) -->
                <div class="mb-4">
                    <label class="form-label fw-bold mb-2">Seleccione el Alcance del Respaldo *</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="card card-border-shadow-primary cursor-pointer h-100 p-3 border {{ $exportScope === 'all' ? 'border-primary bg-primary bg-opacity-10 shadow-sm' : 'border-light' }}">
                                <div class="d-flex align-items-start gap-3">
                                    <input type="radio" wire:model.live="exportScope" value="all" class="form-check-input mt-1">
                                    <div>
                                        <h6 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                                            <i class="ri ri-database-2-line text-primary"></i>
                                            <span>Toda la Base de Datos (Recomendado)</span>
                                        </h6>
                                        <small class="text-muted">
                                            Exporta las <strong>{{ count($availableTables) }} tablas</strong> con su estructura completa (CREATE TABLE) y todos sus registros (INSERT INTO).
                                        </small>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="col-md-6">
                            <label class="card card-border-shadow-secondary cursor-pointer h-100 p-3 border {{ $exportScope === 'single' ? 'border-primary bg-primary bg-opacity-10 shadow-sm' : 'border-light' }}">
                                <div class="d-flex align-items-start gap-3">
                                    <input type="radio" wire:model.live="exportScope" value="single" class="form-check-input mt-1">
                                    <div>
                                        <h6 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                                            <i class="ri ri-table-line text-primary"></i>
                                            <span>Tabla Específica</span>
                                        </h6>
                                        <small class="text-muted">
                                            Seleccione una tabla en particular para exportar solo su estructura y registros, con opción de filtros y columnas.
                                        </small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Formato de Exportación -->
                <div class="mb-4">
                    <label class="form-label fw-bold mb-2">Formato de Exportación *</label>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="card cursor-pointer p-3 border h-100 {{ $exportFormat === 'sql_gz' ? 'border-success bg-success bg-opacity-10 shadow-sm' : 'border-light' }}">
                                <div class="d-flex align-items-start gap-3">
                                    <input type="radio" wire:model.live="exportFormat" value="sql_gz" class="form-check-input mt-1">
                                    <div>
                                        <h6 class="mb-1 fw-bold text-success d-flex align-items-center gap-1">
                                            <i class="ri ri-file-zip-line"></i>
                                            <span>GZip Comprimido (.sql.gz)</span>
                                        </h6>
                                        <small class="text-muted">Recomendado. Reduce el tamaño hasta un 85% para descargas ultrarrápidas.</small>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="col-md-4">
                            <label class="card cursor-pointer p-3 border h-100 {{ $exportFormat === 'zip' ? 'border-warning bg-warning bg-opacity-10 shadow-sm' : 'border-light' }}">
                                <div class="d-flex align-items-start gap-3">
                                    <input type="radio" wire:model.live="exportFormat" value="zip" class="form-check-input mt-1">
                                    <div>
                                        <h6 class="mb-1 fw-bold text-warning d-flex align-items-center gap-1">
                                            <i class="ri ri-folder-zip-line"></i>
                                            <span>ZIP Comprimido (.zip)</span>
                                        </h6>
                                        <small class="text-muted">Compatible universalmente con exploradores Windows, macOS y Linux.</small>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="col-md-4">
                            <label class="card cursor-pointer p-3 border h-100 {{ $exportFormat === 'sql' ? 'border-primary bg-primary bg-opacity-10 shadow-sm' : 'border-light' }}">
                                <div class="d-flex align-items-start gap-3">
                                    <input type="radio" wire:model.live="exportFormat" value="sql" class="form-check-input mt-1">
                                    <div>
                                        <h6 class="mb-1 fw-bold text-primary d-flex align-items-center gap-1">
                                            <i class="ri ri-file-code-line"></i>
                                            <span>SQL Plano (.sql)</span>
                                        </h6>
                                        <small class="text-muted">Archivo de texto estándar sin compresión, editable directamente.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Si es tabla individual, mostrar selectores adicionales -->
                @if($exportScope === 'single')
                    <div class="row g-3 mb-4 p-3 bg-light rounded border">
                        <div class="col-md-6">
                            <label for="exportTableSelect" class="form-label fw-bold">Seleccionar Tabla *</label>
                            <select wire:model.live="selectedTable" id="exportTableSelect" class="form-select">
                                <option value="">-- Seleccione una tabla --</option>
                                @foreach($availableTables as $tblKey => $tblTitle)
                                    <option value="{{ $tblKey }}">{{ $tblTitle }} ({{ $tblKey }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="exportFormatExtra" class="form-label fw-bold">Otros formatos tabulares (Opcional)</label>
                            <select wire:model.live="exportFormat" id="exportFormatExtra" class="form-select">
                                <option value="sql_gz">GZip (.sql.gz) - SQL</option>
                                <option value="zip">ZIP (.zip) - SQL</option>
                                <option value="sql">SQL (.sql) - SQL</option>
                                <option value="xlsx">Excel (.xlsx)</option>
                                <option value="csv">CSV (.csv)</option>
                                <option value="pdf">PDF (.pdf)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Columnas de la tabla seleccionada -->
                    @if($selectedTable && count($availableColumns) > 0)
                        <div class="card border border-light mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                <h6 class="mb-0 fw-bold">Columnas a Exportar ({{ count($selectedColumns) }} de {{ count($availableColumns) }})</h6>
                                <div>
                                    <button type="button" wire:click="selectAllColumns" class="btn btn-xs btn-outline-primary me-1">Seleccionar Todas</button>
                                    <button type="button" wire:click="deselectAllColumns" class="btn btn-xs btn-outline-secondary">Deseleccionar</button>
                                </div>
                            </div>
                            <div class="card-body py-3">
                                <div class="row g-2" style="max-height: 200px; overflow-y: auto;">
                                    @foreach($availableColumns as $colKey => $colMeta)
                                        <div class="col-md-3 col-sm-4">
                                            <div class="form-check">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model="selectedColumns" 
                                                    value="{{ $colKey }}" 
                                                    id="col_{{ $colKey }}" 
                                                    class="form-check-input"
                                                >
                                                <label for="col_{{ $colKey }}" class="form-check-label small text-truncate d-block" title="{{ $colMeta['name'] }}">
                                                    {{ $colMeta['name'] }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Nombre de archivo personalizado opcional -->
                <div class="mb-4">
                    <label for="customExportFilename" class="form-label fw-bold">Nombre del Archivo (Opcional)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri ri-file-text-line"></i></span>
                        <input 
                            type="text" 
                            wire:model="exportFileName" 
                            id="customExportFilename" 
                            class="form-control" 
                            placeholder="{{ $this->generateDefaultFileName() }}"
                        >
                    </div>
                    <small class="text-muted">Si se deja vacío, el sistema generará automáticamente un nombre descriptivo con la fecha y hora.</small>
                </div>

                <!-- Barra de Progreso de Exportación (0% a 100%) -->
                @if($isExporting)
                    <div class="card border border-primary bg-primary bg-opacity-10 p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-primary d-flex align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                <span>{{ $exportStatusMessage }}</span>
                            </span>
                            <span class="badge bg-primary fs-6">{{ $exportProgress }}%</span>
                        </div>
                        <div class="progress" style="height: 24px; border-radius: 12px;">
                            <div 
                                class="progress-bar progress-bar-striped progress-bar-animated bg-primary fw-bold" 
                                role="progressbar" 
                                style="width: {{ $exportProgress }}%;" 
                                aria-valuenow="{{ $exportProgress }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100"
                            >
                                {{ $exportProgress }}%
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Resumen de Exportación Completada -->
                @if($exportCompleted && !empty($exportDownloadFile))
                    <div class="alert alert-success d-flex align-items-center justify-content-between p-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <i class="ri ri-checkbox-circle-line fs-2 text-success"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-success">¡Respaldo generado y listo para descargar!</h6>
                                <small class="text-dark">
                                    Archivo: <strong>{{ $exportStats['fileName'] }}</strong> | Tamaño: <strong>{{ $exportStats['fileSize'] }}</strong> | Formato: <strong>{{ $exportStats['format'] }}</strong>
                                </small>
                            </div>
                        </div>
                        <button type="button" wire:click="downloadGeneratedExport" class="btn btn-success d-flex align-items-center gap-2 px-3 shadow-sm">
                            <i class="ri ri-download-line fs-5"></i> Descargar Respaldo
                        </button>
                    </div>
                @endif

                @if($exportError)
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                        <i class="ri ri-error-warning-line fs-5"></i>
                        <div>{{ $exportError }}</div>
                    </div>
                @endif

                <!-- Botón de Inicio de Exportación -->
                <div class="d-flex justify-content-end gap-2">
                    <button 
                        type="button" 
                        wire:click="startExport" 
                        wire:loading.attr="disabled"
                        class="btn btn-primary btn-lg d-flex align-items-center gap-2 px-4 shadow-sm"
                        {{ $isExporting ? 'disabled' : '' }}
                    >
                        <span wire:loading.remove wire:target="startExport">
                            <i class="ri ri-download-cloud-2-line"></i> Iniciar Exportación de Base de Datos
                        </span>
                        <span wire:loading wire:target="startExport">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Generando Respaldo...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>