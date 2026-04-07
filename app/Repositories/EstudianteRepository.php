<?php

namespace App\Repositories;

use App\Repositories\Contracts\EstudianteRepositoryInterface;
use App\Models\Student;

class EstudianteRepository extends BaseRepository implements EstudianteRepositoryInterface
{
    public function __construct(Student $model)
    {
        parent::__construct($model);
    }

    /**
     * Obtener estudiantes con sus relaciones
     */
    public function getAllWithRelationships()
    {
        return $this->model->with(['nivelEducativo'])->get();
    }

    /**
     * Buscar estudiantes por nombre o documento
     */
    public function search($searchTerm)
    {
        return $this->model->where(function($query) use ($searchTerm) {
            $query->where('nombres', 'like', '%' . $searchTerm . '%')
                  ->orWhere('apellidos', 'like', '%' . $searchTerm . '%')
                  ->orWhere('documento_identidad', 'like', '%' . $searchTerm . '%');
        })
        ->with('nivelEducativo')
        ->orderBy('nombres')
        ->orderBy('apellidos')
        ->get();
    }

    /**
     * Obtener estudiantes por nivel educativo
     */
    public function getByNivelEducativo($nivelEducativoId)
    {
        return $this->model->where('nivel_educativo_id', $nivelEducativoId)
            ->with(['nivelEducativo'])
            ->get();
    }

    /**
     * Obtener estudiantes matriculados
     */
    public function getMatriculados()
    {
        return $this->model->whereHas('matriculas', function($query) {
            $query->where('estado', 'activo');
        })
        ->with(['nivelEducativo', 'matriculas'])
        ->get();
    }

    /**
     * Obtener estudiantes no matriculados
     */
    public function getNoMatriculados()
    {
        return $this->model->whereDoesntHave('matriculas', function($query) {
            $query->where('estado', 'activo');
        })
        ->with(['nivelEducativo'])
        ->get();
    }
}