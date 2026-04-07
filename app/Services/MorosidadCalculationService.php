<?php

namespace App\Services;

use App\Models\Matricula;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class MorosidadCalculationService
{
    /**
     * Calcular datos de morosidad para un conjunto de matrículas
     */
    public function calculateMorosidadData(Collection $matriculas, $fechaDesde = null, $fechaHasta = null, $fechaCorte = null)
    {
        $fechaCorte = $fechaCorte ? Carbon::parse($fechaCorte) : now();
        $fechaDesde = $fechaDesde ? Carbon::parse($fechaDesde) : null;
        $fechaHasta = $fechaHasta ? Carbon::parse($fechaHasta) : null;

        $results = [];
        foreach ($matriculas as $matricula) {
            // Verificar que la matrícula tenga estudiante asociado
            if (!$matricula->estudiante) {
                continue; // Saltar matrículas sin estudiante
            }

            // Obtener cuotas en el rango de fechas
            $cuotasRango = $matricula->cronogramaPagos;

            // Filtrar cuotas según el rango de fechas
            if ($fechaDesde) {
                $cuotasRango = $cuotasRango->filter(function ($cuota) use ($fechaDesde) {
                    return $cuota->fecha_vencimiento->gte($fechaDesde);
                });
            }

            if ($fechaHasta) {
                $cuotasRango = $cuotasRango->filter(function ($cuota) use ($fechaHasta) {
                    return $cuota->fecha_vencimiento->lte($fechaHasta);
                });
            }

            // Filtrar cuotas pendientes de pago
            $cuotasPendientes = $cuotasRango->filter(function ($cuota) {
                return $cuota->saldo_pendiente > 0.01;
            });

            // Calcular valores para el rango de fechas
            $costoRango = $cuotasRango->sum('monto');
            $pagadoRango = $cuotasRango->sum('monto_pagado');
            $saldoPendienteRango = $cuotasRango->sum('saldo_pendiente');
            $cantidadCuotas = $cuotasRango->count(); // Total de cuotas en el rango
            $cantidadCuotasPendientes = $cuotasPendientes->count(); // Cuotas pendientes de pago

            // Calcular porcentaje pagado en el rango
            $porcentajePagadoRango = $costoRango > 0 ? ($pagadoRango / $costoRango) * 100 : 0;

            // Calcular estado basado en las cuotas del rango
            $estado = $this->calculateEstado($cuotasRango, $fechaCorte);

            $results[] = [
                'id' => $matricula->id,
                'estudiante_id' => $matricula->estudiante->id,
                'estudiante_nombre' => $matricula->estudiante->nombres . ' ' . $matricula->estudiante->apellidos,
                'programa_nombre' => $matricula->programa->nombre ?? '',
                'nivel_nombre' => $matricula->programa->nivelEducativo->nombre ?? '',
                'turno_nombre' => $matricula->turno->nombre ?? '',
                'costo_rango' => $costoRango,
                'pagado_rango' => $pagadoRango,
                'saldo_pendiente_rango' => $saldoPendienteRango,
                'porcentaje_pagado_rango' => round($porcentajePagadoRango, 2),
                'estado' => $estado,
                'fecha_matricula' => $matricula->fecha_matricula->format('d/m/Y'),
                'monto_vencido' => $this->calculateMontoVencido($cuotasRango, $fechaCorte),
                'cantidad_cuotas' => $cantidadCuotasPendientes, // Mostrar solo cuotas pendientes
                'matricula' => $matricula
            ];
        }

        return $results;
    }

    /**
     * Calcular el estado de la matrícula basado en las cuotas
     */
    private function calculateEstado(Collection $cuotasRango, Carbon $fechaCorte)
    {
        $saldoPendienteRango = $cuotasRango->sum('saldo_pendiente');

        if ($saldoPendienteRango <= 0.01) {
            return 'Al día';
        }

        // Verificar si hay cuotas vencidas
        $cuotasVencidas = $cuotasRango->filter(function ($cuota) use ($fechaCorte) {
            return $cuota->fecha_vencimiento->lt($fechaCorte) && $cuota->saldo_pendiente > 0.01;
        });

        return $cuotasVencidas->count() > 0 ? 'Moroso' : 'Pendiente';
    }

    /**
     * Calcular el monto vencido
     */
    private function calculateMontoVencido(Collection $cuotasRango, Carbon $fechaCorte)
    {
        $montoVencido = 0;

        foreach ($cuotasRango as $cuota) {
            if ($cuota->fecha_vencimiento->lt($fechaCorte) && $cuota->saldo_pendiente > 0.01) {
                $montoVencido += $cuota->saldo_pendiente;
            }
        }

        return $montoVencido;
    }

    /**
     * Calcular totales consolidados
     */
    public function calculateTotals($morososData)
    {
        $totalEstudiantes = count($morososData);
        $totalMorosos = collect($morososData)->filter(function ($item) {
            return $item['estado'] === 'Moroso';
        })->count();
        
        $porcentajeMorosidad = $totalEstudiantes > 0 ? ($totalMorosos / $totalEstudiantes) * 100 : 0;

        return [
            'total_estudiantes' => $totalEstudiantes,
            'total_morosos' => $totalMorosos,
            'porcentaje_morosidad' => round($porcentajeMorosidad, 2)
        ];
    }
}