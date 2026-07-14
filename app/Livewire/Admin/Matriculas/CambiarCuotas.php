<?php

namespace App\Livewire\Admin\Matriculas;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PaymentSchedule;
use App\Models\Matricula;

class CambiarCuotas extends Component
{
    use HasDynamicLayout, HasRegionalFormatting;
    use WithPagination;

    public $search = '';
    public $nuevo_monto = '';
    public $porcentaje_ajuste = '';
    public $tipo_ajuste = 'monto'; // 'monto' o 'porcentaje'
    public $aplicar_a = 'seleccionadas'; // 'seleccionadas', 'todas_pendientes', 'por_curso'
    public $curso_id = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $selectedSchedules = [];
    public $showModal = false;
    public $showPreview = false;
    public $previewData = [];
    public $expandedMatriculas = [];

    protected $rules = [
        'nuevo_monto' => 'required_if:tipo_ajuste,monto|numeric|min:0.01',
        'porcentaje_ajuste' => 'required_if:tipo_ajuste,porcentaje|numeric|min:-50|max:200'
    ];

    public function mount()
    {
        abort_unless(auth()->user()->can('cambiar cuotas matriculas'), 403);
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->expandedMatriculas = [];
    }

    public function selectSchedule($scheduleId)
    {
        if (in_array($scheduleId, $this->selectedSchedules)) {
            $this->selectedSchedules = array_diff($this->selectedSchedules, [$scheduleId]);
        } else {
            $this->selectedSchedules[] = $scheduleId;
        }
    }

    public function selectAll()
    {
        $matriculas = $this->getMatriculasQuery()->get();
        $schedules = [];
        foreach ($matriculas as $matricula) {
            $schedules = array_merge($schedules, $matricula->paymentSchedules->pluck('id')->toArray());
        }
        $this->selectedSchedules = array_unique($schedules);
    }

    public function deselectAll()
    {
        $this->selectedSchedules = [];
    }

    public function toggleExpand($matriculaId)
    {
        if (in_array($matriculaId, $this->expandedMatriculas)) {
            $this->expandedMatriculas = array_diff($this->expandedMatriculas, [$matriculaId]);
        } else {
            $this->expandedMatriculas[] = $matriculaId;
        }
    }

    public function selectMatriculaSchedules($matriculaId)
    {
        $matricula = Matricula::with(['paymentSchedules' => function($q) {
            $q->where('numero_cuota', '>', 0)
              ->whereIn('estado', ['pendiente', 'vencido']);
        }])->find($matriculaId);

        if ($matricula) {
            $scheduleIds = $matricula->paymentSchedules->pluck('id')->toArray();
            
            // Check if all of them are already selected
            $allSelected = count(array_intersect($scheduleIds, $this->selectedSchedules)) === count($scheduleIds);

            if ($allSelected) {
                // Deselect all for this matricula
                $this->selectedSchedules = array_diff($this->selectedSchedules, $scheduleIds);
            } else {
                // Select all for this matricula
                $this->selectedSchedules = array_unique(array_merge($this->selectedSchedules, $scheduleIds));
            }
        }
    }

    public function openModal()
    {
        if (empty($this->selectedSchedules)) {
            session()->flash('error', 'Seleccione al menos una cuota para cambiar el monto.');
            return;
        }
        $this->showModal = true;
    }

    public function previewChanges()
    {
        $this->validate();

        $schedules = $this->getSchedulesToUpdate();
        $this->previewData = [];

        foreach ($schedules as $schedule) {
            $montoOriginal = $schedule->monto;
            $montoNuevo = $this->tipo_ajuste === 'monto'
                ? $this->nuevo_monto
                : $montoOriginal * (1 + ($this->porcentaje_ajuste / 100));

            $this->previewData[] = [
                'id' => $schedule->id,
                'estudiante' => $schedule->matricula->student->nombres . ' ' . $schedule->matricula->student->apellidos,
                'cuota' => $schedule->numero_cuota,
                'monto_original' => $montoOriginal,
                'monto_nuevo' => round($montoNuevo, 2),
                'diferencia' => round($montoNuevo - $montoOriginal, 2)
            ];
        }

        $this->showPreview = true;
    }

    public function aplicarCambios()
    {
        try {
            $updated = 0;

            foreach ($this->previewData as $item) {
                PaymentSchedule::where('id', $item['id'])
                    ->update(['monto' => $item['monto_nuevo']]);
                $updated++;
            }

            session()->flash('message', "Se actualizaron {$updated} cuotas correctamente.");
            $this->reset(['selectedSchedules', 'nuevo_monto', 'porcentaje_ajuste', 'showModal', 'showPreview', 'previewData']);
        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar las cuotas: ' . $e->getMessage());
        }
    }

    private function getSchedulesToUpdate()
    {
        $query = PaymentSchedule::with(['matricula.student'])
            ->where('numero_cuota', '>', 0)
            ->whereIn('estado', ['pendiente', 'vencido']);

        if (!auth()->user()->hasRole('Super Administrador')) {
            $query->where('empresa_id', auth()->user()->empresa_id)
                  ->where('sucursal_id', auth()->user()->sucursal_id);
        }

        switch ($this->aplicar_a) {
            case 'seleccionadas':
                $query->whereIn('id', $this->selectedSchedules);
                break;
            case 'todas_pendientes':
                if ($this->fecha_desde) {
                    $query->where('fecha_vencimiento', '>=', $this->fecha_desde);
                }
                if ($this->fecha_hasta) {
                    $query->where('fecha_vencimiento', '<=', $this->fecha_hasta);
                }
                break;
            case 'por_curso':
                if ($this->curso_id) {
                    $query->whereHas('matricula', function($q) {
                        $q->where('programa_id', $this->curso_id);
                    });
                }
                break;
        }

        return $query->get();
    }

    private function getMatriculasQuery()
    {
        $query = Matricula::with(['estudiante', 'programa', 'paymentSchedules' => function($q) {
            $q->where('numero_cuota', '>', 0)
              ->whereIn('estado', ['pendiente', 'vencido'])
              ->orderBy('numero_cuota');
        }])
        ->whereHas('estudiante')
        ->whereHas('paymentSchedules', function($q) {
            $q->where('numero_cuota', '>', 0)
              ->whereIn('estado', ['pendiente', 'vencido']);
        });

        if (!auth()->user()->hasRole('Super Administrador')) {
            $query->where('empresa_id', auth()->user()->empresa_id)
                  ->where('sucursal_id', auth()->user()->sucursal_id);
        }

        if ($this->search) {
            $query->whereHas('estudiante', function($q) {
                $q->where('nombres', 'like', '%' . $this->search . '%')
                  ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                  ->orWhere('codigo', 'like', '%' . $this->search . '%');
            });
        }

        return $query;
    }

    public function render()
    {
        $matriculas = $this->getMatriculasQuery()->paginate(10);
        $cursos = \App\Models\Programa::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('livewire.admin.matriculas.cambiar-cuotas', [
            'matriculas' => $matriculas,
            'cursos' => $cursos
        ])->layout($this->getLayout());
    }
}
