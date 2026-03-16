<div wire:poll.30s="$dispatch('refresh-rates')">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h4 class="mb-1">Tasas de Cambio BCV</h4>
                            <p class="mb-0 text-muted">Banco Central de Venezuela - Actualización automática</p>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="text-end me-3">
                                <small class="text-muted d-block">Última actualización: {{ $lastUpdate }}</small>
                                <small class="text-muted">Horarios: 10:00 AM y 2:00 PM</small>
                            </div>

                            <button wire:click="fetchNow" class="btn btn-primary">
                                <i class="ri ri-refresh-line me-1"></i>Actualizar Ahora
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Métricas principales -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded-3">
                                <i class="ri ri-money-dollar-circle-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info mt-4">
                        <h4 class="mb-1">{{ number_format($stats['usd_rate'] ?? 0, 4) }}</h4>
                        <p class="mb-0">Bolívares por USD</p>
                        <small class="text-muted">{{ $stats['last_fetch'] ?? 'N/A' }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded-3">
                                <i class="ri ri-money-euro-circle-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info mt-4">
                        <h4 class="mb-1">{{ number_format($stats['eur_rate'] ?? 0, 4) }}</h4>
                        <p class="mb-0">Bolívares por EUR</p>
                        <small class="text-muted">{{ $stats['last_fetch'] ?? 'N/A' }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded-3">
                                <i class="ri ri-calendar-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info mt-4">
                        <h4 class="mb-1">{{ $stats['date'] ?? 'N/A' }}</h4>
                        <p class="mb-0">Fecha de la Tasa</p>
                        <small class="text-muted">Verificada</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-warning rounded-3">
                                <i class="ri ri-database-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-info mt-4">
                        <h4 class="mb-1">Fuente Confiable</h4>
                        <p class="mb-0">Fuente de Datos</p>
                        <small class="text-muted">10:00 AM - 2:00 PM</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasa activa del día -->
    @if($todayRate)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary border-2">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ri ri-exchange-dollar-line me-2"></i>Tasa del Día</h5>

                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-6 border-end">
                            <h2 class="display-6 text-primary">{{ number_format($todayRate->usd_rate, 4) }}</h2>
                            <p class="mb-0 text-muted">Bolívares por Dólar (USD)</p>
                            <small class="text-muted">Fuente: {{ $todayRate->source }}</small>
                        </div>
                        <div class="col-md-6">
                            <h2 class="display-6 text-primary">{{ number_format($todayRate->eur_rate, 4) }}</h2>
                            <p class="mb-0 text-muted">Bolívares por Euro (EUR)</p>
                            <small class="text-muted">Actualizado: {{ $todayRate->fetch_time->format('H:i') }}</small>
                        </div>
                         <button wire:click="editRate()" class="btn btn-primary">
                        <i class="ri ri-add-line me-1"></i>Editar tasa del dia
                    </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning border-2">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="ri ri-alert-line me-2"></i>No hay tasa registrada para hoy</h5>
                </div>
                <div class="card-body text-center">
                    <p class="mb-3">Aún no se ha registrado una tasa de cambio para el día de hoy.</p>
                  
                    <button wire:click="editRate()" class="btn btn-primary">
                        <i class="ri ri-add-line me-1"></i>Registrar Tasa Manual
                    </button>
                    
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Histórico mensual -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 d-flex align-items-center gap-2">
                        <i class="ri ri-calendar-2-line"></i>
                        Histórico por Mes
                        @if(isset($monthHistoryExists) && $monthHistoryExists)
                            <span class="badge bg-success">Guardado</span>
                        @else
                            <span class="badge bg-secondary">Pendiente</span>
                        @endif
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <input type="month" class="form-control" style="max-width: 180px"
                               wire:model.live="selectedMonth">
                        <button class="btn btn-outline-primary" wire:click="loadMonthHistory">
                            <i class="ri ri-file-chart-line me-1"></i>Consultar
                        </button>
                        @if(isset($monthHistoryExists) && $monthHistoryExists)
                            <button class="btn btn-success" wire:click="saveMonthHistory">
                                <i class="ri ri-save-3-line me-1"></i>Actualizar histórico
                            </button>
                        @else
                            <button class="btn btn-success" wire:click="saveMonthHistory">
                                <i class="ri ri-save-3-line me-1"></i>Guardar en histórico
                            </button>
                        @endif
                        <button class="btn btn-outline-success" wire:click="backfillMonthFromBCV" @if($isBackfilling) disabled @endif>
                            <i class="ri ri-database-2-line me-1"></i>
                            @if($isBackfilling) Rellenando... @else Rellenar mes (BCV) @endif
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($showMonthHistory)
                        <div class="row g-4 mb-3">
                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted">Periodo</div>
                                    <div class="fw-bold">{{ $monthStats['period'] ?? '' }}</div>
                                    <small class="text-muted">Registros: {{ $monthStats['records'] ?? 0 }}</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted">Estado</div>
                                    @if(isset($monthHistoryExists) && $monthHistoryExists)
                                        <div>Guardado</div>
                                        <small class="text-muted">
                                            Última generación: {{ $monthHistoryInfo['generated_at'] ?? 'N/A' }}
                                            @if(($monthHistoryInfo['daily_count'] ?? 0) > 0)
                                                · Registros guardados: {{ $monthHistoryInfo['daily_count'] }}
                                            @endif
                                        </small>
                                    @else
                                        <div>Pendiente de guardar</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted">USD</div>
                                    <div>Prom: <span class="fw-bold">{{ number_format($monthStats['usd']['avg'] ?? 0, 4) }}</span></div>
                                    <div>Min: <span class="fw-bold">{{ number_format($monthStats['usd']['min'] ?? 0, 4) }}</span></div>
                                    <div>Max: <span class="fw-bold">{{ number_format($monthStats['usd']['max'] ?? 0, 4) }}</span></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted">EUR</div>
                                    <div>Prom: <span class="fw-bold">
                                        {{ isset($monthStats['eur']['avg']) ? number_format($monthStats['eur']['avg'], 4) : 'N/A' }}
                                    </span></div>
                                    <div>Min: <span class="fw-bold">
                                        {{ isset($monthStats['eur']['min']) ? number_format($monthStats['eur']['min'], 4) : 'N/A' }}
                                    </span></div>
                                    <div>Max: <span class="fw-bold">
                                        {{ isset($monthStats['eur']['max']) ? number_format($monthStats['eur']['max'], 4) : 'N/A' }}
                                    </span></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted">Fuentes</div>
                                    <div>
                                        @if(!empty($monthStats['sources']))
                                            @foreach($monthStats['sources'] as $src)
                                                <span class="badge bg-label-info me-1">{{ $src }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>USD (Bs.)</th>
                                        <th>EUR (Bs.)</th>
                                        <th>Fuente</th>
                                        <th>Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthRates as $rate)
                                        <tr>
                                            <td>
                                                {{ ($rate->date instanceof \Carbon\Carbon ? $rate->date : \Carbon\Carbon::parse($rate->date))->format('d/m/Y') }}
                                            </td>
                                            <td>
                                                {{ $rate->usd_rate !== null ? number_format($rate->usd_rate, 4) : 'N/A' }}
                                            </td>
                                            <td>
                                                {{ $rate->eur_rate !== null ? number_format($rate->eur_rate, 4) : 'N/A' }}
                                            </td>
                                            <td>{{ $rate->source ?? 'N/A' }}</td>
                                            <td>
                                                @if($rate->fetch_time)
                                                    {{ $rate->fetch_time instanceof \Carbon\Carbon ? $rate->fetch_time->format('H:i') : \Carbon\Carbon::parse($rate->fetch_time)->format('H:i') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">Seleccione un mes y presione “Consultar” para ver el histórico.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    @if($showEditModal)
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ri ri-edit-line me-2"></i>
                            {{ 'Modificar Tasa Manual' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit="saveRate">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tasa USD <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Bs.</span>
                                        <input type="number"
                                               class="form-control @error('usd_rate') is-invalid @enderror"
                                               wire:model="usd_rate"
                                               step="0.0001"
                                               min="0.0001"
                                               max="999999.9999"
                                               placeholder="0.0000">
                                    </div>
                                    @error('usd_rate')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tasa EUR <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Bs.</span>
                                        <input type="number"
                                               class="form-control @error('eur_rate') is-invalid @enderror"
                                               wire:model="eur_rate"
                                               step="0.0001"
                                               min="0.0001"
                                               max="999999.9999"
                                               placeholder="0.0000">
                                    </div>
                                    @error('eur_rate')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Motivo de la {{ $editingRate ? 'edición' : 'creación' }} <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('edit_reason') is-invalid @enderror"
                                              wire:model="edit_reason"
                                              rows="3"
                                              placeholder="Explique el motivo de esta {{ $editingRate ? 'modificación' : 'creación manual' }}..."></textarea>
                                    @error('edit_reason')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </form>

                        @if($editingRate && isset($editingRate->raw_data['edited_by']))
                            <div class="alert alert-info mt-3">
                                <small>
                                    <strong>Última edición:</strong> {{ $editingRate->raw_data['edited_by'] ?? 'N/A' }}<br>
                                    <strong>Motivo:</strong> {{ $editingRate->raw_data['edit_reason'] ?? 'N/A' }}
                                </small>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeEditModal">
                            <i class="ri ri-close-line me-1"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" wire:click="saveRate">
                            <i class="ri ri-save-line me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
