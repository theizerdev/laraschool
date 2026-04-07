<div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Gestión de Períodos de Recuperación</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Períodos de Recuperación</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Total Períodos</p>
                            <h4 class="mb-0">{{ $this->totalPeriods }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                                <span class="avatar-title">
                                    <i class="bx bx-calendar font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Activos</p>
                            <h4 class="mb-0">{{ $this->activePeriods }}</h4>
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
        <div class="col-md-2">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">En Curso</p>
                            <h4 class="mb-0">{{ $this->currentPeriods }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                                <span class="avatar-title">
                                    <i class="bx bx-time font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Inscripción Abierta</p>
                            <h4 class="mb-0">{{ $this->openEnrollmentPeriods }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-warning">
                                <span class="avatar-title">
                                    <i class="bx bx-door-open font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Aprobados</p>
                            <h4 class="mb-0">{{ $this->approvedPeriods }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-purple">
                                <span class="avatar-title">
                                    <i class="bx bx-award font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Pendientes</p>
                            <h4 class="mb-0">{{ $this->pendingPeriods }}</h4>
                        </div>
                        <div class="flex-shrink-0 align-self-center">
                            <div class="mini-stat-icon avatar-sm rounded-circle bg-danger">
                                <span class="avatar-title">
                                    <i class="bx bx-clock font-size-24"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Búsqueda</label>
                            <input type="text" class="form-control" wire:model.live="search" placeholder="Buscar período...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" wire:model.live="status">
                                <option value="">Todos</option>
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="pending">Pendiente</option>
                                <option value="approved">Aprobado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button wire:click="create" class="btn btn-primary">
                                    <i class="bx bx-plus"></i> Nuevo Período
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button wire:click="export" class="btn btn-success">
                                    <i class="bx bx-export"></i> Exportar
                                </button>
                                <button wire:click="printReport" class="btn btn-info">
                                    <i class="bx bx-printer"></i> Imprimir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar Período -->
    @if($showForm)
        <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" wire:click.self="closeModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $isEditing ? 'Editar' : 'Crear' }} Período de Recuperación</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="save">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre del Período</label>
                                    <input type="text" class="form-control" wire:model="name" required>
                                    @error('name') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Período Escolar</label>
                                    <select class="form-select" wire:model="school_period_id" required>
                                        <option value="">Seleccione un período</option>
                                        @foreach($schoolPeriods as $period)
                                            <option value="{{ $period->id }}">{{ $period->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('school_period_id') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" wire:model="description" rows="3"></textarea>
                                    @error('description') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Inicio</label>
                                    <input type="date" class="form-control" wire:model="start_date" required>
                                    @error('start_date') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Fin</label>
                                    <input type="date" class="form-control" wire:model="end_date" required>
                                    @error('end_date') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Inicio de Inscripción</label>
                                    <input type="date" class="form-control" wire:model="enrollment_start_date" required>
                                    @error('enrollment_start_date') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fin de Inscripción</label>
                                    <input type="date" class="form-control" wire:model="enrollment_end_date" required>
                                    @error('enrollment_end_date') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label class="form-label">Nota Mínima Reprobatoria</label>
                                    <input type="number" class="form-control" wire:model="min_failing_grade" step="0.1" min="0" max="10" required>
                                    @error('min_failing_grade') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nota Máxima Reprobatoria</label>
                                    <input type="number" class="form-control" wire:model="max_failing_grade" step="0.1" min="0" max="10" required>
                                    @error('max_failing_grade') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nota Mínima de Recuperación</label>
                                    <input type="number" class="form-control" wire:model="min_recovery_grade" step="0.1" min="0" max="10" required>
                                    @error('min_recovery_grade') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="is_active" id="is_active">
                                        <label class="form-check-label" for="is_active">
                                            Período Activo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="save">{{ $isEditing ? 'Actualizar' : 'Guardar' }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Tabla de Períodos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Períodos de Recuperación</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Período Escolar</th>
                                    <th>Fechas</th>
                                    <th>Inscripción</th>
                                    <th>Rangos de Notas</th>
                                    <th>Estado</th>
                                    <th>Estudiantes</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recoveryPeriods as $period)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $period->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ Str::limit($period->description, 50) }}</small>
                                            </div>
                                        </td>
                                        <td>{{ $period->schoolPeriod->name ?? 'N/A' }}</td>
                                        <td>
                                            <div><small class="text-muted">Inicio:</small> {{ $period->start_date->format('d/m/Y') }}</div>
                                            <div><small class="text-muted">Fin:</small> {{ $period->end_date->format('d/m/Y') }}</div>
                                        </td>
                                        <td>
                                            <div><small class="text-muted">Inicio:</small> {{ optional($period->enrollment_start_date)->format('d/m/Y') ?? 'N/A' }}</div>
                                            <div><small class="text-muted">Fin:</small> {{ optional($period->enrollment_end_date)->format('d/m/Y') ?? 'N/A' }}</div>
                                        </td>
                                        <td>
                                            <div><small class="text-muted">Min:</small> {{ $period->min_failing_grade }}</div>
                                            <div><small class="text-muted">Max:</small> {{ $period->max_failing_grade }}</div>
                                            <div><small class="text-muted">Rec:</small> {{ $period->min_recovery_grade }}</div>
                                        </td>
                                        <td>
                                            @if($period->is_active)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div><small class="text-muted">Total:</small> {{ $period->recoveryEnrollments->count() }}</div>
                                            <div><small class="text-muted">Aprobados:</small> {{ $period->recoveryEnrollments->where('enrollment_status', 'approved')->count() }}</div>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="#" wire:click.prevent="viewStudents({{ $period->id }})"><i class="bx bx-show me-1"></i> Ver Estudiantes</a>
                                                    <a class="dropdown-item" href="#" wire:click.prevent="edit({{ $period->id }})"><i class="bx bx-edit me-1"></i> Editar</a>
                                                    @if($period->status !== 'approved')
                                                        <a class="dropdown-item" href="#" wire:click.prevent="approve({{ $period->id }})"><i class="bx bx-check-circle me-1"></i> Aprobar</a>
                                                    @endif
                                                    <a class="dropdown-item" href="#" wire:click.prevent="toggleStatus({{ $period->id }})">
                                                        @if($period->is_active)
                                                            <i class="bx bx-toggle-right me-1"></i> Desactivar
                                                        @else
                                                            <i class="bx bx-toggle-left me-1"></i> Activar
                                                        @endif
                                                    </a>
                                                    <a class="dropdown-item" href="#" wire:click.prevent="printPeriodReport({{ $period->id }})"><i class="bx bx-printer me-1"></i> Imprimir Reporte</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            No se encontraron períodos de recuperación
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-3">
                        {{ $recoveryPeriods->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ver Estudiantes -->
    @if($showStudents)
        <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" wire:click.self="closeStudentsModal">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Estudiantes en Recuperación - {{ $currentPeriod->name ?? '' }}</h5>
                        <button type="button" class="btn-close" wire:click="closeStudentsModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Materia</th>
                                        <th>Nota Original</th>
                                        <th>Nota Recuperación</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($studentsInRecovery as $record)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $record->student->full_name ?? 'N/A' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $record->student->code ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $record->subject->name ?? 'N/A' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $record->program->name ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>{{ $record->original_grade ?? 'N/A' }}</td>
                                            <td>{{ $record->recovery_grade ?? 'N/A' }}</td>
                                            <td>
                                                @if($record->status === 'approved')
                                                    <span class="badge bg-success">Aprobado</span>
                                                @elseif($record->status === 'pending')
                                                    <span class="badge bg-warning">Pendiente</span>
                                                @else
                                                    <span class="badge bg-danger">Reprobado</span>
                                                @endif
                                            </td>
                                            <td>{{ $record->created_at->format('d/m/Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                No hay estudiantes inscritos en este período de recuperación
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeStudentsModal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
