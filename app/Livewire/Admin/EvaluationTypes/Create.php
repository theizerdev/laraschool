<?php

namespace App\Livewire\Admin\EvaluationTypes;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\EvaluationType;

class Create extends Component
{
    use HasDynamicLayout, HasRegionalFormatting;

    public $name = '';
    public $code = '';
    public $default_weight = 0;
    public $description = '';
    public $is_active = true;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:evaluation_types,code',
            'default_weight' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'name.required' => 'El nombre es requerido.',
        'code.required' => 'El código es requerido.',
        'code.unique' => 'Este código ya existe.',
    ];

    public function save()
    {
        if (!auth()->user()->can('create evaluation types')) {
            session()->flash('error', 'No tienes permiso para crear tipos de evaluación.');
            return;
        }

        $this->validate();

        try {
            EvaluationType::create([
                'empresa_id' => auth()->user()->empresa_id,
                'sucursal_id' => auth()->user()->sucursal_id,
                'name' => $this->name,
                'code' => strtoupper($this->code),
                'default_weight' => $this->default_weight,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);

            session()->flash('message', 'Tipo de evaluación creado correctamente.');
            return redirect()->route('admin.evaluation-types.index');

        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.evaluation-types.create')
            ->layout($this->getLayout());
    }

    protected function getPageTitle(): string
    {
        return 'Crear Tipo de Evaluación';
    }

    protected function getBreadcrumb(): array
    {
        return [
            'admin.dashboard' => 'Dashboard',
            'admin.evaluation-types.index' => 'Tipos de Evaluación',
            'admin.evaluation-types.create' => 'Crear'
        ];
    }
}
