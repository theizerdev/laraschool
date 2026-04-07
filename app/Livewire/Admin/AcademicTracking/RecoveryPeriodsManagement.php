<?php

namespace App\Livewire\Admin\AcademicTracking;

use App\Models\RecoveryPeriod;
use App\Models\SchoolPeriod;
use App\Models\AcademicRecord;
use App\Models\Student;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Traits\HasDynamicLayout;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

#[Title('Gestión de Períodos de Recuperación')]
class RecoveryPeriodsManagement extends Component
{
    use WithPagination, HasDynamicLayout;

    public $name;
    public $description;
    public $schoolPeriodId;
    public $startDate;
    public $endDate;
    public $registrationStartDate;
    public $registrationEndDate;
    public $minFailingGrade = 0;
    public $maxFailingGrade = 9.99;
    public $minRecoveryGrade = 10.00;
    public $isActive = true;
    public $recoveryPeriodId;
    public $showForm = false;
    public $showStudents = false;
    public $isEditing = false;
    public $search = '';
    public $selectedStatus = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'schoolPeriodId' => 'required|exists:school_periods,id',
        'startDate' => 'required|date',
        'endDate' => 'required|date|after:startDate',
        'registrationStartDate' => 'required|date|before:startDate',
        'registrationEndDate' => 'required|date|after:registrationStartDate|before:startDate',
        'minFailingGrade' => 'required|numeric|min:0|max:20',
        'maxFailingGrade' => 'required|numeric|min:0|max:20|gt:minFailingGrade',
        'minRecoveryGrade' => 'required|numeric|min:0|max:20',
        'isActive' => 'boolean',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedStatus' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedStatus()
    {
        $this->resetPage();
    }

