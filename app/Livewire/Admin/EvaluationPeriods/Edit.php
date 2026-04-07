<?php

namespace App\Livewire\Admin\EvaluationPeriods;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\EvaluationPeriod;
use App\Models\SchoolPeriod;

class Edit extends Component
{
    use HasDynamicLayout, HasRegionalFormatting;

    public EvaluationPeriod $period;

    public $school_period_id = '';
    public $name = '';
    public $number = 1;
    public $start_date = '';
    public $end_date = '';
    public $weight = 100;
    public $description = '';
    public $is_active = true;
    public $is_closed = false;

    public function mount(EvaluationPeriod $evaluationPeriod)
    {
        $this->period = $evaluationPeriod;
        $this->school_period_id = $evaluationPeriod->school_period_id;
        $this->name = $evaluationPeriod->name;
        $this->number = $evaluationPeriod->number;
        $this->start_date = $evaluationPeriod->start_date->format('Y-m-d');
        $this->end_date = $evaluationPeriod->end_date->format('Y-m-d');
        $this->weight = $evaluationPeriod->weight;
        $this->description = $evaluationPeriod->description;
        $this->is_active = $evaluationPeriod->is_active;
        $this->is_closed = $evaluationPeriod->is_closed;
    }

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
            'is_closed' => 'boolean',
        ];
    }

    public function save()
    {
        if (!auth()->user()->can('edit evaluation periods')) {
            session()->flash('error', 'No tienes permiso para editar lapsos.');
            return;
        }

        $this->validate();

        try {
            $this->period->update([
                'school_period_id' => $this->school_period_id,
                'name' => $this->name,
                'number' => $this->number,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'weight' => $this->weight,
                'description' => $this->description,
                'is_active' => $this->is_active,
                'is_closed' => $this->is_closed,
            ]);

            session()->flash('message', 'Lapso actualizado correctamente.');
            return redirect()->route('admin.evaluation-periods.index');

        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar el lapso: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $schoolPeriods = SchoolPeriod::orderBy('name', 'desc')->get();

        return view('livewire.admin.evaluation-periods.edit', compact('schoolPeriods'))
            ->layout($this->getLayout());
    }

    protected function getPageTitle(): string
    {
        return 'Editar Lapso de Evaluación';
    }

    protected function getBreadcrumb(): array
    {
        return [
            'admin.dashboard' => 'Dashboard',
            'admin.evaluation-periods.index' => 'Lapsos de Evaluación',
            'admin.evaluation-periods.edit' => 'Editar'
        ];
    }
}
