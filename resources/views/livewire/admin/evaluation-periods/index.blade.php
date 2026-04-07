<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Lapsos</h6>
                            <h2 class="mb-0">{{ $this->stats['total'] }}</h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="ri ri-calendar-line text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Activos</h6>
                            <h2 class="mb-0">{{ $this->stats['activos'] }}</h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="ri ri-check-line text-success" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Cerrados</h6>
                            <h2 class="mb-0">{{ $this->stats['cerrados'] }}</h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="ri ri-lock-line text-warning" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Lista de Lapsos de Evaluación</h5>
                            <p class="mb-0">Administra los lapsos de evaluación del sistema</p>
                        </div>
                        @can('create evaluation_periods')
                        <div>
                            <a href="{{ route('admin.evaluation-periods.create') }}" class="btn btn-primary">
                                <i class="ri ri-add-line"></i> Nuevo Lapso
                            </a>
                        </div>
                        @endcan
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card-header border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" placeholder="Buscar lapso..."
                                   wire:model.live.debounce.300ms="search">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Período Escolar</label>
                            <select class="form-select" wire:model.live="school_period_id">
                                <option value="">Todos</option>
                                @foreach($schoolPeriods as $sp)
                                    <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select class="form-select" wire:model.live="is_active">
                                <option value="">Todos</option>
                                <option value="1">Activos</option>
                                <option value="0">Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Cerrado</label>
                            <select class="form-select" wire:model.live="is_closed">
                                <option value="">Todos</option>
                                <option value="1">Cerrados</option>
                                <option value="0">Abiertos</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-label-secondary w-100" wire:click="clearFilters">
                                <i class="ri ri-eraser-line"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-datatable table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th wire:click="sortBy('number')" style="cursor: pointer;">
                                    #
                                    @if($sortBy === 'number')
                                        <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                    @endif
                                </th>
                                <th wire:click="sortBy('name')" style="cursor: pointer;">
                                    Nombre
                                    @if($sortBy === 'name')
                                        <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                    @endif
                                </th>
                                <th>Período Escolar</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Peso %</th>
                                <th>Estado</th>
                                <th>Cerrado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periods as $period)
                                <tr>
                                    <td><span class="badge bg-primary">{{ $period->number }}</span></td>
                                    <td>{{ $period->name }}</td>
                                    <td>{{ $period->schoolPeriod->name ?? '-' }}</td>
                                    <td>{{ $period->start_date->format('d/m/Y') }}</td>
                                    <td>{{ $period->end_date->format('d/m/Y') }}</td>
                                    <td>{{ number_format($period->weight, 2) }}%</td>
                                    <td>
                                        <span class="badge bg-{{ $period->is_active ? 'success' : 'danger' }}">
                                            {{ $period->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $period->is_closed ? 'secondary' : 'info' }}">
                                            {{ $period->is_closed ? 'Cerrado' : 'Abierto' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                <i class="ri ri-more-2-line"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @can('edit evaluation periods')
                                                <a class="dropdown-item" href="{{ route('admin.evaluation-periods.edit', $period->id) }}">
                                                    <i class="ri ri-pencil-line me-1"></i> Editar
                                                </a>
                                                <button type="button" class="dropdown-item" wire:click="toggleStatus({{ $period->id }})">
                                                    <i class="ri ri-{{ $period->is_active ? 'close' : 'check' }}-line me-1"></i>
                                                    {{ $period->is_active ? 'Desactivar' : 'Activar' }}
                                                </button>
                                                <button type="button" class="dropdown-item" wire:click="toggleClosed({{ $period->id }})">
                                                    <i class="ri ri-{{ $period->is_closed ? 'lock-unlock' : 'lock' }}-line me-1"></i>
                                                    {{ $period->is_closed ? 'Abrir' : 'Cerrar' }}
                                                </button>
                                                @endcan
                                                @can('delete evaluation periods')
                                                <button type="button" class="dropdown-item text-danger"
                                                        wire:click="delete({{ $period->id }})"
                                                        wire:confirm="¿Estás seguro de eliminar este lapso?">
                                                    <i class="ri ri-delete-bin-line me-1"></i> Eliminar
                                                </button>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No se encontraron lapsos</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="card-footer">
                    {{ $periods->links('livewire.pagination') }}
                </div>
            </div>
        </div>
    </div>
</div>