    public function getBaseQuery()
    {
        return RecoveryPeriod::with(['schoolPeriod', 'createdBy', 'approvedBy'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhereHas('schoolPeriod', function ($periodQuery) {
                          $periodQuery->where('nombre', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->selectedStatus, function ($query) {
                switch ($this->selectedStatus) {
                    case 'active':
                        $query->where('is_active', true);
                        break;
                    case 'inactive':
                        $query->where('is_active', false);
                        break;
                    case 'current':
                        $query->current();
                        break;
                    case 'registration_open':
                        $query->registrationOpen();
                        break;
                    case 'approved':
                        $query->approved();
                        break;
                    case 'pending':
                        $query->whereNull('approved_at');
                        break;
                }
            });
    }

    public function getRecoveryPeriodsProperty()
    {
        return $this->getBaseQuery()
            ->orderBy('start_date', 'desc')
            ->paginate(15);
    }

    public function getSchoolPeriodsProperty()
    {
        return SchoolPeriod::orderBy('name', 'desc')->get();
    }

    public function getTotalPeriodsProperty()
    {
        return $this->getBaseQuery()->count();
    }

    public function getActivePeriodsProperty()
    {
        return $this->getBaseQuery()->where('is_active', true)->count();
    }

    public function getCurrentPeriodsProperty()
    {
        return $this->getBaseQuery()->current()->count();
    }

    public function getOpenEnrollmentPeriodsProperty()
    {
        return $this->getBaseQuery()->registrationOpen()->count();
    }

    public function getApprovedPeriodsProperty()
    {
        return $this->getBaseQuery()->approved()->count();
    }

    public function getPendingPeriodsProperty()
    {
        return $this->getBaseQuery()->whereNull('approved_at')->count();
    }

    public function getStatsProperty()
    {
        $baseQuery = RecoveryPeriod::query();

        return [
            'total' => $baseQuery->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'current' => (clone $baseQuery)->current()->count(),
            'registration_open' => (clone $baseQuery)->registrationOpen()->count(),
            'approved' => (clone $baseQuery)->approved()->count(),
            'pending' => (clone $baseQuery)->whereNull('approved_at')->count(),
        ];
    }

    public function create()
    {
        $this->resetInputFields();
        $this->showForm = true;
    }

    public function edit($id)
    {
        $recoveryPeriod = RecoveryPeriod::findOrFail($id);

        $this->recoveryPeriodId = $id;
        $this->name = $recoveryPeriod->name;
        $this->description = $recoveryPeriod->description;
        $this->schoolPeriodId = $recoveryPeriod->school_period_id;
        $this->startDate = $recoveryPeriod->start_date->format('Y-m-d');
        $this->endDate = $recoveryPeriod->end_date->format('Y-m-d');
        $this->registrationStartDate = $recoveryPeriod->registration_start_date->format('Y-m-d');
        $this->registrationEndDate = $recoveryPeriod->registration_end_date->format('Y-m-d');
        $this->minFailingGrade = $recoveryPeriod->min_failing_grade;
        $this->maxFailingGrade = $recoveryPeriod->max_failing_grade;
        $this->minRecoveryGrade = $recoveryPeriod->min_recovery_grade;
        $this->isActive = $recoveryPeriod->is_active;

        $this->isEditing = true;
        $this->showForm = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'school_period_id' => $this->schoolPeriodId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'registration_start_date' => $this->registrationStartDate,
            'registration_end_date' => $this->registrationEndDate,
            'min_failing_grade' => $this->minFailingGrade,
            'max_failing_grade' => $this->maxFailingGrade,
            'min_recovery_grade' => $this->minRecoveryGrade,
            'is_active' => $this->isActive,
            'created_by' => auth()->id(),
        ];

        if ($this->recoveryPeriodId) {
            $recoveryPeriod = RecoveryPeriod::findOrFail($this->recoveryPeriodId);
            $recoveryPeriod->update($data);
            $this->dispatch('success', 'Período de recuperación actualizado exitosamente');
        } else {
            RecoveryPeriod::create($data);
            $this->dispatch('success', 'Período de recuperación creado exitosamente');
        }

        $this->resetInputFields();
        $this->showForm = false;
    }

    public function closeModal()
    {
        $this->resetInputFields();
    }

    public function approve($id)
    {
        $recoveryPeriod = RecoveryPeriod::findOrFail($id);

        if (!$recoveryPeriod->approved_at) {
            $recoveryPeriod->update([
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->dispatch('success', 'Período de recuperación aprobado exitosamente');
        }
    }

    public function deactivate($id)
    {
        $recoveryPeriod = RecoveryPeriod::findOrFail($id);

        $recoveryPeriod->update([
            'is_active' => false,
        ]);

        $this->dispatch('success', 'Período de recuperación desactivado exitosamente');
    }

    public function toggleStatus($id)
    {
        $recoveryPeriod = RecoveryPeriod::findOrFail($id);
        $recoveryPeriod->update([
            'is_active' => !$recoveryPeriod->is_active,
        ]);

        $status = $recoveryPeriod->is_active ? 'activado' : 'desactivado';
        $this->dispatch('success', "Período de recuperación {$status} exitosamente");
    }

    public function printPeriodReport($id)
    {
        $recoveryPeriod = RecoveryPeriod::findOrFail($id);
        $fileName = 'Reporte-Recuperacion-' . Str::slug($recoveryPeriod->name) . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RecoveryPeriodReportExport($recoveryPeriod), $fileName);
    }

    public function viewStudents($id)
    {
        $this->recoveryPeriodId = $id;
        $this->showStudents = true;
    }

    public function closeStudentsModal()
    {
        $this->showStudents = false;
        $this->reset('recoveryPeriodId');
    }

    public function getCurrentPeriodProperty()
    {
        return $this->recoveryPeriodId ? RecoveryPeriod::find($this->recoveryPeriodId) : null;
    }

    public function getStudentsInRecoveryProperty()
    {
        if (!$this->recoveryPeriodId) {
            return collect();
        }

        return \App\Models\RecoveryEnrollment::with(['student', 'subject'])
            ->where('recovery_period_id', $this->recoveryPeriodId)
            ->when($this->search, function ($query) {
                $query->whereHas('student', function ($q) {
                    $q->where('nombres', 'like', '%' . $this->search . '%')
                      ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                      ->orWhere('codigo', 'like', '%' . $this->search . '%');
                });
            })
            ->paginate(15);
    }

    public function resetInputFields()
    {
        $this->recoveryPeriodId = null;
        $this->name = '';
        $this->description = '';
        $this->schoolPeriodId = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->registrationStartDate = '';
        $this->registrationEndDate = '';
        $this->minFailingGrade = 0;
        $this->maxFailingGrade = 9.99;
        $this->minRecoveryGrade = 10.00;
        $this->isActive = true;
        $this->showForm = false;
        $this->showStudents = false;
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.admin.academic-tracking.recovery-periods-management', [
            'recoveryPeriods' => $this->recoveryPeriods,
            'schoolPeriods' => $this->schoolPeriods,
            'stats' => $this->stats,
            'studentsInRecovery' => $this->showStudents ? $this->studentsInRecovery : collect(),
        ])->layout($this->getLayout());
    }
}
