<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\ExchangeRate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CajaExportController extends Controller
{
    public function export(Caja $caja)
    {
        $caja->load(['usuario', 'sucursal', 'pagos.detalles.conceptoPago', 'pagos.matricula.student']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar encabezado
        $sheet->setCellValue('A1', 'REPORTE DETALLADO DE CAJA');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Información de la caja
        $row = 3;
        $sheet->setCellValue('A' . $row, 'Fecha:');
        $sheet->setCellValue('B' . $row, $caja->fecha->format('d/m/Y'));
        $sheet->setCellValue('D' . $row, 'Usuario:');
        $sheet->setCellValue('E' . $row, $caja->usuario->name);

        $row++;
        $sheet->setCellValue('A' . $row, 'Sucursal:');
        $sheet->setCellValue('B' . $row, $caja->sucursal->nombre);
        $sheet->setCellValue('D' . $row, 'Estado:');
        $sheet->setCellValue('E' . $row, ucfirst($caja->estado));

        // Tasa de cambio
        $tasaCambio = ExchangeRate::whereDate('created_at', $caja->fecha)->first();
        if ($tasaCambio) {
            $row++;
            $sheet->setCellValue('A' . $row, 'Tasa de Cambio:');
            $sheet->setCellValue('B' . $row, number_format($tasaCambio->usd_rate, 4) . ' Bs/$');
        }

        // Resumen financiero
        $row += 2;
        $sheet->setCellValue('A' . $row, 'RESUMEN FINANCIERO');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $pagos = $caja->pagos()->where('estado', 'aprobado')->get();
        $totalUsdPagos = (float) $pagos->sum('total');
        $totalBsPagos = (float) $pagos->sum('total_bolivares');
        $datos = [
            ['Concepto', 'Monto USD', 'Monto Bs'],
            ['Monto Inicial', number_format($caja->monto_inicial, 2), $tasaCambio ? number_format($caja->monto_inicial * $tasaCambio->usd_rate, 2) : '-'],
            ['Total Ingresos', number_format($totalUsdPagos, 2), number_format($totalBsPagos, 2)],
            ['Monto Final', number_format($totalUsdPagos, 2), number_format($totalBsPagos, 2)]
        ];

        foreach ($datos as $fila) {
            $sheet->setCellValue('A' . $row, $fila[0]);
            $sheet->setCellValue('B' . $row, $fila[1]);
            $sheet->setCellValue('C' . $row, $fila[2]);
            $row++;
        }

        // Resumen por método de pago
        $row += 2;
        $sheet->setCellValue('A' . $row, 'RESUMEN POR MÉTODO DE PAGO');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, 'Método');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $sheet->setCellValue('C' . $row, 'Total USD');
        $sheet->setCellValue('D' . $row, 'Total Bs');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $row++;
        $resumenMetodo = $caja->pagos()
            ->where('estado', 'aprobado')
            ->selectRaw('metodo_pago, COUNT(*) as cantidad, SUM(total) as total, SUM(COALESCE(total_bolivares,0)) as total_bs')
            ->groupBy('metodo_pago')
            ->get();
        foreach ($resumenMetodo as $item) {
            $sheet->setCellValue('A' . $row, ucfirst($item->metodo_pago));
            $sheet->setCellValue('B' . $row, (int) $item->cantidad);
            $sheet->setCellValue('C' . $row, number_format((float) $item->total, 2));
            $sheet->setCellValue('D' . $row, number_format((float) $item->total_bs, 2));
            $row++;
        }

        // Resumen por concepto
        $row += 2;
        $sheet->setCellValue('A' . $row, 'RESUMEN POR CONCEPTO');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, 'Concepto');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $sheet->setCellValue('C' . $row, 'Total USD');
        $sheet->setCellValue('D' . $row, 'Total Bs');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $row++;
        $resumenConcepto = $caja->pagos()->where('estado', 'aprobado')->with(['detalles.conceptoPago'])->get()
            ->flatMap(function ($pago) {
                return $pago->detalles->map(function ($detalle) use ($pago) {
                    return [
                        'concepto' => $detalle->conceptoPago->nombre ?? 'Sin concepto',
                        'cantidad' => (float) $detalle->cantidad,
                        'subtotal' => (float) $detalle->subtotal,
                        'subtotal_bs' => $pago->tasa_cambio ? ((float) $detalle->subtotal * (float) $pago->tasa_cambio) : 0,
                    ];
                });
            })
            ->groupBy('concepto')
            ->map(function ($items, $concepto) {
                return [
                    'concepto' => $concepto,
                    'cantidad' => $items->sum('cantidad'),
                    'total' => $items->sum('subtotal'),
                    'total_bs' => $items->sum('subtotal_bs'),
                ];
            });
        foreach ($resumenConcepto as $concepto) {
            $sheet->setCellValue('A' . $row, $concepto['concepto']);
            $sheet->setCellValue('B' . $row, (int) $concepto['cantidad']);
            $sheet->setCellValue('C' . $row, number_format((float) $concepto['total'], 2));
            $sheet->setCellValue('D' . $row, number_format((float) $concepto['total_bs'], 2));
            $row++;
        }

        // Detalle de pagos
        $row += 2;
        $sheet->setCellValue('A' . $row, 'DETALLE DE PAGOS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        $row++;
        $encabezados = ['Documento', 'Estudiante', 'Método', 'Monto USD', 'Monto Bs', 'Referencia', 'Fecha Pago', 'Tasa (Bs/$)', 'Hora'];
        foreach ($encabezados as $col => $encabezado) {
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $encabezado);
        }
        $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);

        $row++;
        foreach ($caja->pagos()->where('estado', 'aprobado')->get() as $pago) {
            if ($pago->es_pago_mixto && $pago->detalles_pago_mixto) {
                foreach ($pago->detalles_pago_mixto as $detalle) {
                    $montoBolivares = $pago->tasa_cambio
                        ? number_format($detalle['monto'] * $pago->tasa_cambio, 2)
                        : '-';

                    $sheet->setCellValue('A' . $row, $pago->numero_completo);
                    $sheet->setCellValue('B' . $row, $pago->matricula->student->nombres . ' ' . $pago->matricula->student->apellidos);
                    $sheet->setCellValue('C' . $row, ucfirst(str_replace('_', ' ', $detalle['metodo'])));
                    $sheet->setCellValue('D' . $row, number_format($detalle['monto'], 2));
                    $sheet->setCellValue('E' . $row, $montoBolivares);
                    $sheet->setCellValue('F' . $row, $detalle['referencia'] ?: '-');
                    $sheet->setCellValue('G' . $row, $pago->fecha ? $pago->fecha->format('d/m/Y') : $pago->created_at->format('d/m/Y'));
                    $sheet->setCellValue('H' . $row, $pago->tasa_cambio ? number_format($pago->tasa_cambio, 4) : '-');
                    $sheet->setCellValue('I' . $row, $pago->created_at->format('H:i'));
                    $row++;
                }
            } else {
                $montoBolivares = $pago->total_bolivares ? number_format($pago->total_bolivares, 2) : '-';

                $sheet->setCellValue('A' . $row, $pago->numero_completo);
                $sheet->setCellValue('B' . $row, $pago->matricula->student->nombres . ' ' . $pago->matricula->student->apellidos);
                $sheet->setCellValue('C' . $row, $pago->metodo_pago);
                $sheet->setCellValue('D' . $row, number_format($pago->total, 2));
                $sheet->setCellValue('E' . $row, $montoBolivares);
                $sheet->setCellValue('F' . $row, $pago->referencia ?: '-');
                $sheet->setCellValue('G' . $row, $pago->fecha ? $pago->fecha->format('d/m/Y') : $pago->created_at->format('d/m/Y'));
                $sheet->setCellValue('H' . $row, $pago->tasa_cambio ? number_format($pago->tasa_cambio, 4) : '-');
                $sheet->setCellValue('I' . $row, $pago->created_at->format('H:i'));
                $row++;
            }
        }

        // Aplicar estilos
        $sheet->getStyle('A1:I' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(10);

        // Pie de página de auditoría
        $footerRow = $row + 2;
        $sheet->setCellValue('A' . $footerRow, 'Montos en Bs calculados según total_bolivares de cada pago');
        $sheet->mergeCells('A' . $footerRow . ':I' . $footerRow);
        $sheet->getStyle('A' . $footerRow)->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A' . $footerRow)->getFont()->getColor()->setRGB('6C757D');
        $sheet->getStyle('A' . $footerRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $filename = 'caja_' . $caja->fecha->format('Y-m-d') . '_' . $caja->numero_corte . '.xlsx';

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}
