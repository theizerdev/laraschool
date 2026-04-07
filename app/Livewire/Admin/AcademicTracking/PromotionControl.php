<?php

namespace App\Livewire\Admin\AcademicTracking;

use App\Models\AcademicRecord;
use App\Models\Student;
use App\Models\SchoolPeriod;
use App\Models\Programa;
use App\Models\EducationalLevel;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use App\Traits\HasDynamicLayout;

#[Title('Control de Promoción')]
class PromotionControl extends Component
{
    use WithPagination, HasDynamicLayout;

    public $selectedPeriodId;
    public $selectedProgramId;
    public $selectedLevelId;
    public $selectedGrade;
    public $selectedSection;
    public $promotionStatus = '';
    public $search = '';
    public $showOnlyPending = true;
    public $showStatistics = true;

    protected $queryString = [
        'selectedPeriodId' => ['except' => ''],
        'selectedProgramId' => ['except' => ''],
        'selectedLevelId' => ['except' => ''],
        'selectedGrade' => ['except' => ''],
        'selectedSection' => ['except' => ''],
        'promotionStatus' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedPeriodId()
    {
        $this->resetPage();
    }

    public function updatingSelectedProgramId()
    {
        $this->resetPage();
    }

    public function updatingSelectedLevelId()
    {
        $this->resetPage();
    }

    public function getBaseQuery()
    {
        $query = AcademicRecord::with([
            'student',
            'subject',
            'schoolPeriod',
            'program',
            'educationalLevel',
            'recoveryPeriod'
        ]);

        if ($this->selectedPeriodId) {
            $query->where('school_period_id', $this->selectedPeriodId);
        }

        if ($this->selectedProgramId) {
            $query->where('program_id', $this->selectedProgramId);
        }

        if ($this->selectedLevelId) {
            $query->where('educational_level_id', $this->selectedLevelId);
        }

        if ($this->selectedGrade) {
            $query->where('grade', $this->selectedGrade);
        }

        if ($this->selectedSection) {
            $query->where('section', $this->selectedSection);
        }

        if ($this->promotionStatus) {
            switch ($this->promotionStatus) {
                case 'promoted':
                    $query->where('promoted', true);
                    break;
                case 'repeated':
                    $query->where('repeated', true);
                    break;
                case 'pending':
                    $query->where('promoted', false)->where('repeated', false);
                    break;
                case 'in_recovery':
                    $query->where('status', AcademicRecord::STATUS_IN_RECOVERY);
                    break;
            }
        }

        if ($this->showOnlyPending) {
            $query->where('promoted', false)->where('repeated', false);
        }

        if ($this->search) {
            $query->whereHas('student', function ($studentQuery) {
                $studentQuery->where('nombres', 'like', '%' . $this->search . '%')
                            ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                            ->orWhere('codigo', 'like', '%' . $this->search . '%');
            });
        }

        return $query;
    }

    public function getStudentsProperty()
    {
        $query = Student::query();

        // Aplicar filtros basados en las propiedades públicas
        if ($this->selectedPeriodId) {
            $query->whereHas('matriculas', function ($q) {
                $q->where('periodo_id', $this->selectedPeriodId);
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nombres', 'like', '%' . $this->search . '%')
                  ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                  ->orWhere('codigo', 'like', '%' . $this->search . '%');
            });
        }

        // Cargar relaciones necesarias
        $query->with([
            'academicRecords' => function ($q) {
                if ($this->selectedPeriodId) {
                    $q->where('school_period_id', $this->selectedPeriodId);
                }
            },
            'academicRecords.subject',
            'academicRecords.program',
            'academicRecords.educationalLevel'
        ]);

        $students = $query->paginate(10);

        // Calcular el estado de promoción para cada estudiante
        $students->each(function ($student) {
            $student->promotion_status = $this->calculatePromotionStatus($student);
        });

        // Filtrar por estado de promoción si se ha seleccionado uno
        if ($this->promotionStatus) {
            $students = $students->filter(function ($student) {
                return $student->promotion_status === $this->promotionStatus;
            });
        }

        return $students;
    }

    public function calculatePromotionStatus(Student $student): string
    {
        $academicRecords = $student->academicRecords;

        if ($academicRecords->isEmpty()) {
            return 'pending'; // No hay registros, estado pendiente
        }

        $totalSubjects = $academicRecords->count();
        $approvedSubjects = 0;

        foreach ($academicRecords as $record) {
            // Asumimos que una nota final >= 10 es aprobatoria.
            // Esto debería venir de una configuración del sistema.
            if ($record->final_grade !== null && $record->final_grade >= 10) {
                $approvedSubjects++;
            }
        }

        if ($approvedSubjects === $totalSubjects) {
            return 'promoted'; // Aprobó todas las materias
        }

        // Lógica simple: si no aprobó todo, se considera retenido por ahora.
        // Esto puede expandirse para incluir "en recuperación".
        return 'repeated';
    }

    public function getSchoolPeriodsProperty()
    {
        return SchoolPeriod::orderBy('name', 'desc')->get();
    }

    public function getProgramsProperty()
    {
        return Programa::orderBy('nombre')->get();
    }

    public function getEducationalLevelsProperty()
    {
        return EducationalLevel::orderBy('nombre')->get();
    }

    public function getGradesProperty()
    {
        return ['1ro', '2do', '3ro', '4to', '5to', '6to'];
    }

    public function getSectionsProperty()
    {
        return ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    }

    public function getStatsProperty()
    {
        $baseQuery = $this->getBaseQuery();

        $totalStudents = $baseQuery->distinct('student_id')->count('student_id');

        return [
            'total_students' => $totalStudents,
            'promoted_students' => (clone $baseQuery)->where('promoted', true)->distinct('student_id')->count('student_id'),
            'repeated_students' => (clone $baseQuery)->where('repeated', true)->distinct('student_id')->count('student_id'),
            'pending_students' => (clone $baseQuery)->where('promoted', false)->where('repeated', false)->distinct('student_id')->count('student_id'),
            'in_recovery_students' => (clone $baseQuery)->where('status', AcademicRecord::STATUS_IN_RECOVERY)->distinct('student_id')->count('student_id'),
            'approved_percentage' => $totalStudents > 0 ? round(((clone $baseQuery)->where('promoted', true)->distinct('student_id')->count('student_id') / $totalStudents) * 100, 2) : 0,
        ];
    }

    public function promoteStudent($studentId)
    {
        // Implementar lógica de promoción
        $this->dispatch('success', 'Estudiante promovido exitosamente');
    }

    public function repeatStudent($studentId)
    {
        // Implementar lógica de repetición
        $this->dispatch('success', 'Estudiante retenido exitosamente');
    }

    public function bulkPromote()
    {
        // Implementar promoción masiva
        $this->dispatch('success', 'Promoción masiva procesada exitosamente');
    }

    public function generatePromotionReport()
    {
        // Implementar generación de reporte de promoción
        $this->dispatch('success', 'Reporte de promoción generado exitosamente');
    }

    public function render()
    {
        return view('livewire.admin.academic-tracking.promotion-control', [
            'students' => $this->students,
            'schoolPeriods' => $this->schoolPeriods,
            'programs' => $this->programs,
            'educationalLevels' => $this->educationalLevels,
            'grades' => $this->grades,
            'sections' => $this->sections,
            'stats' => $this->stats,
        ])->layout($this->getLayout(), ['title' => 'Control de Promoción']);
    }
}
