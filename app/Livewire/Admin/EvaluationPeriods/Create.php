<?php

namespace App\Livewire\Admin\EvaluationPeriods;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\EvaluationPeriod;
use App\Models\SchoolPeriod;

class Create extends Component
{
    use HasDynamicLayout, HasRegionalFormatting;

    public $school_period_id = '';
    public $name = '';
    public $number = 1;
    public $start_date = '';
    public $end_date = '';
    public $weight = 100;
    public $description = '';
    public $is_active = true;

    protected function rules()
    {
        return [
            'school_period_id' => 'required|exists:school_periods,id',
            'name' => 'required|string|max:255',
            'number' => 'required|integer|min:1|max:10',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'weight' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'school_period_id.required' => 'El período escolar es requerido.',
        'name.required' => 'El nombre del lapso es requerido.',
        'number.required' => 'El número del lapso es requerido.',
        'start_date.required' => 'La fecha de inicio es requerida.',
        'end_date.required' => 'La fecha de fin es requerida.',
        'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
    ];

    public function save()
    {
        if (!auth()->user()->can('create evaluation periods')) {
            session()->flash('error', 'No tienes permiso para crear lapsos.');
            return;
        }

        $this->validate();

        try {
            EvaluationPeriod::create([
                'empresa_id' => auth()->user()->empresa_id,
                'sucursal_id' => auth()->user()->sucursal_id,
                'school_period_id' => $this->school_period_id,
                'name' => $this->name,
                'number' => $this->number,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'weight' => $this->weight,
                'description' => $this->description,
                'is_active' => $this->is_active,
                'is_closed' => false,
            ]);

            session()->flash('message', 'Lapso creado correctamente.');
            return redirect()->route('admin.evaluation-periods.index');

        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el lapso: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $schoolPeriods = SchoolPeriod::active()->orderBy('name', 'desc')->get();

        return view('livewire.admin.evaluation-periods.create', compact('schoolPeriods'))
            ->layout($this->getLayout());
    }

    protected function getPageTitle(): string
    {
        return 'Crear Lapso de Evaluación';
    }

    protected function getBreadcrumb(): array
    {
        return [
            'admin.dashboard' => 'Dashboard',
            'admin.evaluation-periods.index' => 'Lapsos de Evaluación',
            'admin.evaluation-periods.create' => 'Crear'
        ];
    }
}
