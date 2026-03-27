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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Nueva Matrícula</h4>
            <p class="text-muted mb-0">Registrar una nueva matrícula de estudiante</p>
        </div>
        <div>
            <a href="{{ route('admin.matriculas.index') }}" class="btn btn-secondary">
                <i class="ri ri-arrow-left-line me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Formulario de Matrícula</h5>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="store">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="searchStudent" class="form-label">Estudiante *</label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri ri-search-line"></i>
                                </span>
                                <input type="text" 
                                       wire:model.live.debounce.300ms="searchStudent" 
                                       class="form-control" 
                                       id="searchStudent"
                                       placeholder="Buscar por nombre, apellido o documento..."
                                       autocomplete="off">
                                @if($selectedStudent)
                                    <button type="button" 
                                            wire:click="clearStudentSelection" 
                                            class="btn btn-outline-secondary">
                                        <i class="ri ri-close-line"></i>
                                    </button>
                                @endif
                            </div>
                            
                            @if($showStudentDropdown && count($students) > 0)
                                <div class="dropdown-menu show w-100 mt-1" style="max-height: 300px; overflow-y: auto;">
                                    @foreach($students as $student)
                                        <a href="javascript:void(0)" 
                                           wire:click="selectStudent({{ $student->id }})" 
                                           class="dropdown-item d-flex align-items-center py-2">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial bg-primary rounded-circle">
                                                    {{ substr($student->nombres, 0, 1) }}{{ substr($student->apellidos, 0, 1) }}
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-medium">{{ $student->nombres }} {{ $student->apellidos }}</div>
                                                <small class="text-muted">
                                                    DNI: {{ $student->documento_identidad }}
                                                    @if($student->nivelEducativo)
                                                        • {{ $student->nivelEducativo->nombre }}
                                                    @endif
                                                </small>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @elseif($showStudentDropdown && strlen($searchStudent) >= 2)
                                <div class="dropdown-menu show w-100 mt-1">
                                    <div class="dropdown-item-text text-muted text-center py-3">
                                        <i class="ri ri-search-line me-1"></i>
                                        No se encontraron estudiantes disponibles
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        @if($selectedStudent)
                            <div class="mt-2 p-2 bg-light rounded">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial bg-success rounded-circle">
                                            {{ substr($selectedStudent->nombres, 0, 1) }}{{ substr($selectedStudent->apellidos, 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-medium">{{ $selectedStudent->nombres }} {{ $selectedStudent->apellidos }}</div>
                                        <small class="text-muted">
                                            DNI: {{ $selectedStudent->documento_identidad }}
                                            @if($selectedStudent->nivelEducativo)
                                                • {{ $selectedStudent->nivelEducativo->nombre }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @error('student_id') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="programa_id" class="form-label">Programa *</label>
                        <select wire:model="programa_id" class="form-select" id="programa_id" required>
                            <option value="">Seleccione un programa</option>
                            @foreach($programas as $programa)
                                <option value="{{ $programa->id }}">{{ $programa->nombre }}</option>
                            @endforeach
                        </select>
                        @error('programa_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="periodo_id" class="form-label">Período Escolar *</label>
                        <select wire:model.change="periodo_id" class="form-select" id="periodo_id" required>
                            <option value="">Seleccione un período</option>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->name }}</option>
                            @endforeach
                        </select>
                        @error('periodo_id') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="fecha_matricula" class="form-label">Fecha de Matrícula *</label>
                        <input type="date" wire:model="fecha_matricula" class="form-control" id="fecha_matricula" required>
                        @error('fecha_matricula') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="estado" class="form-label">Estado *</label>
                        <select wire:model="estado" class="form-select" id="estado" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                            <option value="graduado">Graduado</option>
                        </select>
                        @error('estado') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <h5 class="mt-4 mb-3">Información de Costos</h5>
                    </div>

                    <div class="col-md-4">
                        <label for="costo" class="form-label">Costo Total *</label>
                        <input type="number" step="0.01" wire:model.blur="costo" class="form-control" id="costo" required>
                        @error('costo') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="cuota_inicial" class="form-label">Cuota Inicial *</label>
                        <input type="number" step="0.01" wire:model.blur="cuota_inicial" class="form-control" id="cuota_inicial" required>
                        @error('cuota_inicial') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="numero_cuotas" class="form-label">Número de Cuotas *</label>
                        <input type="number" wire:model.blur="numero_cuotas" class="form-control" id="numero_cuotas" required>
                        @error('numero_cuotas') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>

                <!-- Tabla de amortización -->
                @if($showSchedule && count($paymentSchedule) > 0)
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="mb-3">Tabla de Amortización</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cuota</th>
                                        <th>Descripción</th>
                                        <th>Monto</th>
                                        <th>Fecha de Vencimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentSchedule as $schedule)
                                    <tr>
                                        <td>{{ $schedule['numero_cuota'] }}</td>
                                        <td>{{ $schedule['descripcion'] }}</td>
                                        <td>@money($schedule['monto'])</td>
                                        <td>{{ format_date($schedule['fecha_vencimiento']) }}
                                    </tr>
                                    @endforeach
                                    <tr class="table-info">
                                        <td colspan="2"><strong>Total</strong></td>
                                        <td><strong>@money(array_sum(array_column($paymentSchedule, 'monto')))</strong></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri ri-save-line me-1"></i> Guardar Matrícula
                    </button>
                    <a href="{{ route('admin.matriculas.index') }}" class="btn btn-secondary ms-2">
                        <i class="ri ri-arrow-left-line me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>