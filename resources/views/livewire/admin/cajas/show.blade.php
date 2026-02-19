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

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Detalle de Caja - {{ format_date($caja->fecha) }}</h5>
                            <p class="mb-0">
                                Estado:
                                @if($caja->estado === 'abierta')
                                    <span class="badge bg-success">Abierta</span>
                                @else
                                    <span class="badge bg-secondary">Cerrada</span>
                                @endif
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success" wire:click="exportarExcel">
                                <i class="ri ri-file-excel-2-line"></i> Exportar Excel
                            </button>
                            @if($caja->estado === 'abierta')
                                @can('edit cajas')
                                <button type="button" class="btn btn-warning" wire:click="abrirModalCerrar">
                                    <i class="ri ri-lock-line"></i> Cerrar Caja
                                </button>
                                @endcan
                            @else
                                @can('edit cajas')
                                <button type="button" class="btn btn-info" wire:click="recalcularMontos">
                                    <i class="ri ri-calculator-line"></i> Recalcular Montos
                                </button>
                                @endcan
                            @endif
                            <a href="{{ route('admin.cajas.index') }}" class="btn btn-secondary">
                                <i class="ri ri-arrow-left-line"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen General -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Monto Inicial</h6>
                            <h3 class="mb-0">
                                <x-dual-currency :amount="$caja->monto_inicial" />
                            </h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="ri ri-money-dollar-circle-line text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Ingresos</h6>
                            <h3 class="mb-0">
                                <span class="text-success fw-bold">${{ number_format($this->totalUsdPagos, 2, '.', ',') }}</span>
                                @if($this->totalBsPagos)
                                    <small class="d-block text-muted">Bs. {{ number_format($this->totalBsPagos, 2, ',', '.') }}</small>
                                @endif
                            </h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="ri ri-arrow-up-circle-line text-success" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Monto Final</h6>
                            <h3 class="mb-0">
                                <span class="text-success fw-bold">${{ number_format($this->totalUsdPagos, 2, '.', ',') }}</span>
                                @if($this->totalBsPagos)
                                    <small class="d-block text-muted">Bs. {{ number_format($this->totalBsPagos, 2, ',', '.') }}</small>
                                @endif
                            </h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="ri ri-safe-line text-info" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Pagos</h6>
                            <h3 class="mb-0">{{ $caja->pagos->where('estado', 'aprobado')->count() }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="ri ri-file-list-3-line text-warning" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Resumen por Método de Pago -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Resumen por Método de Pago</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Método</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->resumenPorMetodo as $metodo)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @php
                                                    $iconClass = match($metodo->metodo_pago) {
                                                        'efectivo' => 'ri ri-money-dollar-circle-line text-success',
                                                        'transferencia' => 'ri ri-bank-line text-info',
                                                        'tarjeta' => 'ri ri-bank-card-line text-primary',
                                                        default => 'ri ri-question-line text-muted'
                                                    };
                                                @endphp
                                                <i class="{{ $iconClass }} me-2"></i>
                                                {{ ucfirst($metodo->metodo_pago) }}
                                            </div>
                                        </td>
                                        <td class="text-end">{{ $metodo->cantidad }}</td>
                                        <td class="text-end fw-semibold">
                                            <span class="text-success fw-bold">${{ number_format($metodo->total, 2, '.', ',') }}</span>
                                            @if($metodo->total_bs > 0)
                                                <small class="d-block text-muted">Bs. {{ number_format($metodo->total_bs, 2, ',', '.') }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No hay pagos registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen por Concepto -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Resumen por Concepto</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Concepto</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->resumenPorConcepto as $concepto)
                                    <tr>
                                        <td>{{ $concepto['concepto'] }}</td>
                                        <td class="text-end">{{ $concepto['cantidad'] }}</td>
                                        <td class="text-end fw-semibold">
                                            <span class="text-success fw-bold">${{ number_format($concepto['total'], 2, '.', ',') }}</span>
                                            @if(($concepto['total_bs'] ?? 0) > 0)
                                                <small class="d-block text-muted">Bs. {{ number_format($concepto['total_bs'], 2, ',', '.') }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No hay conceptos registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalle de Pagos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Detalle de Pagos</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Estudiante</th>
                                    <th>Método</th>
                                    <th class="text-end">Total</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($caja->pagos as $pago)
                                    <tr>
                                        <td>
                                            <div class="fw-medium text-primary">{{ $pago->numero_completo }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $pago->matricula->student->nombres ?? '' }} {{ $pago->matricula->student->apellidos ?? '' }}</div>
                                            <small class="text-muted">{{ $pago->matricula->student->documento_identidad ?? '' }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $iconClass = match($pago->metodo_pago) {
                                                    'efectivo' => 'ri ri-money-dollar-circle-line text-success',
                                                    'transferencia' => 'ri ri-bank-line text-info',
                                                    'tarjeta' => 'ri ri-bank-card-line text-primary',
                                                    default => 'ri ri-question-line text-muted'
                                                };
                                            @endphp
                                            <i class="{{ $iconClass }} me-1"></i>
                                            {{ ucfirst($pago->metodo_pago) }}
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <span class="text-success fw-bold">${{ number_format($pago->total, 2, '.', ',') }}</span>
                                            @if($pago->total_bolivares)
                                                <small class="d-block text-muted">Bs. {{ number_format($pago->total_bolivares, 2, ',', '.') }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $pago->created_at->format('H:i') }}</td>
                                        <td>
                                            @if($pago->estado === 'aprobado')
                                                <span class="badge bg-success">Aprobado</span>
                                            @elseif($pago->estado === 'pendiente')
                                                <span class="badge bg-warning">Pendiente</span>
                                            @else
                                                <span class="badge bg-danger">Cancelado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay pagos registrados en esta caja</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cerrar Caja -->
    @if($showCerrarModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cerrar Caja</h5>
                    <button type="button" class="btn-close" wire:click="$set('showCerrarModal', false)"></button>
                </div>
                <form wire:submit="cerrarCaja">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="ri ri-alert-line me-2"></i>
                            <strong>¿Estás seguro de cerrar la caja?</strong><br>
                            Una vez cerrada no se podrán agregar más pagos a esta caja.
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Monto Final Calculado:</label>
                                <div class="fw-bold text-success"><x-dual-currency :amount="$caja->monto_final" /></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Total Ingresos:</label>
                                <div class="fw-bold text-primary"><x-dual-currency :amount="$caja->total_ingresos" /></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observaciones de Cierre</label>
                            <textarea class="form-control" wire:model="observaciones_cierre" rows="3"
                                      placeholder="Observaciones sobre el cierre de caja..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showCerrarModal', false)">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="ri ri-lock-line"></i> Cerrar Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Recalcular Montos -->
    @if($showRecalcularModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Recalcular Montos</h5>
                    <button type="button" class="btn-close" wire:click="$set('showRecalcularModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ri ri-calculator-line me-2"></i>
                        <strong>¿Deseas recalcular los montos de esta caja?</strong><br>
                        Esto actualizará el monto final de cierre basándose en los pagos actuales.
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Monto Actual:</label>
                            <div class="fw-bold">$ {{ number_format($caja->monto_final, 2, '.', ',') }}</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Total Ingresos:</label>
                            <div class="fw-bold">$ {{ number_format($caja->total_ingresos, 2, '.', ',') }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showRecalcularModal', false)">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-info" wire:click="confirmarRecalcular">
                        <i class="ri ri-calculator-line"></i> Recalcular Montos
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
