<?php

namespace App\Services;

use App\Repositories\MatriculaRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\CronogramaPagoRepository;
use App\Models\Matricula;
use App\Models\PaymentSchedule;
use Carbon\Carbon;

class MatriculaService
{
    protected $matriculaRepository;
    protected $estudianteRepository;
    protected $cronogramaPagoRepository;

    public function __construct(
        MatriculaRepository $matriculaRepository,
        EstudianteRepository $estudianteRepository,
        CronogramaPagoRepository $cronogramaPagoRepository
    ) {
        $this->matriculaRepository = $matriculaRepository;
        $this->estudianteRepository = $estudianteRepository;
        $this->cronogramaPagoRepository = $cronogramaPagoRepository;
    }

    /**
     * Generar cronograma de pagos para una matrícula
     */
    public function generarCronogramaPagos(
        $matriculaId, 
        $costo, 
        $cuotaInicial, 
        $numeroCuotas, 
        $fechaMatricula, 
        $periodoId
    ) {
        $matricula = $this->matriculaRepository->find($matriculaId);
        
        // Calcular monto restante después de la cuota inicial
        $montoRestante = $costo - $cuotaInicial;

        if ($numeroCuotas <= 0) {
            // Si no hay cuotas, todo se cobra en la cuota inicial
            return [
                [
                    'numero_cuota' => 1,
                    'descripcion' => 'Pago único',
                    'monto' => $costo,
                    'fecha_vencimiento' => $fechaMatricula
                ]
            ];
        }

        // Calcular monto por cuota
        $montoCuota = $montoRestante / $numeroCuotas;

        // Obtener el período escolar
        $periodo = $matricula->periodo; // Asumiendo que existe la relación
        
        // Array para almacenar el cronograma
        $cronograma = [];

        // Agregar cuota inicial si existe
        if ($cuotaInicial > 0) {
            $cronograma[] = [
                'numero_cuota' => 0,
                'descripcion' => 'Cuota inicial',
                'monto' => $cuotaInicial,
                'fecha_vencimiento' => $fechaMatricula
            ];
        }

        // Generar cuotas mensuales a partir de la fecha de matrícula
        $currentDate = new Carbon($fechaMatricula);

        for ($i = 1; $i <= $numeroCuotas; $i++) {
            // Para la primera cuota real, usamos la fecha de matrícula
            if ($i == 1) {
                $dueDate = new Carbon($fechaMatricula);
            } else {
                // Para las demás cuotas, sumamos meses a la fecha base
                $dueDate = new Carbon($fechaMatricula);
                $dueDate->addMonths($i - 1);
                
                // Asegurarnos de que la fecha no exceda la fecha final del período
                $endDate = new Carbon($periodo->end_date);
                if ($dueDate->greaterThan($endDate)) {
                    $dueDate = $endDate;
                }
            }

            $cronograma[] = [
                'numero_cuota' => $i,
                'descripcion' => 'Cuota ' . $i,
                'monto' => round($montoCuota, 2),
                'fecha_vencimiento' => $dueDate->format('Y-m-d')
            ];
        }

        return $cronograma;
    }

    /**
     * Crear matrícula con cronograma de pagos
     */
    public function crearMatriculaConCronograma(array $datosMatricula, array $cronograma)
    {
        // Crear la matrícula
        $matricula = $this->matriculaRepository->create($datosMatricula);

        // Crear el cronograma de pagos
        foreach ($cronograma as $item) {
            $item['matricula_id'] = $matricula->id;
            $item['empresa_id'] = auth()->user()->empresa_id;
            $item['sucursal_id'] = auth()->user()->sucursal_id;
            $item['estado'] = 'pendiente';
            $item['monto_pagado'] = 0;
            $item['saldo_pendiente'] = $item['monto'];
            
            PaymentSchedule::create($item);
        }

        // Actualizar estado de solvencia
        $matricula->syncPaymentSchedules();

        return $matricula;
    }

    /**
     * Calcular estado de morosidad de una matrícula
     */
    public function calcularEstadoMorosidad($matriculaId)
    {
        $matricula = $this->matriculaRepository->find($matriculaId);
        
        // Obtener todas las cuotas de la matrícula
        $cuotas = $this->cronogramaPagoRepository->getByMatricula($matriculaId);
        
        $fechaActual = Carbon::today();
        $tieneCuotasVencidas = false;
        $tieneSaldoPendiente = false;
        
        foreach ($cuotas as $cuota) {
            // Verificar si la cuota está vencida y tiene saldo pendiente
            if (
                $cuota->fecha_vencimiento < $fechaActual && 
                $cuota->saldo_pendiente > 0.01
            ) {
                $tieneCuotasVencidas = true;
                break;
            }
        }
        
        // Verificar si hay algún saldo pendiente en total
        $totalPendiente = $this->cronogramaPagoRepository->getTotalPendienteByMatricula($matriculaId);
        $tieneSaldoPendiente = $totalPendiente > 0.01;
        
        if ($tieneCuotasVencidas) {
            return 'moroso';
        } elseif ($tieneSaldoPendiente) {
            return 'pendiente';
        } else {
            return 'al_dia';
        }
    }

    /**
     * Actualizar solvencia de todas las matrículas
     */
    public function actualizarSolvenciaMasiva()
    {
        $matriculas = $this->matriculaRepository->all();
        
        foreach ($matriculas as $matricula) {
            $matricula->syncPaymentSchedules();
        }
    }
}