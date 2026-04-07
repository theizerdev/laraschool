<?php

namespace App\Livewire\Admin\Reportes;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\Student;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\ConceptoPago;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\MorosidadCalculationService;

class EstadoCuentas extends Component
{
    use HasDynamicLayout, HasRegionalFormatting;


    public $estudiantes;
    public $estudiante_id;
    public $matricula_id;
    public $estudianteSeleccionado;
    public $matriculaSeleccionada;
    public $pagos = [];
    public $totalPagado = 0;
    public $saldoPendiente = 0;
    public $detalleCuotas = [];

    protected $morosidadCalculationService;

    public function boot(MorosidadCalculationService $morosidadCalculationService)
    {
        $this->morosidadCalculationService = $morosidadCalculationService;
    }

    public function mount()
    {
        $this->estudiantes = Student::with('matriculas.programa')->get();
    }

    public function updatedEstudianteId()
    {
        $this->estudianteSeleccionado = Student::with('matriculas.programa')
            ->find($this->estudiante_id);

        $this->matricula_id = '';
        $this->matriculaSeleccionada = null;
        $this->pagos = [];
        $this->totalPagado = 0;
        $this->saldoPendiente = 0;
    }

    public function updatedMatriculaId()
    {
        $this->matriculaSeleccionada = Matricula::with([
            'programa.nivelEducativo',
            'cronogramaPagos'
        ])->find($this->matricula_id);

        $this->cargarEstadoCuenta();
    }

    public function cargarEstadoCuenta()
    {
        if (!$this->matricula_id) {
            return;
        }

        // Obtener la matrícula con el cronograma de pagos
        $matricula = Matricula::with([
            'estudiante',
            'programa.nivelEducativo',
            'cronogramaPagos',
            'pagos.detalles.conceptoPago'
        ])->find($this->matricula_id);

        // Obtener los datos de morosidad para esta matrícula
        $morosidadData = $this->morosidadCalculationService->calculateMorosidadData(collect([$matricula]));
        
        if (!empty($morosidadData)) {
            $data = $morosidadData[0];
            $this->totalPagado = $data['pagado_rango'];
            $this->saldoPendiente = $data['saldo_pendiente_rango'];
            
            // Preparar detalle de cuotas para mostrar
            $this->detalleCuotas = $matricula->cronogramaPagos
                ->sortBy('numero_cuota')
                ->map(function ($cuota) {
                    return [
                        'numero_cuota' => $cuota->numero_cuota,
                        'monto' => $cuota->monto,
                        'monto_pagado' => $cuota->monto_pagado,
                        'saldo_pendiente' => $cuota->saldo_pendiente,
                        'fecha_vencimiento' => $cuota->fecha_vencimiento instanceof \Carbon\Carbon ? $cuota->fecha_vencimiento->format('d/m/Y') : $cuota->fecha_vencimiento,
                        'estado' => $cuota->estado,
                        'fecha_pago' => $cuota->fecha_pago ? (
                            $cuota->fecha_pago instanceof \Carbon\Carbon ? 
                            $cuota->fecha_pago->format('d/m/Y') : 
                            $cuota->fecha_pago
                        ) : null
                    ];
                });
        }

        // Obtener todos los pagos de esta matrícula
        $this->pagos = Pago::with(['detalles.conceptoPago'])
            ->where('matricula_id', $this->matricula_id)
            ->get();
    }

