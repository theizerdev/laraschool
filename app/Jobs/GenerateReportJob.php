<?php

namespace App\Jobs;

use App\Models\ReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutos de timeout
    
    protected $exportId;
    protected $type;
    protected $filters;

    /**
     * Create a new job instance.
     */
    public function __construct($exportId, $type, $filters = [])
    {
        $this->exportId = $exportId;
        $this->type = $type;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $export = ReportExport::find($this->exportId);
        
        if (!$export) {
            Log::error("Export not found: {$this->exportId}");
            return;
        }

        try {
            $export->update([
                'status' => 'processing',
                'started_at' => now()
            ]);

            switch ($this->type) {
                case 'morosidad':
                    $filePath = $this->generateMorosidadReport($this->filters);
                    break;
                    
                case 'matriculas':
                    $filePath = $this->generateMatriculasReport($this->filters);
                    break;
                    
                default:
                    throw new \Exception("Tipo de reporte desconocido: {$this->type}");
            }

            $export->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'completed_at' => now()
            ]);

        } catch (\Exception $e) {
            Log::error("Error generating report: " . $e->getMessage());
            
            $export->update([
                'status' => 'failed',
                'completed_at' => now()
            ]);
        }
    }

    /**
     * Generar reporte de morosidad
     */
    private function generateMorosidadReport($filters)
    {
        // Simular la obtención de datos de morosidad
        // En la implementación real, esto vendría de tu lógica de negocio
        $data = $this->getMorosidadData($filters);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Encabezados
        $headers = ['ID', 'Estudiante', 'Programa', 'Nivel', 'Turno', 'Costo', 'Pagado', 'Pendiente', 'Fecha Matrícula', 'Estado'];
        foreach (range('A', chr(ord('A') + count($headers) - 1)) as $i => $col) {
            $sheet->setCellValue($col . '1', $headers[$i]);
        }
        
        // Datos
        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['id']);
            $sheet->setCellValue('B' . $row, $item['estudiante']);
            $sheet->setCellValue('C' . $row, $item['programa']);
            $sheet->setCellValue('D' . $row, $item['nivel']);
            $sheet->setCellValue('E' . $row, $item['turno']);
            $sheet->setCellValue('F' . $row, $item['costo']);
            $sheet->setCellValue('G' . $row, $item['pagado']);
            $sheet->setCellValue('H' . $row, $item['pendiente']);
            $sheet->setCellValue('I' . $row, $item['fecha_matricula']);
            $sheet->setCellValue('J' . $row, $item['estado']);
            
            $row++;
        }
        
        // Formato monetario
        $lastRow = $row - 1;
        if ($lastRow > 1) {
            $sheet->getStyle('F2:H' . $lastRow)->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }
        
        // Guardar archivo
        $fileName = 'reporte_morosidad_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'reports/' . $fileName;
        
        $writer = new Xlsx($spreadsheet);
        $fullPath = Storage::disk('local')->path($filePath);
        
        // Asegurarse de que el directorio existe
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $writer->save($fullPath);
        
        return $filePath;
    }

    /**
     * Generar reporte de matrículas
     */
    private function generateMatriculasReport($filters)
    {
        // Simular la obtención de datos de matrículas
        // En la implementación real, esto vendría de tu lógica de negocio
        $data = $this->getMatriculasData($filters);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Encabezados
        $headers = ['ID', 'Estudiante', 'Programa', 'Nivel', 'Turno', 'Costo', 'Cuota Inicial', 'Nº Cuotas', 'Fecha Matrícula', 'Estado', 'Solvente'];
        foreach (range('A', chr(ord('A') + count($headers) - 1)) as $i => $col) {
            $sheet->setCellValue($col . '1', $headers[$i]);
        }
        
        // Datos
        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['id']);
            $sheet->setCellValue('B' . $row, $item['estudiante']);
            $sheet->setCellValue('C' . $row, $item['programa']);
            $sheet->setCellValue('D' . $row, $item['nivel']);
            $sheet->setCellValue('E' . $row, $item['turno']);
            $sheet->setCellValue('F' . $row, $item['costo']);
            $sheet->setCellValue('G' . $row, $item['cuota_inicial']);
            $sheet->setCellValue('H' . $row, $item['numero_cuotas']);
            $sheet->setCellValue('I' . $row, $item['fecha_matricula']);
            $sheet->setCellValue('J' . $row, $item['estado']);
            $sheet->setCellValue('K' . $row, $item['solvente'] ? 'Sí' : 'No');
            
            $row++;
        }
        
        // Formato monetario
        $lastRow = $row - 1;
        if ($lastRow > 1) {
            $sheet->getStyle('F2:G' . $lastRow)->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }
        
        // Guardar archivo
        $fileName = 'reporte_matriculas_' . date('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'reports/' . $fileName;
        
        $writer = new Xlsx($spreadsheet);
        $fullPath = Storage::disk('local')->path($filePath);
        
        // Asegurarse de que el directorio existe
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $writer->save($fullPath);
        
        return $filePath;
    }

    /**
     * Obtener datos simulados de morosidad (esto debería ser reemplazado por la lógica real)
     */
    private function getMorosidadData($filters)
    {
        // En la implementación real, aquí iría la lógica para obtener los datos
        // de morosidad basados en los filtros proporcionados
        return [
            [
                'id' => 1,
                'estudiante' => 'Juan Pérez',
                'programa' => 'Matemáticas',
                'nivel' => 'Secundaria',
                'turno' => 'Mañana',
                'costo' => 1200.00,
                'pagado' => 800.00,
                'pendiente' => 400.00,
                'fecha_matricula' => '2024-01-15',
                'estado' => 'Moroso'
            ],
            [
                'id' => 2,
                'estudiante' => 'Ana Gómez',
                'programa' => 'Biología',
                'nivel' => 'Secundaria',
                'turno' => 'Tarde',
                'costo' => 1500.00,
                'pagado' => 1500.00,
                'pendiente' => 0.00,
                'fecha_matricula' => '2024-01-16',
                'estado' => 'Al día'
            ]
        ];
    }

    /**
     * Obtener datos simulados de matrículas (esto debería ser reemplazado por la lógica real)
     */
    private function getMatriculasData($filters)
    {
        // En la implementación real, aquí iría la lógica para obtener los datos
        // de matrículas basados en los filtros proporcionados
        return [
            [
                'id' => 1,
                'estudiante' => 'Juan Pérez',
                'programa' => 'Matemáticas',
                'nivel' => 'Secundaria',
                'turno' => 'Mañana',
                'costo' => 1200.00,
                'cuota_inicial' => 300.00,
                'numero_cuotas' => 3,
                'fecha_matricula' => '2024-01-15',
                'estado' => 'Activo',
                'solvente' => false
            ],
            [
                'id' => 2,
                'estudiante' => 'Ana Gómez',
                'programa' => 'Biología',
                'nivel' => 'Secundaria',
                'turno' => 'Tarde',
                'costo' => 1500.00,
                'cuota_inicial' => 500.00,
                'numero_cuotas' => 2,
                'fecha_matricula' => '2024-01-16',
                'estado' => 'Activo',
                'solvente' => true
            ]
        ];
    }
}