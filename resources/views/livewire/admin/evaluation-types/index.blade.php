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
        <div class="col-md-6">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Tipos</h6>
                            <h2 class="mb-0">{{ $this->stats['total'] }}</h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="ri ri-list-check text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
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
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Lista de Tipos de Evaluación</h5>
                            <p class="mb-0">Administra los tipos de evaluación del sistema</p>
                        </div>
                        @can('create evaluation types')
                        <div>
                            <a href="{{ route('admin.evaluation-types.create') }}" class="btn btn-primary">
                                <i class="ri ri-add-line"></i> Nuevo Tipo
                            </a>
                        </div>
                        @endcan
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card-header border-bottom">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" placeholder="Buscar por nombre o código..."
                                   wire:model.live.debounce.300ms="search">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" wire:model.live="is_active">
                                <option value="">Todos los estados</option>
                                <option value="1">Activos</option>
                                <option value="0">Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mostrar</label>
                            <select class="form-select" wire:model.live="perPage">
                                <option value="10">10 por página</option>
                                <option value="25">25 por página</option>
                                <option value="50">50 por página</option>
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
                                <th wire:click="sortBy('code')" style="cursor: pointer;">
                                    Código
                                    @if($sortBy === 'code')
                                        <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                    @endif
                                </th>
                                <th wire:click="sortBy('name')" style="cursor: pointer;">
                                    Nombre
                                    @if($sortBy === 'name')
                                        <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                    @endif
                                </th>
                                <th>Peso por Defecto</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($types as $type)
                                <tr>
                                    <td><span class="badge bg-label-secondary">{{ $type->code }}</span></td>
                                    <td>{{ $type->name }}</td>
                                    <td>{{ number_format($type->default_weight, 2) }}%</td>
                                    <td>{{ Str::limit($type->description, 50) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $type->is_active ? 'success' : 'danger' }}">
                                            {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                <i class="ri ri-more-2-line"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                @can('edit evaluation types')
                                                <a class="dropdown-item" href="{{ route('admin.evaluation-types.edit', $type->id) }}">
                                                    <i class="ri ri-pencil-line me-1"></i> Editar
                                                </a>
                                                <button type="button" class="dropdown-item"
                                                        wire:click="toggleStatus({{ $type->id }})">
                                                    <i class="ri ri-{{ $type->is_active ? 'close' : 'check' }}-line me-1"></i>
                                                    {{ $type->is_active ? 'Desactivar' : 'Activar' }}
                                                </button>
                                                @endcan
                                                @can('delete evaluation_types')
                                                <button type="button" class="dropdown-item text-danger"
                                                        wire:click="delete({{ $type->id }})"
                                                        wire:confirm="¿Estás seguro de eliminar este tipo de evaluación?">
                                                    <i class="ri ri-delete-bin-line me-1"></i> Eliminar
                                                </button>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No se encontraron tipos de evaluación</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="card-footer">
                   {{ $types->links('livewire.pagination') }}
                </div>
            </div>
        </div>
    </div>
</div>
