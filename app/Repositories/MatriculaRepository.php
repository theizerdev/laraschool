<?php

namespace App\Repositories;

use App\Repositories\Contracts\MatriculaRepositoryInterface;
use App\Models\Matricula;
use App\Models\PaymentSchedule;

class MatriculaRepository extends BaseRepository implements MatriculaRepositoryInterface
{
    public function __construct(Matricula $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener matrículas con sus relaciones
     */
    public function getAllWithRelationships()
    {
        return $this->model->with(['estudiante', 'programa', 'periodo'])->get();
    }

    /**
     * Obtener matrículas por estudiante
     */
    public function getByEstudiante($estudianteId)
    {
        return $this->model->where('estudiante_id', $estudianteId)
            ->with(['estudiante', 'programa', 'periodo'])
            ->get();
    }

    /**
     * Obtener matrículas por programa
     */
    public function getByPrograma($programaId)
    {
        return $this->model->where('programa_id', $programaId)
            ->with(['estudiante', 'programa', 'periodo'])
            ->get();
    }

    /**
     * Obtener matrículas solventes
     */
    public function getSolventes()
    {
        return $this->model->where('solvente', true)
            ->with(['estudiante', 'programa', 'periodo'])
            ->get();
    }

    /**
     * Obtener matrículas no solventes (morosas)
     */
    public function getMorosas()
    {
        return $this->model->where('solvente', false)
            ->with(['estudiante', 'programa', 'periodo'])
            ->get();
    }

    /**
     * Crear matrícula con cronograma de pagos
     */
    public function createWithPaymentSchedule(array $data, array $paymentSchedule)
    {
        $matricula = $this->create($data);
        
        foreach ($paymentSchedule as $schedule) {
            $schedule['matricula_id'] = $matricula->id;
            $schedule['empresa_id'] = auth()->user()->empresa_id;
            $schedule['sucursal_id'] = auth()->user()->sucursal_id;
            
            PaymentSchedule::create($schedule);
        }
        
        return $matricula;
    }

    /**
     * Actualizar estado de solvencia
     */
    public function updateSolvencyStatus($matriculaId)
    {
        $matricula = $this->find($matriculaId);
        $matricula->syncPaymentSchedules(); // Asumiendo que este método existe en el modelo
        return $matricula;
    }
}