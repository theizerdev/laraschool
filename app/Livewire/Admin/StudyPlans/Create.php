<?php

namespace App\Livewire\Admin\StudyPlans;

use App\Traits\HasDynamicLayout;
use Livewire\Component;
use App\Models\StudyPlan;
use App\Models\Programa;
use App\Models\NivelEducativo;

class Create extends Component
{
    use HasDynamicLayout;

    public $name = '';
    public $code = '';
    public $description = '';
    public $program_id = '';
    public $educational_level_id = '';
    public $total_credits = 0;
    public $total_hours = 0;
    public $duration_years = 0;
    public $duration_semesters = 0;
    public $status = 'active';
    public $effective_date = '';
    public $expiration_date = '';
    public $is_default = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:50|unique:study_plans',
        'description' => 'nullable|string|max:1000',
        'program_id' => 'required|exists:programas,id',
        'educational_level_id' => 'required|exists:niveles_educativos,id',
        'total_credits' => 'nullable|integer|min:0',
        'total_hours' => 'nullable|integer|min:0',
        'duration_years' => 'nullable|integer|min:0',
        'duration_semesters' => 'nullable|integer|min:0',
        'status' => 'required|in:active,inactive',
        'effective_date' => 'nullable|date',
        'expiration_date' => 'nullable|date|after_or_equal:effective_date',
        'is_default' => 'boolean',
    ];

    public function mount()
    {
        $this->effective_date = now()->format('Y-m-d');
    }

    public function save()
    {
        $this->validate();

        try {
            // Si este plan va a ser por defecto, desactivar otros planes por defecto del mismo programa y nivel
            if ($this->is_default) {
                StudyPlan::where('program_id', $this->program_id)
                    ->where('educational_level_id', $this->educational_level_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            StudyPlan::create([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'program_id' => $this->program_id,
                'educational_level_id' => $this->educational_level_id,
                'total_credits' => $this->total_credits,
                'total_hours' => $this->total_hours,
                'duration_years' => $this->duration_years,
                'duration_semesters' => $this->duration_semesters,
                'status' => $this->status,
                'effective_date' => $this->effective_date,
                'expiration_date' => $this->expiration_date,
                'is_default' => $this->is_default,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            session()->flash('message', 'Plan de estudio creado correctamente.');
            return redirect()->route('admin.study-plans.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el plan de estudio: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $programs = Programa::orderBy('nombre')->get();
        $educationalLevels = NivelEducativo::orderBy('nombre')->get();

        return view('livewire.admin.study-plans.create', compact('programs', 'educationalLevels'))
            ->layout($this->getLayout());
    }

    protected function getPageTitle(): string
    {
        return 'Crear Plan de Estudio';
    }

    protected function getBreadcrumb(): array
    {
        return [
            'admin.dashboard' => 'Dashboard',
            'admin.study-plans.index' => 'Planes de Estudio',
            '#' => 'Crear'
        ];
    }
}
