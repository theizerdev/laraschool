<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ri ri-file-list-3-line ri-24px"></i>
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Total Calificaciones</h6>
                            <h4 class="mb-0">{{ $this->stats['total'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ri ri-check-double-line ri-24px"></i>
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Calificadas</h6>
                            <h4 class="mb-0">{{ $this->stats['calificadas'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3 mb-3 mb-xl-0">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ri ri-time-line ri-24px"></i>
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Pendientes</h6>
                            <h4 class="mb-0">{{ $this->stats['pendientes'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ri ri-bar-chart-box-line ri-24px"></i>
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted">Promedio General</h6>
                            <h4 class="mb-0">{{ number_format($this->stats['promedio_general'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0">Gestión de Calificaciones</h5>
                <small class="text-muted">Listado de estudiantes con calificaciones registradas</small>
            </div>
            <div>
                <button wire:click="export" class="btn btn-outline-success btn-sm">
                    <i class="ri ri-file-excel-2-line me-1"></i> Exportar Excel
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-header border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Buscar Estudiante</label>
                    <input type="text" id="search" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Nombre, apellido, código o cédula...">
                </div>
                <div class="col-md-2">
                    <label for="nivel_educativo_id" class="form-label">Nivel Educativo</label>
                    <select id="nivel_educativo_id" class="form-select" wire:model.live="nivel_educativo_id">
                        <option value="">Todos</option>
                        @foreach($nivelesEducativos as $nivel)
                            <option value="{{ $nivel->id }}">{{ $nivel->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="programa_id" class="form-label">Programa</label>
                    <select id="programa_id" class="form-select" wire:model.live="programa_id">
                        <option value="">Todos</option>
                        @foreach($programas as $programa)
                            <option value="{{ $programa->id }}">{{ $programa->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="evaluation_period_id" class="form-label">Lapso</label>
                    <select id="evaluation_period_id" class="form-select" wire:model.live="evaluation_period_id">
                        <option value="">Todos</option>
                        @foreach($evaluationPeriods as $period)
                            <option value="{{ $period->id }}">{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Estado</label>
                    <select id="status" class="form-select" wire:model.live="status">
                        <option value="">Todos</option>
                        <option value="pending">Pendiente</option>
                        <option value="graded">Calificado</option>
                        <option value="absent">Ausente</option>
                        <option value="exempt">Exonerado</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-2">
                    <label for="grado" class="form-label">Grado</label>
                    <select id="grado" class="form-select" wire:model.live="grado">
                        <option value="">Todos</option>
                        @foreach($grados as $g)
                            <option value="{{ $g }}">{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="seccion" class="form-label">Sección</label>
                    <select id="seccion" class="form-select" wire:model.live="seccion">
                        <option value="">Todas</option>
                        @foreach($secciones as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button wire:click="clearFilters" class="btn btn-secondary w-100">
                        <i class="ri ri-eraser-line me-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card-datatable table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th wire:click="sortBy('codigo')" style="cursor: pointer;" class="text-nowrap">
                            Código
                            @if($sortBy === 'codigo') <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th wire:click="sortBy('apellidos')" style="cursor: pointer;" class="text-nowrap">
                            Estudiante
                            @if($sortBy === 'apellidos') <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th>Período</th>
                        <th>Programa</th>
                        <th>Nivel Educativo</th>
                        <th wire:click="sortBy('grado')" style="cursor: pointer;" class="text-nowrap">
                            Grado / Sección
                            @if($sortBy === 'grado') <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th class="text-center">Promedio</th>
                        <th class="text-center">Materias</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $matricula = $student->matriculas->first();
                            $stats = $studentStatsMap[$student->id] ?? null;
                            $promedio = $stats['promedio'] ?? null;
                            $aprobadas = $stats['aprobadas'] ?? 0;
                            $reprobadas = $stats['reprobadas'] ?? 0;
                            $totalMaterias = $stats['total_materias'] ?? 0;

                            // Determinar color de fila según promedio
                            $rowClass = '';
                            if ($promedio !== null) {
                                if ($promedio >= 15) {
                                    $rowClass = 'table-success bg-opacity-10';
                                } elseif ($promedio >= 10) {
                                    $rowClass = '';
                                } else {
                                    $rowClass = 'table-danger bg-opacity-10';
                                }
                            }
                        @endphp
                        <tr wire:key="student-{{ $student->id }}"
                            class="{{ $expandedStudent === $student->id ? 'table-active' : $rowClass }}">
                            <td>{{ $student->codigo ?? '-' }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($student->foto)
                                        <img src="{{ asset('storage/' . $student->foto) }}" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                    @else
                                        <div class="bg-label-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <i class="ri ri-user-line text-primary"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <span class="fw-semibold">{{ $student->apellidos }}, {{ $student->nombres }}</span>
                                        @if($reprobadas >= 3)
                                            <i class="ri ri-alert-fill text-danger ms-1" title="Estudiante en riesgo: {{ $reprobadas }} materias reprobadas"></i>
                                        @endif
                                        <br><small class="text-muted">{{ $student->documento_identidad ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-label-primary">{{ $matricula->schoolPeriod->name ?? '-' }}</span>
                            </td>
                            <td>{{ $matricula->programa->nombre ?? '-' }}</td>
                            <td>
                                <span class="badge bg-label-info">{{ $matricula->nivelEducativo->nombre ?? $student->nivelEducativo->nombre ?? '-' }}</span>
                            </td>
                            <td>
                                @if($student->grado || $student->seccion)
                                    {{ $student->grado ?? '-' }} / {{ $student->seccion ?? '-' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($promedio !== null)
                                    @php
                                        $promedioColor = $promedio >= 15 ? 'success' : ($promedio >= 10 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge bg-{{ $promedioColor }} fs-6">{{ number_format($promedio, 2) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($totalMaterias > 0)
                                    <span class="text-success fw-semibold" title="Aprobadas">{{ $aprobadas }}</span>
                                    <span class="text-muted">/</span>
                                    <span class="{{ $reprobadas > 0 ? 'text-danger fw-semibold' : 'text-muted' }}" title="Reprobadas">{{ $reprobadas }}</span>
                                    <br>
                                    <small class="text-muted">de {{ $totalMaterias }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button wire:click="toggleStudent({{ $student->id }})" class="btn btn-sm {{ $expandedStudent === $student->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                    <i class="ri ri-{{ $expandedStudent === $student->id ? 'eye-off-line' : 'eye-line' }} me-1"></i>
                                    {{ $expandedStudent === $student->id ? 'Ocultar' : 'Ver Calificaciones' }}
                                </button>
                            </td>
                        </tr>

                        {{-- Expanded grades for this student --}}
                        @if($expandedStudent === $student->id)
                            <tr wire:key="grades-{{ $student->id }}">
                                <td colspan="9" class="p-0 border-0">
                                    <div class="bg-light p-4">
                                        {{-- Mini Summary Card --}}
                                        @php $summary = $this->studentSummary; @endphp
                                        @if(!empty($summary['subjects']))
                                            <div class="card mb-3 shadow-sm border-primary border-top border-3">
                                                <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                                                    <h6 class="mb-0">
                                                        <i class="ri ri-bar-chart-grouped-line me-1 text-primary"></i>
                                                        Resumen por Materia
                                                    </h6>
                                                    <span class="badge bg-label-primary">{{ count($summary['subjects']) }} materias</span>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered mb-0">
                                                        <thead class="table-primary">
                                                            <tr>
                                                                <th class="fw-semibold">Materia</th>
                                                                @foreach($summary['periods'] as $periodName)
                                                                    <th class="text-center fw-semibold">{{ $periodName }}</th>
                                                                @endforeach
                                                                <th class="text-center fw-semibold">Promedio Final</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($summary['subjects'] as $subject)
                                                                @php
                                                                    $finalAvg = $subject['general_average'];
                                                                    $finalColor = $finalAvg !== null ? ($finalAvg >= 15 ? 'success' : ($finalAvg >= 10 ? 'warning' : 'danger')) : 'secondary';
                                                                @endphp
                                                                <tr>
                                                                    <td class="fw-semibold">{{ $subject['name'] }}</td>
                                                                    @foreach($summary['periods'] as $periodId => $periodName)
                                                                        <td class="text-center">
                                                                            @if(isset($subject['periods'][$periodId]))
                                                                                @php
                                                                                    $avg = $subject['periods'][$periodId]['average'];
                                                                                    $color = $avg >= 10 ? 'success' : 'danger';
                                                                                @endphp
                                                                                <span class="badge bg-label-{{ $color }}">{{ number_format($avg, 2) }}</span>
                                                                            @else
                                                                                <span class="text-muted">-</span>
                                                                            @endif
                                                                        </td>
                                                                    @endforeach
                                                                    <td class="text-center">
                                                                        @if($finalAvg !== null)
                                                                            <span class="badge bg-{{ $finalColor }} fs-6">{{ number_format($finalAvg, 2) }}</span>
                                                                        @else
                                                                            <span class="text-muted">-</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Grades grouped by period --}}
                                        @if($this->studentGrades->isEmpty())
                                            <div class="text-center text-muted py-3">
                                                <i class="ri ri-file-list-3-line ri-2x mb-2"></i>
                                                <p class="mb-0">No se encontraron calificaciones para este estudiante</p>
                                            </div>
                                        @else
                                            @foreach($this->studentGrades as $periodName => $periodGrades)
                                                @php
                                                    $periodGraded = $periodGrades->where('status', 'graded');
                                                    $periodAvg = $periodGraded->count() > 0 ? round($periodGraded->avg('score'), 2) : null;
                                                    $periodColor = $periodAvg !== null ? ($periodAvg >= 15 ? 'success' : ($periodAvg >= 10 ? 'warning' : 'danger')) : 'secondary';
                                                @endphp
                                                <div class="card mb-3 shadow-sm">
                                                    <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                                                        <h6 class="mb-0">
                                                            <i class="ri ri-calendar-line me-1 text-primary"></i>
                                                            {{ $periodName }}
                                                        </h6>
                                                        <div>
                                                            <span class="badge bg-label-secondary me-2">{{ $periodGrades->count() }} evaluaciones</span>
                                                            @if($periodAvg !== null)
                                                                <span class="badge bg-{{ $periodColor }}">Promedio: {{ number_format($periodAvg, 2) }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-hover mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Materia</th>
                                                                    <th>Evaluación</th>
                                                                    <th>Tipo</th>
                                                                    <th class="text-center">Nota</th>
                                                                    <th class="text-center">Máx.</th>
                                                                    <th>Estado</th>
                                                                    <th>Calificado por</th>
                                                                    <th>Observaciones</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($periodGrades as $grade)
                                                                    <tr>
                                                                        <td class="fw-semibold">{{ $grade->evaluation->subject->name ?? '-' }}</td>
                                                                        <td>{{ $grade->evaluation->name ?? '-' }}</td>
                                                                        <td>
                                                                            <span class="badge bg-label-secondary">{{ $grade->evaluation->evaluationType->name ?? '-' }}</span>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if($grade->status === 'graded' && $grade->score !== null)
                                                                                @php
                                                                                    $maxScore = $grade->evaluation->max_score ?? 20;
                                                                                    $isApproved = $grade->score >= ($maxScore / 2);
                                                                                @endphp
                                                                                <span class="badge bg-{{ $isApproved ? 'success' : 'danger' }} fs-6">
                                                                                    {{ number_format($grade->score, 2) }}
                                                                                </span>
                                                                            @else
                                                                                <span class="text-muted">-</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-center">{{ number_format($grade->evaluation->max_score ?? 20, 2) }}</td>
                                                                        <td>
                                                                            @php
                                                                                $statusColors = [
                                                                                    'pending' => 'warning',
                                                                                    'graded' => 'success',
                                                                                    'absent' => 'secondary',
                                                                                    'exempt' => 'info'
                                                                                ];
                                                                                $statusLabels = [
                                                                                    'pending' => 'Pendiente',
                                                                                    'graded' => 'Calificado',
                                                                                    'absent' => 'Ausente',
                                                                                    'exempt' => 'Exonerado'
                                                                                ];
                                                                            @endphp
                                                                            <span class="badge bg-label-{{ $statusColors[$grade->status] ?? 'secondary' }}">
                                                                                {{ $statusLabels[$grade->status] ?? $grade->status }}
                                                                            </span>
                                                                        </td>
                                                                        <td>{{ $grade->gradedBy->name ?? '-' }}</td>
                                                                        <td>
                                                                            @if($grade->observations)
                                                                                <small class="text-muted" title="{{ $grade->observations }}">
                                                                                    <i class="ri ri-chat-3-line me-1"></i>
                                                                                    {{ \Illuminate\Support\Str::limit($grade->observations, 30) }}
                                                                                </small>
                                                                            @else
                                                                                <span class="text-muted">-</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="ri ri-file-list-3-line ri-48px text-muted mb-2"></i>
                                <p class="text-muted mb-0">No se encontraron estudiantes con calificaciones</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>
                <select class="form-select form-select-sm" wire:model.live="perPage" style="width: auto;">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div>
                {{ $students->links('livewire.pagination') }}
            </div>
        </div>
    </div>
</div>
