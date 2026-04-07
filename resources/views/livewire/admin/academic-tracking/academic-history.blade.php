<div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Historial Académico</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Historial Académico</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    @if($this->showStatistics)
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Total Matrículas</p>
                            <h4 class="mb-0">{{ $stats['total_matriculas'] }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                <span class="avatar-title">
                                    <i class="bx bx-book-bookmark font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Total Materias Cursadas</p>
                            <h4 class="mb-0">{{ $stats['total_materias'] }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                                <span class="avatar-title">
                                    <i class="bx bx-layer font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Materias Aprobadas</p>
                            <h4 class="mb-0">{{ $stats['materias_aprobadas'] }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                                <span class="avatar-title">
                                    <i class="bx bx-check-circle font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Materias Reprobadas</p>
                            <h4 class="mb-0">{{ $stats['materias_reprobadas'] }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-danger">
                                <span class="avatar-title">
                                    <i class="bx bx-x-circle font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Búsqueda</label>
                            <input type="text" class="form-control" wire:model.debounce.300ms="search" placeholder="Buscar estudiante...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Período Escolar</label>
                            <select class="form-select" wire:model.change="selectedPeriodId">
                                <option value="">Todos los períodos</option>
                                @foreach($schoolPeriods as $period)
                                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Materia (Filtra matrículas que la contengan)</label>
                            <select class="form-select" wire:model.change="selectedSubjectId">
                                <option value="">Todas las materias</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Principal (Resumen por Matrícula) -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Resumen Académico por Período</h4>
                    <div>
                        <button class="btn btn-primary btn-sm" wire:click="export">
                            <i class="bx bx-export me-1"></i> Exportar
                        </button>
                        <button class="btn btn-info btn-sm" wire:click="generateReport">
                            <i class="bx bx-file me-1"></i> Generar Reporte
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Período</th>
                                    <th>Programa</th>
                                    <th>Nivel Educativo</th>
                                    <th class="text-center">Materias Inscritas</th>
                                    <th class="text-center">Promedio General</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($matriculas as $matricula)
                                    @php
                                        $records = $matricula->academicRecords;
                                        $totalMaterias = $records->count();
                                        $materiasConNota = $records->whereNotNull('final_grade');
                                        $promedio = $materiasConNota->count() > 0 ? $materiasConNota->avg('final_grade') : null;

                                        if ($totalMaterias == 0) {
                                            $estadoGeneral = 'Sin Materias';
                                            $badgeClass = 'secondary';
                                        } else {
                                            $failedCount = $records->where('status', \App\Models\AcademicRecord::STATUS_FAILED)->count();
                                            $recoveryCount = $records->where('status', \App\Models\AcademicRecord::STATUS_IN_RECOVERY)->count();
                                            $completedCount = $records->where('status', \App\Models\AcademicRecord::STATUS_COMPLETED)->count();

                                            $finalizedCount = $failedCount + $recoveryCount + $completedCount;

                                            if ($finalizedCount < $totalMaterias) {
                                                $estadoGeneral = 'En Curso';
                                                $badgeClass = 'info';
                                            } else {
                                                if ($failedCount > 0) {
                                                    $estadoGeneral = 'Reprobado';
                                                    $badgeClass = 'danger';
                                                } elseif ($recoveryCount > 0) {
                                                    $estadoGeneral = 'En Recuperación';
                                                    $badgeClass = 'warning';
                                                } else {
                                                    $estadoGeneral = 'Aprobado';
                                                    $badgeClass = 'success';
                                                }
                                            }
                                        }
                                    @endphp
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $matricula->student->nombres ?? 'N/A' }} {{ $matricula->student->apellidos ?? '' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $matricula->student->codigo ?? 'N/A' }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $matricula->schoolPeriod->name ?? 'N/A' }}</td>
                                    <td>{{ $matricula->programa->nombre ?? 'N/A' }}</td>
                                    <td>{{ $matricula->programa->nivelEducativo->nombre ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $totalMaterias }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($promedio !== null)
                                            <span class="badge bg-{{ $promedio >= 10 ? 'success' : 'danger' }} font-size-14">
                                                {{ number_format($promedio, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $badgeClass }}">{{ $estadoGeneral }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary" wire:click="viewDetails({{ $matricula->id }})">
                                            <i class="bx bx-list-ul me-1"></i> Ver Notas
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bx bx-folder-open font-size-24 mb-2"></i>
                                            <p class="mb-0">No se encontraron registros académicos</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $matriculas->links('livewire.pagination') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalles -->
    @if($detailsMatricula)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Detalle de Calificaciones - {{ $detailsMatricula->schoolPeriod->name ?? '' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeDetails"></button>
                </div>
                <div class="modal-body">
                    <!-- Info del Estudiante -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="avatar-md">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle font-size-24">
                                            {{ substr($detailsMatricula->student->nombres ?? 'E', 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="font-size-16 mb-1">{{ $detailsMatricula->student->nombres ?? '' }} {{ $detailsMatricula->student->apellidos ?? '' }}</h5>
                                    <p class="text-muted mb-0">Código: {{ $detailsMatricula->student->codigo ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="text-muted mb-1">Programa: <span class="fw-medium text-body">{{ $detailsMatricula->programa->nombre ?? 'N/A' }}</span></p>
                            <p class="text-muted mb-0">Nivel: <span class="fw-medium text-body">{{ $detailsMatricula->nivelEducativo->nombre ?? 'N/A' }}</span></p>
                        </div>
                    </div>

                    <!-- Tabla de Materias -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th class="text-start">Materia</th>
                                    <th>Docente</th>
                                    <th>Parcial 1</th>
                                    <th>Parcial 2</th>
                                    <th>Parcial 3</th>
                                    <th>Nota Final</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @forelse($detailsMatricula->academicRecords as $record)
                                    @php
                                        $statusBadge = 'info';
                                        $statusText = $record->status_label;

                                        if ($record->status === \App\Models\AcademicRecord::STATUS_COMPLETED) {
                                            $statusBadge = 'success';
                                            $statusText = 'Aprobado';
                                        } elseif ($record->status === \App\Models\AcademicRecord::STATUS_FAILED) {
                                            $statusBadge = 'danger';
                                            $statusText = 'Reprobado';
                                        } elseif ($record->status === \App\Models\AcademicRecord::STATUS_WITHDRAWN) {
                                            $statusBadge = 'dark';
                                            $statusText = 'Retirado';
                                        } elseif ($record->status == 'in_recovery') {
                                            $statusBadge = 'warning';
                                            $statusText = 'En Recuperación';
                                        }
                                    @endphp
                                <tr>
                                    <td class="text-start fw-medium">{{ $record->subject->name ?? 'N/A' }}</td>
                                    <td>{{ $record->subject->teachers->first()->name ?? 'Sin Asignar' }}</td>
                                    <td>{{ $record->first_partial_grade ?? '-' }}</td>
                                    <td>{{ $record->second_partial_grade ?? '-' }}</td>
                                    <td>{{ $record->third_partial_grade ?? '-' }}</td>
                                    <td>
                                        @if($record->final_grade !== null)
                                            <strong class="text-{{ $record->final_grade >= 10 ? 'success' : 'danger' }}">
                                                {{ number_format($record->final_grade, 2) }}
                                            </strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $statusBadge }}">{{ $statusText }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-muted py-3">No hay materias registradas en este período.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDetails">Cerrar</button>
                    <button type="button" class="btn btn-primary"><i class="bx bx-printer me-1"></i> Imprimir Boletín</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
