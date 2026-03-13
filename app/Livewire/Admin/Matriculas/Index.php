<?php

namespace App\Livewire\Admin\Matriculas;
use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Matricula;
use App\Traits\Exportable;

class Index extends Component
{
    use WithPagination, Exportable, HasDynamicLayout, HasRegionalFormatting;

    public $search = '';
    public $status = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 10]
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortBy = $field;
        $this->resetPage();
    }

    public function toggleStatus($matriculaId)
    {
        if (!auth()->user()->can('edit matriculas')) {
            session()->flash('error', 'No tienes permiso para cambiar el estado.');
            return;
        }

        $matricula = Matricula::find($matriculaId);
        if ($matricula) {
            $matricula->estado = $matricula->estado === 'activo' ? 'inactivo' : 'activo';
            $matricula->save();
        }
    }

    public function desactivarMatriculasSolventes()
    {
        if (!auth()->user()->can('edit matriculas')) {
            session()->flash('error', 'No tienes permiso para cambiar el estado de las matrículas.');
            return;
        }

        try {
            // Desactivar solo las matrículas solventes que están activas
            $matriculasAfectadas = Matricula::where('estado', 'activo')
                ->where('solvente', true)
                ->update(['estado' => 'inactivo']);

            if ($matriculasAfectadas > 0) {
                session()->flash('message', "Se han desactivado {$matriculasAfectadas} matrículas solventes exitosamente.");
            } else {
                session()->flash('info', 'No se encontraron matrículas solventes para desactivar. Solo se desactivan las que están solventes y activas.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error al desactivar matrículas solventes: ' . $e->getMessage());
        }
    }

    public function delete(Matricula $matricula)
    {
        // Verificar permiso para eliminar matrículas
        if (!auth()->user()->can('delete matriculas')) {
            session()->flash('error', 'No tienes permiso para eliminar matrículas.');
            return;
        }

        try {
            $matricula->delete();
            session()->flash('message', 'Matrícula eliminada correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar la matrícula: ' . $e->getMessage());
        }

        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->perPage = 10;
        $this->resetPage();
    }

    protected function getExportQuery()
    {
        return Matricula::with(['estudiante', 'programa', 'periodo'])
            ->when($this->search, function ($query) {
                $query->whereHas('estudiante', function ($subQuery) {
                    $subQuery->where('nombres', 'like', '%' . $this->search . '%')
                        ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                        ->orWhere('documento_identidad', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status !== '', function ($query) {
                $query->where('estado', $this->status);
            })
            ->whereIn('id', function($subquery) {
                $subquery->selectRaw('MAX(id)')
                    ->from('matriculas')
                    ->groupBy('estudiante_id');
            })
            ->orderBy($this->sortBy, $this->sortDirection);
    }

    protected function getExportHeaders(): array
    {
        return [
            'ID',
            'Estudiante',
            'Documento',
            'Programa',
            'Período',
            'Fecha Matrícula',
            'Costo',
            'Cuota Inicial',
            'Número Cuotas',
            'Estado',
            'Solvencia'
        ];
    }

    protected function formatExportRow($matricula): array
    {
        return [
            $matricula->id,
            $matricula->estudiante->nombres. ' ' . $matricula->estudiante->apellidos,
            $matricula->estudiante->documento_identidad,
            $matricula->programa->nombre ?? 'N/A',
            $matricula->periodo->name ?? 'N/A',
            format_date($matricula->fecha_matricula),
            $matricula->costo,
            $matricula->cuota_inicial,
            $matricula->numero_cuotas,
            ucfirst($matricula->estado),
            $matricula->solvente ? 'SOLVENTE' : 'PENDIENTE'
        ];
    }

    public function render()
    {
        // Obtener solo la matrícula más reciente de cada estudiante
        $matriculas = Matricula::with(['estudiante', 'programa', 'periodo', 'paymentSchedules'])
            ->when($this->search, function ($query) {
                $query->whereHas('estudiante', function ($subQuery) {
                    $subQuery->where('nombres', 'like', '%' . $this->search . '%')
                        ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                        ->orWhere('documento_identidad', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status !== '', function ($query) {
                $query->where('estado', $this->status);
            })
            ->whereIn('id', function($subquery) {
                $subquery->selectRaw('MAX(id)')
                    ->from('matriculas')
                    ->groupBy('estudiante_id');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        // Estadísticas - contar solo matrículas únicas por estudiante
        $matriculasUnicas = Matricula::whereIn('id', function($subquery) {
            $subquery->selectRaw('MAX(id)')
                ->from('matriculas')
                ->groupBy('estudiante_id');
        });
        
        $totalMatriculas = (clone $matriculasUnicas)->count();
        $matriculasActivas = (clone $matriculasUnicas)->where('estado', 'activo')->count();
        $matriculasInactivas = (clone $matriculasUnicas)->where('estado', 'inactivo')->count();
        $matriculasGraduadas = (clone $matriculasUnicas)->where('estado', 'graduado')->count();
        $ingresosTotales = (clone $matriculasUnicas)->where('estado', 'activo')->sum('costo');
        
        // Calcular matrículas solventes y pendientes (solo activas) - únicas por estudiante
        $matriculasSolventes = (clone $matriculasUnicas)->where('estado', 'activo')
            ->where('solvente', true)
            ->count();
            
        $matriculasPendientes = (clone $matriculasUnicas)->where('estado', 'activo')
            ->where('solvente', false)
            ->count();

        return view('livewire.admin.matriculas.index', compact(
            'matriculas',
            'totalMatriculas',
            'matriculasActivas',
            'matriculasInactivas',
            'matriculasGraduadas',
            'ingresosTotales',
            'matriculasSolventes',
            'matriculasPendientes'
        ))->layout($this->getLayout());
    }
}