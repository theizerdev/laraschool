<?php

namespace App\Livewire\Admin\Grades;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use App\Traits\Exportable;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\EvaluationPeriod;
use App\Models\EducationalLevel;
use App\Models\Programa;
use App\Models\Student;
use App\Models\Matricula;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    use WithPagination, HasDynamicLayout, HasRegionalFormatting, Exportable;

    public $search = '';
    public $evaluation_period_id = '';
    public $status = '';
    public $programa_id = '';
    public $nivel_educativo_id = '';
    public $grado = '';
    public $seccion = '';
    public $sortBy = 'apellidos';
    public $sortDirection = 'asc';
    public $perPage = 25;
    public $expandedStudent = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'evaluation_period_id' => ['except' => ''],
        'status' => ['except' => ''],
        'programa_id' => ['except' => ''],
        'nivel_educativo_id' => ['except' => ''],
        'grado' => ['except' => ''],
        'seccion' => ['except' => ''],
        'sortBy' => ['except' => 'apellidos'],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 25]
    ];

    public function getStatsProperty()
    {
        return [
            'total' => Grade::count(),
            'calificadas' => Grade::where('status', 'graded')->count(),
            'pendientes' => Grade::where('status', 'pending')->count(),
            'promedio_general' => Grade::where('status', 'graded')->avg('score'),
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->expandedStudent = null;
    }

    public function updatedProgramaId()
    {
        $this->resetPage();
        $this->expandedStudent = null;
    }

    public function updatedNivelEducativoId()
    {
        $this->resetPage();
        $this->expandedStudent = null;
    }

    public function updatedGrado()
    {
        $this->resetPage();
        $this->expandedStudent = null;
    }

    public function updatedSeccion()
    {
        $this->resetPage();
        $this->expandedStudent = null;
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

    public function toggleStudent($studentId)
    {
        $this->expandedStudent = $this->expandedStudent === $studentId ? null : $studentId;
    }

    public function getStudentGradesProperty()
    {
        if (!$this->expandedStudent) {
            return collect();
        }

        return Grade::with(['evaluation.subject', 'evaluation.evaluationType', 'evaluation.evaluationPeriod', 'gradedBy'])
            ->where('student_id', $this->expandedStudent)
            ->when($this->evaluation_period_id !== '', function ($query) {
                $query->whereHas('evaluation', function ($q) {
                    $q->where('evaluation_period_id', $this->evaluation_period_id);
                });
            })
            ->when($this->status !== '', function ($query) {
                $query->where('status', $this->status);
            })
            ->get()
            ->groupBy(function ($grade) {
                return $grade->evaluation->evaluationPeriod->name ?? 'Sin Lapso';
            });
    }

    public function getStudentSummaryProperty()
    {
        if (!$this->expandedStudent) {
            return collect();
        }

        $grades = Grade::with(['evaluation.subject', 'evaluation.evaluationPeriod'])
            ->where('student_id', $this->expandedStudent)
            ->where('status', 'graded')
            ->get();

        $summary = [];
        $periods = [];

        foreach ($grades as $grade) {
            $subjectName = $grade->evaluation->subject->name ?? 'Sin Materia';
            $periodName = $grade->evaluation->evaluationPeriod->name ?? 'Sin Lapso';
            $subjectId = $grade->evaluation->subject->id ?? 0;
            $periodId = $grade->evaluation->evaluationPeriod->id ?? 0;

            if (!isset($summary[$subjectId])) {
                $summary[$subjectId] = [
                    'name' => $subjectName,
                    'periods' => [],
                ];
            }

            if (!isset($summary[$subjectId]['periods'][$periodId])) {
                $summary[$subjectId]['periods'][$periodId] = [
                    'name' => $periodName,
                    'scores' => [],
                ];
            }

            $summary[$subjectId]['periods'][$periodId]['scores'][] = $grade->score;
            $periods[$periodId] = $periodName;
        }

        // Calculate averages
        foreach ($summary as &$subject) {
            $allScores = [];
            foreach ($subject['periods'] as &$period) {
                $period['average'] = count($period['scores']) > 0 ? round(array_sum($period['scores']) / count($period['scores']), 2) : null;
                $allScores = array_merge($allScores, $period['scores']);
            }
            $subject['general_average'] = count($allScores) > 0 ? round(array_sum($allScores) / count($allScores), 2) : null;
        }

        ksort($periods);

        return [
            'subjects' => $summary,
            'periods' => $periods,
        ];
    }

    protected function getStudentStatsMap($studentIds)
    {
        $grades = Grade::select('student_id', 'score', 'status', 'evaluation_id')
            ->with('evaluation:id,subject_id,max_score')
            ->whereIn('student_id', $studentIds)
            ->get();

        $stats = [];
        foreach ($studentIds as $id) {
            $studentGrades = $grades->where('student_id', $id);
            $graded = $studentGrades->where('status', 'graded');
            $scores = $graded->pluck('score')->filter();

            $subjectScores = [];
            foreach ($graded as $g) {
                $subjectId = $g->evaluation->subject_id ?? null;
                if ($subjectId && $g->score !== null) {
                    $maxScore = $g->evaluation->max_score ?? 20;
                    if (!isset($subjectScores[$subjectId])) {
                        $subjectScores[$subjectId] = ['scores' => [], 'max' => $maxScore];
                    }
                    $subjectScores[$subjectId]['scores'][] = $g->score;
                }
            }

            $aprobadas = 0;
            $reprobadas = 0;
            foreach ($subjectScores as $data) {
                $avg = array_sum($data['scores']) / count($data['scores']);
                if ($avg >= ($data['max'] / 2)) {
                    $aprobadas++;
                } else {
                    $reprobadas++;
                }
            }

            $promedio = $scores->count() > 0 ? round($scores->avg(), 2) : null;
            $totalMaterias = count($subjectScores);

            $stats[$id] = [
                'promedio' => $promedio,
                'total_evaluaciones' => $studentGrades->count(),
                'calificadas' => $graded->count(),
                'pendientes' => $studentGrades->where('status', 'pending')->count(),
                'aprobadas' => $aprobadas,
                'reprobadas' => $reprobadas,
                'total_materias' => $totalMaterias,
            ];
        }

        return $stats;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->evaluation_period_id = '';
        $this->status = '';
        $this->programa_id = '';
        $this->nivel_educativo_id = '';
        $this->grado = '';
        $this->seccion = '';
        $this->sortBy = 'apellidos';
        $this->sortDirection = 'asc';
        $this->expandedStudent = null;
        $this->resetPage();
    }

    public function render()
    {
        $studentIds = Grade::query()
            ->when($this->evaluation_period_id !== '', function ($query) {
                $query->whereHas('evaluation', function ($q) {
                    $q->where('evaluation_period_id', $this->evaluation_period_id);
                });
            })
            ->when($this->status !== '', function ($query) {
                $query->where('status', $this->status);
            })
            ->pluck('student_id')
            ->unique();

        $studentsQuery = Student::with(['nivelEducativo', 'matriculas' => function ($q) {
                $q->latest('fecha_matricula')->with(['schoolPeriod', 'programa', 'nivelEducativo']);
            }])
            ->whereIn('id', $studentIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nombres', 'like', '%' . $this->search . '%')
                      ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                      ->orWhere('codigo', 'like', '%' . $this->search . '%')
                      ->orWhere('documento_identidad', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->nivel_educativo_id !== '', function ($query) {
                $query->where('nivel_educativo_id', $this->nivel_educativo_id);
            })
            ->when($this->grado !== '', function ($query) {
                $query->where('grado', $this->grado);
            })
            ->when($this->seccion !== '', function ($query) {
                $query->where('seccion', $this->seccion);
            })
            ->when($this->programa_id !== '', function ($query) {
                $query->whereHas('matriculas', function ($q) {
                    $q->where('programa_id', $this->programa_id);
                });
            });

        $sortField = $this->sortBy;
        if (in_array($sortField, ['apellidos', 'nombres', 'codigo', 'grado'])) {
            $studentsQuery->orderBy($sortField, $this->sortDirection);
        } else {
            $studentsQuery->orderBy('apellidos', 'asc');
        }

        $students = $studentsQuery->paginate($this->perPage);

        $pageStudentIds = $students->pluck('id')->toArray();
        $studentStatsMap = $this->getStudentStatsMap($pageStudentIds);

        $evaluationPeriods = EvaluationPeriod::active()->orderBy('number')->get();
        $nivelesEducativos = EducationalLevel::query()->orderBy('nombre')->get();
        $programas = Programa::query()->where('activo', true)->orderBy('nombre')->get();
        $grados = Student::whereIn('id', $studentIds)->select('grado')->distinct()->whereNotNull('grado')->orderBy('grado')->pluck('grado');
        $secciones = Student::whereIn('id', $studentIds)->select('seccion')->distinct()->whereNotNull('seccion')->orderBy('seccion')->pluck('seccion');

        return view('livewire.admin.grades.index', compact(
            'students',
            'evaluationPeriods',
            'nivelesEducativos',
            'programas',
            'grados',
            'secciones',
            'studentStatsMap'
        ))->layout($this->getLayout());
    }

    // Exportable trait methods
    protected function getExportQuery()
    {
        return Grade::with(['student', 'evaluation.subject', 'evaluation.evaluationPeriod', 'gradedBy'])
            ->when($this->evaluation_period_id !== '', function ($query) {
                $query->whereHas('evaluation', function ($q) {
                    $q->where('evaluation_period_id', $this->evaluation_period_id);
                });
            })
            ->when($this->status !== '', function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy('student_id');
    }

    protected function getExportHeaders()
    {
        return [
            'Código',
            'Estudiante',
            'Materia',
            'Evaluación',
            'Lapso',
            'Nota',
            'Nota Máxima',
            'Estado',
            'Calificado Por',
            'Fecha',
        ];
    }

    protected function formatExportRow($grade)
    {
        $statusLabels = [
            'pending' => 'Pendiente',
            'graded' => 'Calificado',
            'absent' => 'Ausente',
            'exempt' => 'Exonerado',
        ];

        return [
            $grade->student->codigo ?? '-',
            ($grade->student->apellidos ?? '') . ', ' . ($grade->student->nombres ?? ''),
            $grade->evaluation->subject->name ?? '-',
            $grade->evaluation->name ?? '-',
            $grade->evaluation->evaluationPeriod->name ?? '-',
            $grade->status === 'graded' ? number_format($grade->score, 2) : '-',
            number_format($grade->evaluation->max_score ?? 20, 2),
            $statusLabels[$grade->status] ?? $grade->status,
            $grade->gradedBy->name ?? '-',
            $grade->graded_at ? $grade->graded_at->format('d/m/Y') : '-',
        ];
    }

    protected function getPageTitle(): string
    {
        return 'Calificaciones';
    }

    protected function getBreadcrumb(): array
    {
        return [
            'admin.dashboard' => 'Dashboard',
            'admin.grades.index' => 'Calificaciones'
        ];
    }
}