    public function exportarExcel()
    {
        if (!$this->matriculaSeleccionada) {
            session()->flash('error', 'Debe seleccionar una matrícula para exportar.');
            return;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar página
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

        // Encabezado principal con logo/empresa
        $sheet->setCellValue('A1', 'ESTADO DE CUENTA ESTUDIANTIL');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setRGB('2E3B4E');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFill()->setFillType('solid')->getStartColor()->setRGB('E8F4FD');

        // Fecha de generación
        $sheet->setCellValue('A2', 'Generado el: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

        // Información del estudiante con formato mejorado
        $sheet->setCellValue('A4', 'INFORMACIÓN DEL ESTUDIANTE');
        $sheet->mergeCells('A4:F4');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4')->getFill()->setFillType('solid')->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal('center');

        $sheet->setCellValue('A5', 'Nombre Completo:');
        $sheet->setCellValue('B5', ($this->estudianteSeleccionado->nombres ?? '') . ' ' . ($this->estudianteSeleccionado->apellidos ?? ''));
        $sheet->setCellValue('D5', 'Documento:');
        $sheet->setCellValue('E5', $this->estudianteSeleccionado->documento_identidad ?? '');

        $sheet->setCellValue('A6', 'Programa:');
        $sheet->setCellValue('B6', $this->matriculaSeleccionada->programa->nombre ?? '');
        $sheet->setCellValue('D6', 'Fecha Matrícula:');
        $sheet->setCellValue('E6', $this->matriculaSeleccionada->fecha_matricula?->format('d/m/Y') ?? 'N/A');

        // Estilo para etiquetas
        $sheet->getStyle('A5:A6')->getFont()->setBold(true);
        $sheet->getStyle('D5:D6')->getFont()->setBold(true);

        // Resumen financiero
        $sheet->setCellValue('A8', 'RESUMEN FINANCIERO');
        $sheet->mergeCells('A8:F8');
        $sheet->getStyle('A8')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A8')->getFill()->setFillType('solid')->getStartColor()->setRGB('70AD47');
        $sheet->getStyle('A8')->getAlignment()->setHorizontal('center');

        $costoTotal = $this->matriculaSeleccionada->costo ?? 0;
        $sheet->setCellValue('A9', 'Costo Total:');
        $sheet->setCellValue('B9', $costoTotal);
        $sheet->setCellValue('C9', 'Total Pagado:');
        $sheet->setCellValue('D9', $this->totalPagado);
        $sheet->setCellValue('E9', 'Saldo Pendiente:');
        $sheet->setCellValue('F9', $this->saldoPendiente);

        // Formato de moneda para resumen
        $sheet->getStyle('B9:F9')->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('A9:F9')->getFont()->setBold(true);

        // Color condicional para saldo
        if ($this->saldoPendiente > 0) {
            $sheet->getStyle('F9')->getFill()->setFillType('solid')->getStartColor()->setRGB('FFE699');
        } else {
            $sheet->getStyle('F9')->getFill()->setFillType('solid')->getStartColor()->setRGB('C6EFCE');
        }

        // Detalle de cuotas
        $sheet->setCellValue('A11', 'DETALLE DE CUOTAS');
        $sheet->mergeCells('A11:F11');
        $sheet->getStyle('A11')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A11')->getFill()->setFillType('solid')->getStartColor()->setRGB('5B9BD5');
        $sheet->getStyle('A11')->getAlignment()->setHorizontal('center');

        // Encabezados de tabla de cuotas
        $headers = ['N° Cuota', 'Monto', 'Pagado', 'Pendiente', 'Vence', 'Estado'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '12', $header);
            $col++;
        }

        // Estilo de encabezados de cuotas
        $sheet->getStyle('A12:F12')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A12:F12')->getFill()->setFillType('solid')->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A12:F12')->getAlignment()->setHorizontal('center');

        // Datos de cuotas
        $row = 13;
        foreach ($this->detalleCuotas as $cuota) {
            $sheet->setCellValue('A' . $row, $cuota['numero_cuota']);
            $sheet->setCellValue('B' . $row, $cuota['monto']);
            $sheet->setCellValue('C' . $row, $cuota['monto_pagado']);
            $sheet->setCellValue('D' . $row, $cuota['saldo_pendiente']);
            $sheet->setCellValue('E' . $row, $cuota['fecha_vencimiento']);
            $sheet->setCellValue('F' . $row, ucfirst($cuota['estado']));

            // Color condicional por estado
            if ($cuota['estado'] === 'pagado') {
                $sheet->getStyle('F' . $row)->getFill()->setFillType('solid')->getStartColor()->setRGB('C6EFCE');
            } elseif ($cuota['estado'] === 'pendiente') {
                $sheet->getStyle('F' . $row)->getFill()->setFillType('solid')->getStartColor()->setRGB('FFE699');
            } else {
                $sheet->getStyle('F' . $row)->getFill()->setFillType('solid')->getStartColor()->setRGB('FFC7CE');
            }

            $row++;
        }

        // Formato de moneda para columnas de montos
        if ($row > 13) {
            $sheet->getStyle('B13:D' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0.00');
        }

        // Detalle de pagos
        $startRow = $row + 1;
        $sheet->setCellValue('A' . $startRow, 'DETALLE DE PAGOS');
        $sheet->mergeCells('A' . $startRow . ':F' . $startRow);
        $sheet->getStyle('A' . $startRow)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $startRow)->getFill()->setFillType('solid')->getStartColor()->setRGB('C55A5A');
        $sheet->getStyle('A' . $startRow)->getAlignment()->setHorizontal('center');

        // Encabezados de tabla de pagos
        $headers = ['Fecha', 'Documento', 'Concepto', 'Método Pago', 'Monto', 'Estado'];
        $col = 'A';
        $startRow++;
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $startRow, $header);
            $col++;
        }

        // Estilo de encabezados de pagos
        $sheet->getStyle($startRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($startRow)->getFill()->setFillType('solid')->getStartColor()->setRGB('C55A5A');
        $sheet->getStyle($startRow)->getAlignment()->setHorizontal('center');

        // Datos de pagos
        $startRow++;
        foreach ($this->pagos as $pago) {
            $conceptos = $pago->detalles->pluck('conceptoPago.nombre')->filter()->implode(', ');

            $sheet->setCellValue('A' . $startRow, $pago->fecha->format('d/m/Y'));
            $sheet->setCellValue('B' . $startRow, $pago->numero_completo ?? 'N/A');
            $sheet->setCellValue('C' . $startRow, $conceptos ?: 'N/A');
            $sheet->setCellValue('D' . $startRow, ucfirst($pago->metodo_pago ?? 'N/A'));
            $sheet->setCellValue('E' . $startRow, $pago->total);
            $sheet->setCellValue('F' . $startRow, ucfirst($pago->estado));

            // Color condicional por estado
            if ($pago->estado === 'aprobado') {
                $sheet->getStyle('F' . $startRow)->getFill()->setFillType('solid')->getStartColor()->setRGB('C6EFCE');
            } elseif ($pago->estado === 'pendiente') {
                $sheet->getStyle('F' . $startRow)->getFill()->setFillType('solid')->getStartColor()->setRGB('FFE699');
            } else {
                $sheet->getStyle('F' . $startRow)->getFill()->setFillType('solid')->getStartColor()->setRGB('FFC7CE');
            }

            $startRow++;
        }

        // Formato de moneda para columna de montos de pagos
        if ($startRow > ($row + 3)) {
            $sheet->getStyle('E' . ($row + 3) . ':E' . ($startRow - 1))->getNumberFormat()->setFormatCode('$#,##0.00');
        }

        // Totales finales
        $startRow += 1;
        $sheet->setCellValue('D' . $startRow, 'TOTAL PAGADO:');
        $sheet->setCellValue('E' . $startRow, $this->totalPagado);
        $sheet->getStyle('D' . $startRow . ':E' . $startRow)->getFont()->setBold(true);
        $sheet->getStyle('E' . $startRow)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('D' . $startRow . ':E' . $startRow)->getFill()->setFillType('solid')->getStartColor()->setRGB('D9E2F3');

        // Bordes para toda la tabla
        $sheet->getStyle('A12:F' . $startRow)->getBorders()->getAllBorders()->setBorderStyle('thin');

        // Ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);

        // Pie de página
        $footerRow = $startRow + 3;
        $sheet->setCellValue('A' . $footerRow, 'Este documento es un estado de cuenta oficial generado automáticamente.');
        $sheet->mergeCells('A' . $footerRow . ':F' . $footerRow);
        $sheet->getStyle('A' . $footerRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A' . $footerRow)->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('666666');

        // Crear respuesta de descarga
        $nombreEstudiante = str_replace(' ', '_', ($this->estudianteSeleccionado->nombres ?? '') . '_' . ($this->estudianteSeleccionado->apellidos ?? ''));
        $filename = 'Estado_Cuenta_' . $nombreEstudiante . '_' . date('Y-m-d') . '.xlsx';

        return new StreamedResponse(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . urlencode($filename) . '"',
            ]
        );
    }

    public function exportarPDF()
    {
        // Lógica para exportar a PDF
        session()->flash('message', 'Funcionalidad de exportación en desarrollo.');
    }

    public function render()
    {
        return view('livewire.admin.reportes.estado-cuentas')->layout($this->getLayout());
    }
}
