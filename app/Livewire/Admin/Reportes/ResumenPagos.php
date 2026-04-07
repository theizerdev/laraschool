<?php

namespace App\Livewire\Admin\Reportes;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\Pago;
use App\Models\SchoolPeriod;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResumenPagos extends Component
{
    use HasDynamicLayout;
    use HasRegionalFormatting;


    public $periodos;
    public $periodo_id;
    public $fecha_inicio;
    public $fecha_fin;
    public $pagos = [];
    public $totales = [];

    public function mount()
    {
        $this->periodos = SchoolPeriod::all();
        $this->fecha_inicio = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_fin = now()->endOfMonth()->format('Y-m-d');
        $this->cargarReporte();
    }

    public function updatedPeriodoId()
    {
        if ($this->periodo_id) {
            $periodo = SchoolPeriod::find($this->periodo_id);
            if ($periodo) {
                // Ensure dates are set, fallback to period name if dates are missing
                $this->fecha_inicio = $periodo->fecha_inicio?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
                $this->fecha_fin = $periodo->fecha_fin?->format('Y-m-d') ?? now()->endOfMonth()->format('Y-m-d');

                // If period has no dates, use the period name in the display
                if (!$periodo->fecha_inicio || !$periodo->fecha_fin) {
                    session()->flash('warning', 'El período seleccionado no tiene fechas definidas. Se usará el rango actual.');
                }

                $this->cargarReporte();
            }
        }
    }

    public $cargando = false;

    public function cargarReporte()
    {
        try {
            \Log::info('Iniciando cargarReporte', [
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin' => $this->fecha_fin,
                'periodo_id' => $this->periodo_id
            ]);

            $this->cargando = true;
            $this->resetErrorBag();

            \Log::debug('Validando fechas');

            // Validar fechas
            if (!$this->fecha_inicio || !$this->fecha_fin) {
                \Log::warning('Fechas no proporcionadas');
                throw new \Exception('Debe seleccionar un rango de fechas válido');
            }

            if ($this->fecha_inicio > $this->fecha_fin) {
                \Log::warning('Fechas inválidas', [
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_fin' => $this->fecha_fin
                ]);
                throw new \Exception('La fecha de inicio no puede ser mayor a la fecha fin');
            }

            \Log::debug('Obteniendo pagos');
            $this->pagos = Pago::with(['matricula' => function($query) {
                    $query->with(['student', 'programa']);
                }])
                ->whereBetween('fecha', [$this->fecha_inicio, $this->fecha_fin])
                ->where('estado', 'aprobado')
                ->get();

            \Log::debug('Calculando totales');
            $this->totales = DB::table('pagos')
                ->join('pago_detalles', 'pagos.id', '=', 'pago_detalles.pago_id')
                ->join('conceptos_pago', 'pago_detalles.concepto_pago_id', '=', 'conceptos_pago.id')
                ->select(
                    'conceptos_pago.nombre as concepto',
                    DB::raw('SUM(pago_detalles.subtotal) as total'),
                    DB::raw('COUNT(DISTINCT pagos.id) as cantidad')
                )
                ->whereBetween('pagos.fecha', [$this->fecha_inicio, $this->fecha_fin])
                ->where('pagos.estado', 'aprobado')
                ->groupBy('conceptos_pago.nombre')
                ->get();

            if ($this->pagos->isEmpty()) {
                \Log::info('No se encontraron pagos');
                session()->flash('info', 'No se encontraron pagos en el rango seleccionado');
            } else {
                \Log::info('Pagos encontrados', ['count' => $this->pagos->count()]);
            }
        } catch (\Exception $e) {
            \Log::error('Error en cargarReporte: ' . $e->getMessage());
            $this->addError('error', $e->getMessage());
            session()->flash('error', $e->getMessage());
        } finally {
            $this->cargando = false;
            \Log::info('Finalizando cargarReporte');
        }
    }

    public function exportarExcel()
    {
        if (count($this->pagos) == 0) {
            session()->flash('error', 'No hay datos para exportar.');
            return;
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Resumen de Pagos');

            // === ENCABEZADO PRINCIPAL ===
            $sheet->setCellValue('A1', 'RESUMEN DE PAGOS POR PERÍODO');
            $sheet->mergeCells('A1:G1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '2E86AB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FA']]
            ]);

            // === INFORMACIÓN DEL REPORTE ===
            $sheet->setCellValue('A3', 'Período:');
            $periodoTexto = ($this->fecha_inicio ? \Carbon\Carbon::createFromFormat('Y-m-d', $this->fecha_inicio)->format('d/m/Y') : 'N/A') .
                           ' al ' .
                           ($this->fecha_fin ? \Carbon\Carbon::createFromFormat('Y-m-d', $this->fecha_fin)->format('d/m/Y') : 'N/A');
            $sheet->setCellValue('B3', $periodoTexto);

            $sheet->setCellValue('A4', 'Fecha de generación:');
            $sheet->setCellValue('B4', now()->format('d/m/Y H:i:s'));

            $sheet->setCellValue('A5', 'Total de pagos:');
            $sheet->setCellValue('B5', count($this->pagos));

            $sheet->setCellValue('D3', 'Total ingresos:');
            $sheet->setCellValue('E3', '$' . number_format($this->pagos->sum('total'), 2));

            $sheet->setCellValue('D4', 'Conceptos únicos:');
            $sheet->setCellValue('E4', $this->totales->count());

            // Formato información del reporte
            $sheet->getStyle('A3:A5')->getFont()->setBold(true);
            $sheet->getStyle('D3:D4')->getFont()->setBold(true);
            $sheet->getStyle('E3')->getFont()->setBold(true)->getColor()->setRGB('28A745');

            // === RESUMEN POR CONCEPTOS ===
            $sheet->setCellValue('A7', 'RESUMEN POR CONCEPTOS');
            $sheet->mergeCells('A7:E7');
            $sheet->getStyle('A7')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E9ECEF']]
            ]);

            // Encabezados resumen
            $headers = ['Concepto', 'Cantidad', 'Total', 'Porcentaje', 'Promedio'];
            foreach ($headers as $index => $header) {
                $column = chr(65 + $index); // A, B, C, D, E
                $sheet->setCellValue($column . '9', $header);
            }

            $sheet->getStyle('A9:E9')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '495057']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // Datos del resumen
            $totalGeneral = $this->totales->sum('total');
            $row = 10;
            foreach ($this->totales as $total) {
                $sheet->setCellValue('A' . $row, $total->concepto);
                $sheet->setCellValue('B' . $row, $total->cantidad);
                $sheet->setCellValue('C' . $row, $total->total);
                $sheet->setCellValue('D' . $row, $totalGeneral > 0 ? ($total->total / $totalGeneral) : 0);
                $sheet->setCellValue('E' . $row, $total->cantidad > 0 ? ($total->total / $total->cantidad) : 0);
                $row++;
            }

            // Total del resumen
            $sheet->setCellValue('A' . $row, 'TOTAL GENERAL');
            $sheet->setCellValue('B' . $row, $this->totales->sum('cantidad'));
            $sheet->setCellValue('C' . $row, $totalGeneral);
            $sheet->setCellValue('D' . $row, 1); // 100%
            $sheet->setCellValue('E' . $row, $this->totales->sum('cantidad') > 0 ? ($totalGeneral / $this->totales->sum('cantidad')) : 0);

            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8F9FA']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
            ]);

            // Formato del resumen
            $rangeResumen = 'A10:E' . $row;
            $sheet->getStyle($rangeResumen)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('C10:C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('D10:D' . $row)->getNumberFormat()->setFormatCode('0.00%');
            $sheet->getStyle('E10:E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

            // === DETALLE DE PAGOS ===
            $startDetailRow = $row + 3;
            $sheet->setCellValue('A' . ($startDetailRow - 1), 'DETALLE DE PAGOS');
            $sheet->mergeCells('A' . ($startDetailRow - 1) . ':G' . ($startDetailRow - 1));
            $sheet->getStyle('A' . ($startDetailRow - 1))->applyFromArray([
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E9ECEF']]
            ]);

            // Encabezados detalle
            $detailHeaders = ['Fecha', 'Estudiante', 'Documento', 'Programa', 'Concepto', 'Monto', 'Método', 'Estado'];
            foreach ($detailHeaders as $index => $header) {
                $column = chr(65 + $index);
                $sheet->setCellValue($column . ($startDetailRow + 1), $header);
            }

            $sheet->getStyle('A' . ($startDetailRow + 1) . ':H' . ($startDetailRow + 1))->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '495057']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // Datos del detalle
            $detailRow = $startDetailRow + 2;
            foreach ($this->pagos as $pago) {
                $conceptos = $pago->detalles->pluck('conceptoPago.nombre')->filter()->implode(', ');
                $estudiante = ($pago->matricula?->student?->nombres ?? '') . ' ' . ($pago->matricula?->student?->apellidos ?? '');

                $sheet->setCellValue('A' . $detailRow, $pago->fecha->format('d/m/Y'));
                $sheet->setCellValue('B' . $detailRow, trim($estudiante) ?: 'N/A');
                $sheet->setCellValue('C' . $detailRow, $pago->matricula?->student?->documento_identidad ?? 'N/A');
                $sheet->setCellValue('D' . $detailRow, $pago->matricula?->programa?->nombre ?? 'N/A');
                $sheet->setCellValue('E' . $detailRow, $conceptos ?: 'N/A');
                $sheet->setCellValue('F' . $detailRow, $pago->total);
                $sheet->setCellValue('G' . $detailRow, ucfirst($pago->metodo_pago ?? 'N/A'));
                $sheet->setCellValue('H' . $detailRow, ucfirst($pago->estado ?? 'N/A'));
                $detailRow++;
            }

            // Formato del detalle
            $rangeDetail = 'A' . ($startDetailRow + 2) . ':H' . ($detailRow - 1);
            $sheet->getStyle($rangeDetail)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('F' . ($startDetailRow + 2) . ':F' . ($detailRow - 1))->getNumberFormat()->setFormatCode('$#,##0.00');

            // === CONFIGURACIÓN DE COLUMNAS ===
            $sheet->getColumnDimension('A')->setWidth(12);
            $sheet->getColumnDimension('B')->setWidth(35);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(25);
            $sheet->getColumnDimension('E')->setWidth(30);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(12);

            // === PIE DE PÁGINA ===
            $footerRow = $detailRow + 2;
            $sheet->setCellValue('A' . $footerRow, 'Reporte generado por Sistema de Gestión Académica');
            $sheet->mergeCells('A' . $footerRow . ':G' . $footerRow);
            $sheet->getStyle('A' . $footerRow)->applyFromArray([
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6C757D']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            $filename = 'resumen_pagos_' .
                       ($this->fecha_inicio ? \Carbon\Carbon::createFromFormat('Y-m-d', $this->fecha_inicio)->format('Y-m-d') : 'sin_fecha') .
                       '_al_' .
                       ($this->fecha_fin ? \Carbon\Carbon::createFromFormat('Y-m-d', $this->fecha_fin)->format('Y-m-d') : 'sin_fecha') .
                       '.xlsx';

            // Mensaje de éxito antes de la descarga
            session()->flash('success', 'Archivo Excel generado correctamente.');

            return new StreamedResponse(
                function () use ($spreadsheet) {
                    $writer = new Xlsx($spreadsheet);
                    $writer->save('php://output');
                },
                200,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="' . urlencode($filename) . '"',
                    'Cache-Control' => 'max-age=0',
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Error exportando Excel: ' . $e->getMessage());
            session()->flash('error', 'Error al generar el archivo Excel: ' . $e->getMessage());
            return;
        }
    }

    public function exportarPDF()
    {
        if (count($this->pagos) == 0) {
            session()->flash('error', 'No hay datos para exportar.');
            return;
        }

        session()->flash('info', 'Funcionalidad de exportación a PDF en desarrollo.');
    }

    public function render()
    {
        return view('livewire.admin.reportes.resumen-pagos')->layout($this->getLayout());
    }
}
