<?php

namespace App\Livewire\Admin\EvaluationTypes;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\EvaluationType;

class Edit extends Component
{
    use HasDynamicLayout, HasRegionalFormatting;

    public EvaluationType $type;

    public $name = '';
    public $code = '';
    public $default_weight = 0;
    public $description = '';
    public $is_active = true;

    public function mount(EvaluationType $evaluationType)
    {
        $this->type = $evaluationType;
        $this->name = $evaluationType->name;
        $this->code = $evaluationType->code;
        $this->default_weight = $evaluationType->default_weight;
        $this->description = $evaluationType->description;
        $this->is_active = $evaluationType->is_active;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:evaluation_types,code,' . $this->type->id,
            'default_weight' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function save()
    {
        if (!auth()->user()->can('edit evaluation types')) {
            session()->flash('error', 'No tienes permiso para editar tipos de evaluación.');
            return;
        }

        $this->validate();

        try {
            $this->type->update([
                'name' => $this->name,
                'code' => strtoupper($this->code),
                'default_weight' => $this->default_weight,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);

            session()->flash('message', 'Tipo de evaluación actualizado correctamente.');
            return redirect()->route('admin.evaluation-types.index');

        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.evaluation-types.edit')
            ->layout($this->getLayout());
    }

    protected function getPageTitle(): string
    {
        return 'Editar Tipo de Evaluación';
    }

    protected function getBreadcrumb(): array
    {
        return [
            'admin.dashboard' => 'Dashboard',
            'admin.evaluation-types.index' => 'Tipos de Evaluación',
            'admin.evaluation-types.edit' => 'Editar'
        ];
    }
}
