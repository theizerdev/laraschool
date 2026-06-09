<div>
    <div>
    <!-- Alertas -->
    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri ri-error-warning-line me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri ri-check-line me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session()->has('message'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="ri ri-information-line me-2"></i>
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Reporte de Morosidad</h4>
            <p class="text-muted mb-0">Morosidad por nivel/programa</p>
            <div class="mt-2">
                <span class="badge bg-{{ $whatsappStatus === 'connected' ? 'success' : 'secondary' }}">
                    <i class="ri ri-whatsapp-line me-1"></i>
                    WhatsApp: {{ $whatsappStatus === 'connected' ? 'Conectado' : 'Desconectado' }}
                </span>
            </div>
        </div>
        <div>
            <button
                wire:click="exportarExcel"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
                class="btn btn-success me-2"
                @if(count($morosos) == 0 && count($solventes) == 0) disabled @endif
            >
                <span wire:loading.remove wire:target="exportarExcel">
                    <i class="ri ri-file-excel-line me-1"></i> Exportar Excel
                </span>
                <span wire:loading wire:target="exportarExcel">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Exportando...
                </span>
            </button>
            <button wire:click="exportarPDF" class="btn btn-danger me-2" @if(count($morosos) == 0 && count($solventes) == 0) disabled @endif>
                <i class="ri ri-file-pdf-line me-1"></i> Exportar PDF
            </button>

        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nivel_educativo_id" class="form-label">Nivel Educativo</label>
                        <select wire:model.live="nivel_educativo_id" class="form-select" id="nivel_educativo_id">
                            <option value="">Todos los niveles</option>
                            @foreach($nivelesEducativos as $nivel)
                                <option value="{{ $nivel->id }}">{{ $nivel->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="programa_id" class="form-label">Programa</label>
                        <select wire:model.live="programa_id" class="form-select" id="programa_id" @if($programas->count() == 0) disabled @endif>
                            <option value="">Todos los programas</option>
                            @foreach($programas as $programa)
                                <option value="{{ $programa->id }}">{{ $programa->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="fecha_desde" class="form-label">Fecha Desde</label>
                        <input type="date" wire:model="fecha_desde" class="form-control" id="fecha_desde">
                        <div class="form-text">Opcional: Filtrar cuotas vencidas desde esta fecha</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                        <input type="date" wire:model="fecha_hasta" class="form-control" id="fecha_hasta">
                        <div class="form-text">Filtrar cuotas vencidas hasta esta fecha</div>
                    </div>
                </div>
            </div>

            <button wire:click="cargarReporte" class="btn btn-primary">
                <i class="ri ri-search-line me-1"></i> Generar Reporte
            </button>
        </div>
    </div>

    @if(isset($totales) && isset($totales['total_estudiantes']) && $totales['total_estudiantes'] > 0)
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="mb-1 text-muted">Total Estudiantes</p>
                        <h3 class="mb-0">{{ $totales['total_estudiantes'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="mb-1 text-muted">Estudiantes Morosos</p>
                        <h3 class="mb-0 text-danger">{{ $totales['total_morosos'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="mb-1 text-muted">Porcentaje de Morosidad</p>
                        <h3 class="mb-0
                            @if(($totales['porcentaje_morosidad'] ?? 0) > 20) text-danger
                            @elseif(($totales['porcentaje_morosidad'] ?? 0) > 10) text-warning
                            @else text-success @endif">
                            {{ Number::format($totales['porcentaje_morosidad'] ?? 0, 2) }}%
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if((count($morosos) > 0 || count($solventes) > 0))
        <!-- Tabs -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button
                            wire:click="$set('activeTab', 'morosos')"
                            class="nav-link {{ $activeTab === 'morosos' ? 'active' : '' }}"
                            type="button"
                            role="tab"
                        >
                            <i class="ri ri-error-warning-line me-1"></i>
                            Morosos ({{ count($morosos) }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button
                            wire:click="$set('activeTab', 'solventes')"
                            class="nav-link {{ $activeTab === 'solventes' ? 'active' : '' }}"
                            type="button"
                            role="tab"
                        >
                            <i class="ri ri-checkbox-circle-line me-1"></i>
                            Solventes ({{ count($solventes) }})
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <!-- Tab Morosos -->
                @if($activeTab === 'morosos')
                    @if(count($morosos) > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th wire:click="$set('sortBy', 'estudiante_id'); $set('sortDirection', $sortBy === 'estudiante_id' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            Estudiante
                                            @if($sortBy === 'estudiante_id')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th>Programa</th>
                                        <th>Nivel</th>
                                        <th>Estado</th>
                                        <th class="text-end">Cuotas</th>
                                        <th class="text-end" wire:click="$set('sortBy', 'costo_rango'); $set('sortDirection', $sortBy === 'costo_rango' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            Costo (Rango)
                                            @if($sortBy === 'costo_rango')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th class="text-end" wire:click="$set('sortBy', 'pagado_rango'); $set('sortDirection', $sortBy === 'pagado_rango' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            Pagado (Rango)
                                            @if($sortBy === 'pagado_rango')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th class="text-end" wire:click="$set('sortBy', 'saldo_pendiente_rango'); $set('sortDirection', $sortBy === 'saldo_pendiente_rango' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            Pendiente (Rango)
                                            @if($sortBy === 'saldo_pendiente_rango')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th class="text-end" wire:click="$set('sortBy', 'porcentaje_pagado_rango'); $set('sortDirection', $sortBy === 'porcentaje_pagado_rango' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            % Pagado (Rango)
                                            @if($sortBy === 'porcentaje_pagado_rango')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th>Riesgo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($morosos as $moroso)
                                        <tr>
                                            <td>{{ $moroso['estudiante_nombre'] }}</td>
                                            <td>{{ $moroso['programa_nombre'] }}</td>
                                            <td>{{ $moroso['nivel_nombre'] }}</td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    {{ $moroso['estado'] }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ $moroso['cantidad_cuotas'] }}</td>
                                            <td class="text-end"><x-dual-currency :amount="$moroso['costo_rango']" /></td>
                                            <td class="text-end"><x-dual-currency :amount="$moroso['pagado_rango']" /></td>
                                            <td class="text-end"><x-dual-currency :amount="$moroso['saldo_pendiente_rango']" /></td>
                                            <td class="text-end">{{ number_format($moroso['porcentaje_pagado_rango'], 2) }}%</td>
                                            <td>
                                                @if($moroso['porcentaje_pagado_rango'] < 30)
                                                    <span class="badge bg-danger">Alto Riesgo</span>
                                                @elseif($moroso['porcentaje_pagado_rango'] < 60)
                                                    <span class="badge bg-warning">Medio Riesgo</span>
                                                @else
                                                    <span class="badge bg-info">Bajo Riesgo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button
                                                    wire:click="mostrarDetalleDeuda({{ $moroso['id'] }})"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Ver detalles"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if(config('whatsapp.active'))
                                                <button
                                                    wire:click="enviarNotificacion({{ $moroso['id'] }})"
                                                    class="btn btn-sm btn-outline-success ms-1"
                                                    title="Enviar notificación por WhatsApp"
                                                    @if($whatsappStatus !== 'connected') disabled @endif
                                                >
                                                    <i class="fab fa-whatsapp"></i>
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ri ri-checkbox-circle-line ri-3x text-success mb-3"></i>
                            <h5 class="mb-2">¡Excelente!</h5>
                            <p class="text-muted mb-0">No se encontraron estudiantes morosos con los filtros aplicados</p>
                        </div>
                    @endif
                @endif

                <!-- Tab Solventes -->
                @if($activeTab === 'solventes')
                    @if(count($solventes) > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th wire:click="$set('sortBy', 'estudiante_id'); $set('sortDirection', $sortBy === 'estudiante_id' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            Estudiante
                                            @if($sortBy === 'estudiante_id')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th>Programa</th>
                                        <th>Nivel</th>
                                        <th>Estado</th>
                                        <th class="text-end">Cuotas</th>
                                        <th class="text-end" wire:click="$set('sortBy', 'costo_rango'); $set('sortDirection', $sortBy === 'costo_rango' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            Costo (Rango)
                                            @if($sortBy === 'costo_rango')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th class="text-end" wire:click="$set('sortBy', 'pagado_rango'); $set('sortDirection', $sortBy === 'pagado_rango' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            Pagado (Rango)
                                            @if($sortBy === 'pagado_rango')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th class="text-end" wire:click="$set('sortBy', 'saldo_pendiente_rango'); $set('sortDirection', $sortBy === 'saldo_pendiente_rango' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            Pendiente (Rango)
                                            @if($sortBy === 'saldo_pendiente_rango')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th class="text-end" wire:click="$set('sortBy', 'porcentaje_pagado_rango'); $set('sortDirection', $sortBy === 'porcentaje_pagado_rango' && $sortDirection === 'asc' ? 'desc' : 'asc')" style="cursor: pointer;">
                                            % Pagado (Rango)
                                            @if($sortBy === 'porcentaje_pagado_rango')
                                                <i class="ri ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                                            @endif
                                        </th>
                                        <th>Riesgo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($solventes as $solvente)
                                        <tr>
                                            <td>{{ $solvente['estudiante_nombre'] }}</td>
                                            <td>{{ $solvente['programa_nombre'] }}</td>
                                            <td>{{ $solvente['nivel_nombre'] }}</td>
                                            <td>
                                                <span class="badge bg-success">
                                                    {{ $solvente['estado'] }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ $solvente['cantidad_cuotas'] }}</td>
                                            <td class="text-end"><x-dual-currency :amount="$solvente['costo_rango']" /></td>
                                            <td class="text-end"><x-dual-currency :amount="$solvente['pagado_rango']" /></td>
                                            <td class="text-end"><x-dual-currency :amount="$solvente['saldo_pendiente_rango']" /></td>
                                            <td class="text-end">{{ number_format($solvente['porcentaje_pagado_rango'], 2) }}%</td>
                                            <td>
                                                @if($solvente['porcentaje_pagado_rango'] < 30)
                                                    <span class="badge bg-danger">Alto Riesgo</span>
                                                @elseif($solvente['porcentaje_pagado_rango'] < 60)
                                                    <span class="badge bg-warning">Medio Riesgo</span>
                                                @else
                                                    <span class="badge bg-info">Bajo Riesgo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button
                                                    wire:click="mostrarDetalleDeuda({{ $solvente['id'] }})"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Ver detalles"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ri ri-search-eye-line ri-3x text-muted mb-3"></i>
                            <h5 class="mb-2">Sin datos</h5>
                            <p class="text-muted mb-0">No se encontraron estudiantes solventes con los filtros aplicados</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ri ri-search-eye-line ri-3x text-muted mb-3"></i>
                <h5 class="mb-2">No hay datos para mostrar</h5>
                <p class="text-muted mb-0">Configure los filtros y genere el reporte</p>
            </div>
        </div>
    @endif
</div>

<!-- Modal para mostrar el detalle de la deuda -->
<div class="modal fade" id="detalleDeudaModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Deuda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="cerrarModal"></button>
            </div>
            <div class="modal-body">
                @if($estudianteSeleccionado)
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Estudiante:</strong> {{ $estudianteSeleccionado->estudiante->nombres ?? '' }} {{ $estudianteSeleccionado->estudiante->apellidos ?? '' }}</p>
                            <p><strong>Documento:</strong> {{ $estudianteSeleccionado->estudiante->documento_identidad ?? '' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Programa:</strong> {{ $estudianteSeleccionado->programa->nombre ?? '' }}</p>
                            <p><strong>Nivel:</strong> {{ $estudianteSeleccionado->programa->nivelEducativo->nombre ?? '' }}</p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <p class="mb-1 text-muted">Costo Total (Rango)</p>
                                <h4 class="mb-0"><x-dual-currency :amount="$detalleDeuda->sum('monto')" /></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <p class="mb-1 text-muted">Total Pagado (Rango)</p>
                                <h4 class="mb-0 text-success"><x-dual-currency :amount="$detalleDeuda->sum('monto_pagado')" /></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center bg-warning bg-opacity-10">
                                <p class="mb-1 text-muted">Saldo Pendiente (Rango)</p>
                                <h4 class="mb-0 text-warning">
                                    <x-dual-currency :amount="$detalleDeuda->sum('monto') - $detalleDeuda->sum('monto_pagado')" />
                                </h4>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Detalle de Pagos</h6>
                        <div class="btn-group" role="group">
                            <button wire:click="enviarNotificacionDeuda" class="btn btn-sm btn-warning">
                                <i class="ri ri-mail-send-line me-1"></i> Enviar Email
                            </button>
                            @if($whatsappStatus === 'connected')
                                <button wire:click="enviarWhatsAppMorosidad" class="btn btn-sm btn-success">
                                    <i class="ri ri-whatsapp-line me-1"></i> Enviar WhatsApp
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-success" disabled title="WhatsApp no conectado">
                                    <i class="ri ri-whatsapp-line me-1"></i> WhatsApp
                                </button>
                            @endif
                        </div>
                    </div>

                    @if(count($detalleDeuda) > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Concepto</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-end">Pagado</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($detalleDeuda as $cuota)
                                        <tr>
                                            <td>{{ format_date($cuota->fecha_vencimiento) ?? 'N/A' }}</td>
                                            <td>Cuota {{ $cuota->numero_cuota ?? 'N/A' }}</td>
                                            <td class="text-end"><x-dual-currency :amount="$cuota->monto" /></td>
                                            <td class="text-end"><x-dual-currency :amount="$cuota->monto_pagado ?? 0" /></td>
                                            <td>
                                                @if($cuota->monto_pagado >= $cuota->monto)
                                                    <span class="badge bg-success">Pagado</span>
                                                @elseif($cuota->monto_pagado > 0)
                                                    <span class="badge bg-warning">Parcial</span>
                                                @else
                                                    <span class="badge bg-danger">Pendiente</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="ri ri-file-search-line ri-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No se encontraron pagos registrados</p>
                        </div>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="cerrarModal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Script para controlar la modal -->
@script
<script>
    $wire.on('mostrarModal', () => {
        const modal = new bootstrap.Modal(document.getElementById('detalleDeudaModal'));
        modal.show();
    });

    document.addEventListener('livewire:init', function () {
        const modalElement = document.getElementById('detalleDeudaModal');
        modalElement.addEventListener('hidden.bs.modal', function () {
            $wire.cerrarModal();
        });
    });
</script>
@endscript

<!-- Mostrar la modal si mostrarModal es true -->
@if($mostrarModal)
    @script
    <script>
        document.addEventListener('livewire:init', function () {
            const modal = new bootstrap.Modal(document.getElementById('detalleDeudaModal'));
            modal.show();
        });
    </script>
    @endscript
@endif
</div>
