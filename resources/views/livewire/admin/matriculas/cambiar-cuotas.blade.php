<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Cambiar Montos de Cuotas</h4>
            <p class="text-muted mb-0">Actualizar montos de cuotas mensuales pendientes</p>
        </div>
        <div>
            <a href="{{ route('admin.matriculas.index') }}" class="btn btn-secondary">
                <i class="ri ri-arrow-left-line me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Filtros Avanzados -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="ri ri-filter-3-line me-2"></i>Opciones de Actualización
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Aplicar cambios a:</label>
                    <select wire:model.live="aplicar_a" class="form-select">
                        <option value="seleccionadas">Cuotas seleccionadas</option>
                        <option value="todas_pendientes">Todas las pendientes</option>
                        <option value="por_curso">Por curso específico</option>
                    </select>
                </div>

                @if($aplicar_a === 'por_curso')
                <div class="col-md-4">
                    <label class="form-label">Curso:</label>
                    <select wire:model="curso_id" class="form-select">
                        <option value="">Seleccionar curso</option>
                        @foreach($cursos as $curso)
                            <option value="{{ $curso->id }}">{{ $curso->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($aplicar_a === 'todas_pendientes')
                <div class="col-md-4">
                    <label class="form-label">Desde fecha:</label>
                    <input type="date" wire:model="fecha_desde" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hasta fecha:</label>
                    <input type="date" wire:model="fecha_hasta" class="form-control">
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">Cuotas Mensuales Pendientes</h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="ri ri-search-line"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar estudiante...">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(count($this->selectedSchedules) > 0 || $aplicar_a !== 'seleccionadas')
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    @if($aplicar_a === 'seleccionadas')
                        <span>{{ count($this->selectedSchedules) }} cuotas seleccionadas</span>
                    @elseif($aplicar_a === 'todas_pendientes')
                        <span>Se aplicará a todas las cuotas pendientes</span>
                    @else
                        <span>Se aplicará al curso seleccionado</span>
                    @endif
                    <button wire:click="openModal" class="btn btn-sm btn-primary">
                        <i class="ri ri-edit-line me-1"></i> Actualizar Cuotas
                    </button>
                </div>
            @endif

            <div class="d-flex gap-2 mb-3">
                <button wire:click="selectAll" class="btn btn-sm btn-outline-primary">
                    <i class="ri ri-checkbox-multiple-line me-1"></i> Seleccionar Todo
                </button>
                <button wire:click="deselectAll" class="btn btn-sm btn-outline-secondary">
                    <i class="ri ri-checkbox-blank-line me-1"></i> Deseleccionar Todo
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" class="form-check-input"
                                       @if(count($this->selectedSchedules) > 0) checked @endif
                                       wire:click="@if(count($this->selectedSchedules) > 0) deselectAll @else selectAll @endif">
                            </th>
                            <th>Estudiante</th>
                            <th>Cuotas Pendientes</th>
                            <th>Monto Total Pendiente</th>
                            <th width="80" class="text-end">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matriculas as $matricula)
                            @php
                                $pendingSchedules = $matricula->paymentSchedules;
                                $pendingIds = $pendingSchedules->pluck('id')->toArray();
                                $allSelected = count($pendingIds) > 0 && count(array_intersect($pendingIds, $selectedSchedules)) === count($pendingIds);
                                $someSelected = count($pendingIds) > 0 && count(array_intersect($pendingIds, $selectedSchedules)) > 0 && !$allSelected;
                                $isExpanded = in_array($matricula->id, $expandedMatriculas);
                            @endphp
                            <tr class="align-middle">
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                           wire:click="selectMatriculaSchedules({{ $matricula->id }})"
                                           @if($allSelected) checked @endif>
                                </td>
                                <td style="cursor: pointer;" wire:click="toggleExpand({{ $matricula->id }})">
                                    @if($matricula->estudiante)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <div class="avatar-initial bg-primary bg-opacity-10 text-primary rounded">
                                                    {{ substr($matricula->estudiante->nombres, 0, 1) }}{{ substr($matricula->estudiante->apellidos, 0, 1) }}
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $matricula->estudiante->nombres }} {{ $matricula->estudiante->apellidos }}</h6>
                                                <small class="text-muted">Código: {{ $matricula->estudiante->codigo }} | {{ $matricula->programa->nombre ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-muted">
                                            <i class="ri ri-user-line me-1"></i> Estudiante no disponible
                                        </div>
                                    @endif
                                </td>
                                <td style="cursor: pointer;" wire:click="toggleExpand({{ $matricula->id }})">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                        {{ count($pendingSchedules) }} cuotas
                                    </span>
                                </td>
                                <td style="cursor: pointer;" wire:click="toggleExpand({{ $matricula->id }})">
                                    <span class="fw-bold text-primary">@money($pendingSchedules->sum('monto'))</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-icon btn-flat-secondary" wire:click="toggleExpand({{ $matricula->id }})">
                                        <i class="ri {{ $isExpanded ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }} fs-5"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            @if($isExpanded)
                                <tr>
                                    <td></td>
                                    <td colspan="4" class="p-0">
                                        <div class="bg-light p-3 border-start border-end border-bottom">
                                            <div class="card shadow-none border mb-0">
                                                <div class="card-header bg-white py-2">
                                                    <h6 class="mb-0 text-muted fs-7">Cuotas Pendientes Detalladas</h6>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th width="50" class="text-center">Sel.</th>
                                                                <th>Cuota</th>
                                                                <th>Monto Actual</th>
                                                                <th>Fecha Vencimiento</th>
                                                                <th>Estado</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($pendingSchedules as $schedule)
                                                                <tr class="align-middle">
                                                                    <td class="text-center">
                                                                        <input type="checkbox" class="form-check-input"
                                                                               wire:click="selectSchedule({{ $schedule->id }})"
                                                                               @if(in_array($schedule->id, $selectedSchedules)) checked @endif>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-info bg-opacity-10 text-info">
                                                                            Cuota #{{ $schedule->numero_cuota }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="fw-bold text-primary">@money($schedule->monto)</span>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="ri ri-calendar-line text-muted me-1"></i>
                                                                            {{ format_date($schedule->fecha_vencimiento) }}
                                                                            @if($schedule->fecha_vencimiento < now())
                                                                                <span class="badge bg-danger ms-2">Vencida</span>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-warning bg-opacity-10 text-warning">
                                                                            {{ ucfirst($schedule->estado) }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ri ri-file-list-line ri-48px text-muted mb-2"></i>
                                        <h6 class="text-muted">No hay cuotas pendientes</h6>
                                        <small class="text-muted">Todas las cuotas han sido pagadas o no hay matrículas activas</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $matriculas->links('livewire.pagination') }}
        </div>
    </div>

    <!-- Modal para cambiar montos -->
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ri ri-edit-line me-2"></i>Actualizar Cuotas
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>

                @if(!$showPreview)
                <form wire:submit="previewChanges">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="ri ri-information-line me-2"></i>
                            @if($aplicar_a === 'seleccionadas')
                                Se actualizarán <strong>{{ count($this->selectedSchedules) }}</strong> cuotas seleccionadas.
                            @elseif($aplicar_a === 'todas_pendientes')
                                Se actualizarán <strong>todas las cuotas pendientes</strong> en el rango de fechas.
                            @else
                                Se actualizarán las cuotas del <strong>curso seleccionado</strong>.
                            @endif
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de ajuste:</label>
                                <select wire:model.live="tipo_ajuste" class="form-select">
                                    <option value="monto">Monto fijo</option>
                                    <option value="porcentaje">Porcentaje de ajuste</option>
                                </select>
                            </div>

                            @if($tipo_ajuste === 'monto')
                            <div class="col-md-6">
                                <label class="form-label">Nuevo Monto ($) *</label>
                                <input type="number" step="0.01" wire:model="nuevo_monto"
                                       class="form-control @error('nuevo_monto') is-invalid @enderror"
                                       placeholder="Ingrese el nuevo monto">
                                @error('nuevo_monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @else
                            <div class="col-md-6">
                                <label class="form-label">Porcentaje de Ajuste (%) *</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" wire:model="porcentaje_ajuste"
                                           class="form-control @error('porcentaje_ajuste') is-invalid @enderror"
                                           placeholder="Ej: 10 para aumentar 10%">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Valores negativos para descuentos (ej: -10 para 10% descuento)</small>
                                @error('porcentaje_ajuste')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-info">
                            <i class="ri ri-eye-line me-1"></i> Previsualizar Cambios
                        </button>
                    </div>
                </form>
                @else
                <!-- Vista previa de cambios -->
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="ri ri-alert-line me-2"></i>
                        <strong>Previsualización de cambios</strong><br>
                        Revise los cambios antes de aplicarlos. Esta acción no se puede deshacer.
                    </div>

                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Cuota</th>
                                    <th>Monto Actual</th>
                                    <th>Monto Nuevo</th>
                                    <th>Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previewData as $item)
                                <tr>
                                    <td>{{ $item['estudiante'] }}</td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            #{{ $item['cuota'] }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($item['monto_original'], 2) }}</td>
                                    <td class="fw-bold text-primary">${{ number_format($item['monto_nuevo'], 2) }}</td>
                                    <td>
                                        <span class="badge {{ $item['diferencia'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $item['diferencia'] >= 0 ? '+' : '' }}${{ number_format($item['diferencia'], 2) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center py-2">
                                    <small class="text-muted">Total de cuotas</small>
                                    <h6 class="mb-0">{{ count($previewData) }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center py-2">
                                    <small class="text-muted">Monto total actual</small>
                                    <h6 class="mb-0">${{ number_format(collect($previewData)->sum('monto_original'), 2) }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center py-2">
                                    <small class="text-muted">Monto total nuevo</small>
                                    <h6 class="mb-0 text-primary">${{ number_format(collect($previewData)->sum('monto_nuevo'), 2) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showPreview', false)">
                        <i class="ri ri-arrow-left-line me-1"></i> Volver
                    </button>
                    <button type="button" wire:click="aplicarCambios" class="btn btn-success">
                        <i class="ri ri-check-line me-1"></i> Aplicar Cambios
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
