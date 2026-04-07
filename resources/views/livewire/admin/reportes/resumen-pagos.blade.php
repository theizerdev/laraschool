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

    @if(session()->has('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="ri ri-information-line me-2"></i>
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Resumen de Pagos</h4>
            <p class="text-muted mb-0">Resumen de pagos por período</p>
        </div>
        <div>
            <button
                wire:click="exportarExcel"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
                class="btn btn-success me-2"
                @if(count($pagos) == 0) disabled @endif
            >
                <span wire:loading.remove wire:target="exportarExcel">
                    <i class="ri ri-file-excel-line me-1"></i> Exportar Excel
                </span>
                <span wire:loading wire:target="exportarExcel">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Exportando...
                </span>
            </button>
            <button wire:click="exportarPDF" class="btn btn-danger" @if(count($pagos) == 0) disabled @endif>
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
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="periodo_id" class="form-label">Período Escolar</label>
                        <select wire:model.live="periodo_id" class="form-select" id="periodo_id">
                            <option value="">Seleccione un período</option>
                           @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}">
                                    {{ $periodo->nombre ?? $periodo->name ?? 'Período sin nombre' }}
                                    @if($periodo->fecha_inicio && $periodo->fecha_fin)
                                        ({{ format_date($periodo->fecha_inicio) }} - {{ format_date($periodo->fecha_fin) }})
                                    @elseif($periodo->start_date && $periodo->end_date)
                                        ({{ format_date($periodo->start_date) }} - {{ format_date($periodo->end_date) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" wire:model.live="fecha_inicio" class="form-control" id="fecha_inicio" value="{{ $fecha_inicio ?? '' }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" wire:model.live="fecha_fin" class="form-control" id="fecha_fin" value="{{ $fecha_fin ?? '' }}">
                    </div>
                </div>
            </div>

            <button
                wire:click="cargarReporte"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
                class="btn btn-primary"
            >
                <span wire:loading.remove>
                    <i class="ri ri-search-line me-1"></i> Generar Reporte
                </span>
                <span wire:loading>
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Procesando...
                </span>
            </button>
            @error('error')
                <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if(count($pagos) > 0)
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Totales por Concepto</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th class="text-end">Cantidad</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($totales as $total)
                                        <tr>
                                            <td>
                                                <i class="ri ri-money-dollar-circle-line text-success me-2"></i>
                                                {{ $total->concepto }}
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary">{{ $total->cantidad }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-success"><x-dual-currency :amount="$total->total" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-success">
                                        <th>
                                            <i class="ri ri-calculator-line me-2"></i>
                                            Total General
                                        </th>
                                        <th class="text-end">
                                            <span class="badge bg-success">{{ $totales->sum('cantidad') }}</span>
                                        </th>
                                        <th class="text-end fw-bold"><x-dual-currency :amount="$totales->sum('total')" /></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Resumen</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 text-center">
                                    <p class="mb-1 text-muted">Total Pagos</p>
                                    <h4 class="mb-0">{{ count($pagos) }}</h4>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 text-center">
                                    <p class="mb-1 text-muted">Total Ingresos</p>
                                    <h4 class="mb-0 text-success"><x-dual-currency :amount="$pagos->sum('total')" /></h4>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 text-center">
                                    <p class="mb-1 text-muted">Período</p>
                                    <h6 class="mb-0">
                                        {{ $fecha_inicio ? format_date(\Carbon\Carbon::createFromFormat('Y-m-d', $fecha_inicio)) : 'N/A' }} -
                                        {{ $fecha_fin ? format_date(\Carbon\Carbon::createFromFormat('Y-m-d', $fecha_fin)) : 'N/A' }}
                                    </h6>
                                    @if($periodo_id)
                                        @php
                                            $periodo = \App\Models\SchoolPeriod::find($periodo_id);
                                        @endphp
                                        @if($periodo)
                                            <small class="text-muted">{{ $periodo->nombre ?? $periodo->name ?? 'Período académico' }}</small>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 text-center">
                                    <p class="mb-1 text-muted">Conceptos Únicos</p>
                                    <h4 class="mb-0">{{ $totales->count() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Detalle de Pagos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estudiante</th>
                                <th>Documento</th>
                                <th>Programa</th>
                                <th>Concepto</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Pagado</th>
                                <th>Método</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pagos as $pago)
                                <tr>
                                    <td>{{ $pago->fecha->format('d/m/Y') }}</td>
                                    <td>{{ $pago->matricula?->student?->nombres ?? '' }} {{ $pago->matricula?->student?->apellidos ?? '' }}</td>
                                    <td>{{ $pago->matricula?->student?->documento_identidad ?? 'N/A' }}</td>
                                    <td>{{ $pago->matricula?->programa?->nombre ?? 'N/A' }}</td>
                                    <td>
                                        @if($pago->detalles->count() > 0)
                                            @foreach($pago->detalles as $detalle)
                                                {{ $detalle->conceptoPago->nombre ?? 'N/A' }}
                                                @if(!$loop->last), @endif
                                            @endforeach
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="text-end"><x-dual-currency :amount="$pago->total" /></td>
                                    <td class="text-end"><x-dual-currency :amount="$pago->total" /></td>
                                    <td>
                                        @if($pago->metodo_pago == 'efectivo')
                                            <span class="badge bg-success">Efectivo</span>
                                        @elseif($pago->metodo_pago == 'transferencia')
                                            <span class="badge bg-info">Transferencia</span>
                                        @elseif($pago->metodo_pago == 'tarjeta')
                                            <span class="badge bg-warning">Tarjeta</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $pago->metodo_pago ?? 'N/A' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
