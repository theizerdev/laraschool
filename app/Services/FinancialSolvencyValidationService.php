<?php

namespace App\Services;

use App\Models\LatePaymentRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialSolvencyValidationService
{
    public function validateForEnrollment(int $studentId, int $targetPeriodoId, int $empresaId, int $sucursalId): array
    {
        $startedAt = microtime(true);

        $periodo = DB::table('school_periods')
            ->where('id', $targetPeriodoId)
            ->first();

        if (!$periodo) {
            return [
                'ok' => false,
                'solvente' => false,
                'total_adeudado' => 0,
                'detalles' => [
                    [
                        'concepto' => 'Período escolar',
                        'monto' => 0,
                        'detalle' => 'El período seleccionado no existe.',
                        'tipo' => 'configuracion',
                    ],
                ],
                'duracion_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ];
        }

        $targetStart = Carbon::parse($periodo->start_date);

        $lateRule = LatePaymentRule::query()
            ->where('activo', true)
            ->where('empresa_id', $empresaId)
            ->where('sucursal_id', $sucursalId)
            ->first();

        $pendingSchedules = DB::table('payment_schedules as ps')
            ->join('matriculas as m', 'm.id', '=', 'ps.matricula_id')
            ->join('school_periods as sp', 'sp.id', '=', 'm.periodo_id')
            ->where('m.estudiante_id', $studentId)
            ->where('m.empresa_id', $empresaId)
            ->where('m.sucursal_id', $sucursalId)
            ->where('m.periodo_id', '!=', $targetPeriodoId)
            ->where('sp.start_date', '<', $targetStart->toDateString())
            ->whereIn('ps.estado', ['pendiente', 'vencido'])
            ->select([
                'ps.id',
                'ps.numero_cuota',
                'ps.monto',
                'ps.monto_pagado',
                'ps.fecha_vencimiento',
                'm.id as matricula_id',
                'sp.name as periodo_name',
            ])
            ->orderBy('sp.start_date')
            ->orderBy('ps.numero_cuota')
            ->get();

        $studentMatriculaIds = DB::table('matriculas')
            ->where('estudiante_id', $studentId)
            ->pluck('id');

        $pendingPayments = collect();
        if ($studentMatriculaIds->isNotEmpty()) {
            $pendingPayments = DB::table('pagos as p')
                ->whereIn('p.matricula_id', $studentMatriculaIds->all())
                ->where('p.empresa_id', $empresaId)
                ->where('p.sucursal_id', $sucursalId)
                ->where('p.estado', 'pendiente')
                ->select(['p.id', 'p.total', 'p.fecha', 'p.matricula_id'])
                ->orderByDesc('p.created_at')
                ->get();
        }

        $pendingPaymentDetails = [];
        if ($pendingPayments->isNotEmpty()) {
            $pendingPaymentIds = $pendingPayments->pluck('id')->all();

            $pendingPaymentDetails = DB::table('pago_detalles as pd')
                ->join('conceptos_pago as cp', 'cp.id', '=', 'pd.concepto_pago_id')
                ->whereIn('pd.pago_id', $pendingPaymentIds)
                ->select([
                    'pd.pago_id',
                    'cp.nombre as concepto_nombre',
                    'pd.descripcion',
                    'pd.subtotal',
                ])
                ->get()
                ->groupBy('pago_id')
                ->toArray();
        }

        $blacklistEntry = DB::table('student_financial_blacklists')
            ->where('student_id', $studentId)
            ->where('activo', true)
            ->where('empresa_id', $empresaId)
            ->where('sucursal_id', $sucursalId)
            ->first();

        $detalles = [];
        $totalAdeudado = 0.0;

        foreach ($pendingSchedules as $schedule) {
            $monto = (float) $schedule->monto;
            $montoPagado = (float) ($schedule->monto_pagado ?? 0);
            $saldo = max(0, $monto - $montoPagado);

            if ($saldo <= 0) {
                continue;
            }

            $recargo = 0.0;
            $fechaVenc = $schedule->fecha_vencimiento ? Carbon::parse($schedule->fecha_vencimiento) : null;
            if ($fechaVenc && $fechaVenc->lt(now()) && $lateRule) {
                $diasVencido = $fechaVenc->diffInDays(now());
                if ($diasVencido > (int) $lateRule->dias_gracia) {
                    $recargo = match ($lateRule->tipo) {
                        'porcentaje' => $saldo * ((float) $lateRule->valor / 100),
                        'monto_fijo' => (float) $lateRule->valor,
                        default => 0.0,
                    };
                }
            }

            $montoConRecargo = round($saldo + $recargo, 2);
            $totalAdeudado += $montoConRecargo;

            $etiquetaCuota = (int) $schedule->numero_cuota === 0 ? 'Matrícula/Inscripción' : ('Pensión - Cuota ' . (int) $schedule->numero_cuota);
            $detalleExtra = $fechaVenc ? ('Vence: ' . $fechaVenc->format('d/m/Y')) : 'Sin fecha de vencimiento';

            $detalles[] = [
                'concepto' => $etiquetaCuota,
                'monto' => $montoConRecargo,
                'detalle' => 'Período: ' . ($schedule->periodo_name ?? 'N/A') . ' • ' . $detalleExtra,
                'tipo' => 'cronograma',
            ];
        }

        if ($pendingPayments->isNotEmpty()) {
            $totalPendienteVerificacion = (float) $pendingPayments->sum('total');
            $totalAdeudado += $totalPendienteVerificacion;

            foreach ($pendingPayments as $pago) {
                $items = $pendingPaymentDetails[$pago->id] ?? [];
                $conceptos = collect($items)
                    ->pluck('concepto_nombre')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $detalleConceptos = count($conceptos) > 0
                    ? ('Conceptos: ' . implode(', ', $conceptos))
                    : 'Conceptos: N/D';

                $detalles[] = [
                    'concepto' => 'Pago en proceso de verificación',
                    'monto' => (float) $pago->total,
                    'detalle' => $detalleConceptos,
                    'tipo' => 'verificacion',
                ];
            }
        }

        if ($blacklistEntry) {
            $detalles[] = [
                'concepto' => 'Lista negra de deudores',
                'monto' => 0,
                'detalle' => (string) ($blacklistEntry->motivo ?? 'Registrado como deudor.'),
                'tipo' => 'lista_negra',
            ];
        }

        $solvente = $totalAdeudado <= 0 && !$blacklistEntry;

        return [
            'ok' => true,
            'solvente' => $solvente,
            'total_adeudado' => round($totalAdeudado, 2),
            'detalles' => $detalles,
            'duracion_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }
}
