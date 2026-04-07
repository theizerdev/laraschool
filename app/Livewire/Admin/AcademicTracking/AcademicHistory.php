<?php

namespace App\Livewire\Admin\AcademicTracking;

use App\Models\AcademicRecord;
use App\Models\Matricula;
use App\Models\Student;
use App\Models\SchoolPeriod;
use App\Models\Subject;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use App\Traits\HasDynamicLayout;

#[Title('Historial Académico')]
class AcademicHistory extends Component
{
    use WithPagination, HasDynamicLayout;

    public $studentId;
    public $student;
    public $selectedPeriodId;
    public $selectedSubjectId;
    public $search = '';
    public $showStatistics = true;

    public $detailsMatricula = null;

    protected $queryString = [
        'selectedPeriodId' => ['except' => ''],
        'selectedSubjectId' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount($studentId = null)
    {
        if ($studentId) {
            $this->studentId = $studentId;
            $this->student = Student::find($studentId);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedPeriodId()
    {
        $this->resetPage();
    }

    public function updatingSelectedSubjectId()
    {
        $this->resetPage();
    }

    public function getBaseQuery()
    {
        $query = Matricula::with([
            'student',
            'schoolPeriod',
            'programa.nivelEducativo',
            'academicRecords.subject',
            'academicRecords.teacher.user'
        ]);

        if ($this->studentId) {
            $query->where('estudiante_id', $this->studentId);
        }

        if ($this->selectedPeriodId) {
            $query->where('periodo_id', $this->selectedPeriodId);
        }

        if ($this->selectedSubjectId) {
            $query->whereHas('academicRecords', function ($q) {
                $q->where('subject_id', $this->selectedSubjectId);
            });
        }

        if ($this->search) {
            $query->whereHas('student', function ($q) {
                $q->where('nombres', 'like', '%' . $this->search . '%')
                  ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                  ->orWhere('codigo', 'like', '%' . $this->search . '%');
            });
        }

        return $query;
    }

    public function getMatriculasProperty()
    {
        return $this->getBaseQuery()
            ->orderBy('periodo_id', 'desc')
            ->paginate(15);
    }

    public function getSchoolPeriodsProperty()
    {
        return SchoolPeriod::orderBy('name', 'desc')->get();
    }

    public function getSubjectsProperty()
    {
        return Subject::orderBy('name')->get();
    }

    public function getStatsProperty()
    {
        $baseQuery = $this->getBaseQuery();
        $matriculas = (clone $baseQuery)->get();

        $totalMatriculas = $matriculas->count();
        $totalMaterias = 0;
        $materiasAprobadas = 0;
        $materiasReprobadas = 0;

        foreach ($matriculas as $matricula) {
            $totalMaterias += $matricula->academicRecords->count();
            $materiasAprobadas += $matricula->academicRecords->where('status', AcademicRecord::STATUS_COMPLETED)->count();
            $materiasReprobadas += $matricula->academicRecords->whereIn('status', [AcademicRecord::STATUS_FAILED, AcademicRecord::STATUS_IN_RECOVERY])->count();
        }

        return [
            'total_matriculas' => $totalMatriculas,
            'total_materias' => $totalMaterias,
            'materias_aprobadas' => $materiasAprobadas,
            'materias_reprobadas' => $materiasReprobadas,
        ];
    }

    public function viewDetails($matriculaId)
    {
        $this->detailsMatricula = Matricula::with([
            'student',
            'schoolPeriod',
            'programa',
            'nivelEducativo',
            'academicRecords.subject',
            'academicRecords.subject.teachers.user',
            'academicRecords.recoveryPeriod'
        ])->find($matriculaId);

        $this->dispatch('show-details-modal');
    }

    public function closeDetails()
    {
        $this->detailsMatricula = null;
        $this->dispatch('hide-details-modal');
    }

    public function export()
    {
        $this->dispatch('success', 'Exportación de historial académico en desarrollo');
    }

    public function generateReport()
    {
        $this->dispatch('success', 'Generación de reporte académico en desarrollo');
    }

    public function render()
    {
        return view('livewire.admin.academic-tracking.academic-history', [
            'matriculas' => $this->matriculas,
            'schoolPeriods' => $this->schoolPeriods,
            'subjects' => $this->subjects,
            'stats' => $this->stats,
        ])->layout($this->getLayout());
    }
}
