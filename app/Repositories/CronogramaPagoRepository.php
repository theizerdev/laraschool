<?php

namespace App\Repositories;

use App\Repositories\Contracts\CronogramaPagoRepositoryInterface;
use App\Models\PaymentSchedule;
use Carbon\Carbon;

class CronogramaPagoRepository extends BaseRepository implements CronogramaPagoRepositoryInterface
{
    public function __construct(PaymentSchedule $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener cronograma de pagos por matrícula
     */
    public function getByMatricula($matriculaId)
    {
        return $this->model->where('matricula_id', $matriculaId)
            ->orderBy('numero_cuota')
            ->get();
    }

    /**
     * Obtener cronograma de pagos vencidos
     */
    public function getVencidos()
    {
        return $this->model->where('fecha_vencimiento', '<', Carbon::today())
            ->where('estado', '!=', 'pagado')
            ->with(['matricula.estudiante', 'matricula.programa'])
            ->get();
    }

    /**
     * Obtener cronograma de pagos pendientes
     */
    public function getPendientes()
    {
        return $this->model->where('fecha_vencimiento', '>=', Carbon::today())
            ->where('estado', 'pendiente')
            ->with(['matricula.estudiante', 'matricula.programa'])
            ->get();
    }

    /**
     * Obtener cronograma de pagos por rango de fechas
     */
    public function getByDateRange($fechaInicio, $fechaFin)
    {
        return $this->model->whereBetween('fecha_vencimiento', [$fechaInicio, $fechaFin])
            ->with(['matricula.estudiante', 'matricula.programa'])
            ->get();
    }

    /**
     * Actualizar estado de solvencia de un cronograma
     */
    public function updateSolvencyStatus($id, $estado)
    {
        $cronograma = $this->find($id);
        $cronograma->estado = $estado;
        $cronograma->save();
        
        // Actualizar estado de solvencia de la matrícula relacionada
        $matricula = $cronograma->matricula;
        $matricula->syncPaymentSchedules();
        
        return $cronograma;
    }

    /**
     * Calcular montos pendientes de pago
     */
    public function getTotalPendienteByMatricula($matriculaId)
    {
        return $this->model->where('matricula_id', $matriculaId)
            ->where('estado', '!=', 'pagado')
            ->sum('saldo_pendiente');
    }
}