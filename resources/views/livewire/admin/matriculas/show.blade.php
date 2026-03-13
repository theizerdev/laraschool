<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Detalles de Matrícula</h4>
            <p class="text-muted mb-0">Información detallada de la matrícula</p>
        </div>
        <div>
            <a href="{{ route('admin.matriculas.index') }}" class="btn btn-secondary">
                <i class="ri ri-arrow-left-line me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Datos del Estudiante</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4"><strong>Nombre:</strong></div>
                        <div class="col-sm-8">{{ optional($matricula->estudiante)->nombres ?? '' }} {{ optional($matricula->estudiante)->apellidos ?? '' }}</div>

                        <div class="col-sm-4"><strong>DNI:</strong></div>
                        <div class="col-sm-8">{{ optional($matricula->estudiante)->documento_identidad ?? '' }}</div>

                        <div class="col-sm-4"><strong>Email:</strong></div>
                        <div class="col-sm-8">{{ optional($matricula->estudiante)->correo_electronico ?? '' }}</div>

                        <div class="col-sm-4"><strong>Teléfono:</strong></div>
                        <div class="col-sm-8">{{ optional($matricula->estudiante)->telefono ?? '' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Datos de Matrícula</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4"><strong>Programa:</strong></div>
                        <div class="col-sm-8">{{ optional($matricula->programa)->nombre ?? '' }}</div>

                        <div class="col-sm-4"><strong>Período:</strong></div>
                        <div class="col-sm-8">{{ optional($matricula->periodo)->name ?? '' }}</div>

                        <div class="col-sm-4"><strong>Fecha:</strong></div>
                        <div class="col-sm-8">{{ format_date($matricula->fecha_matricula) }}</div>

                        <div class="col-sm-4"><strong>Estado:</strong></div>
                        <div class="col-sm-8">
                            @if($matricula->estado === 'activo')
                                <span class="badge bg-success">Activo</span>
                            @elseif($matricula->estado === 'inactivo')
                                <span class="badge bg-secondary">Inactivo</span>
                            @elseif($matricula->estado === 'graduado')
                                <span class="badge bg-primary">Graduado</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Información de Costos</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4"><strong>Costo Total:</strong></div>
                        <div class="col-sm-8">@money($matricula->costo)</div>

                        <div class="col-sm-4"><strong>Cuota Inicial:</strong></div>
                        <div class="col-sm-8">@money($matricula->cuota_inicial)</div>

                        <div class="col-sm-4"><strong>Número de Cuotas:</strong></div>
                        <div class="col-sm-8">{{ $matricula->numero_cuotas }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado Financiero / Solvencia -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4 border-{{ $matricula->solvente ? 'success' : 'warning' }}">
                <div class="card-header bg-{{ $matricula->solvente ? 'success' : 'warning' }} text-white">
                    <h5 class="mb-0">
                        <i class="ri ri-shield-{{ $matricula->solvente ? 'check' : 'warning' }}-line me-2"></i>
                        Estado Financiero - {{ $matricula->solvente ? 'SOLVENTE' : 'CON DEUDAS' }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-light">
                                <div class="text-muted small">Total Cuotas</div>
                                <div class="h3 mb-0 text-primary">@money($matricula->resumen_financiero['total_cuotas'])</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-light">
                                <div class="text-muted small">Total Pagado</div>
                                <div class="h3 mb-0 text-success">@money($matricula->resumen_financiero['total_pagado'])</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-light">
                                <div class="text-muted small">Total Pendiente</div>
                                <div class="h3 mb-0 text-danger">@money($matricula->resumen_financiero['total_pendiente'])</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded bg-light">
                                <div class="text-muted small">Progreso de Pago</div>
                                <div class="h3 mb-0 text-info">{{ $matricula->resumen_financiero['porcentaje_pagado'] }}%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="ri ri-checkbox-circle-line text-success me-2"></i>
                                <span class="me-3"><strong>Cuotas Pagadas:</strong> {{ $matricula->resumen_financiero['cuotas_pagadas'] }}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="ri ri-time-line text-warning me-2"></i>
                                <span><strong>Total Cuotas:</strong> {{ $matricula->resumen_financiero['cuotas_totales'] }}</span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            @if($matricula->resumen_financiero['cuotas_vencidas'] > 0)
                                <span class="badge bg-danger">
                                    <i class="ri ri-alert-line me-1"></i>
                                    {{ $matricula->resumen_financiero['cuotas_vencidas'] }} cuota(s) vencida(s)
                                </span>
                            @else
                                <span class="badge bg-success">
                                    <i class="ri ri-check-double-line me-1"></i>
                                    Sin cuotas vencidas
                                </span>
                            @endif
                        </div>
                    </div>

                    @if(!$matricula->solvente)
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="ri ri-error-warning-line me-1"></i>
                        <strong>Atención:</strong> Esta matrícula tiene pagos pendientes. El estudiante NO está solvente.
                    </div>
                    @else
                    <div class="alert alert-success mt-3 mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="ri ri-checkbox-circle-line me-1"></i>
                                <strong>¡Estudiante SOLVENTE!</strong> Puede generar y enviar constancia de solvencia.
                            </div>
                            <button 
                                wire:click="enviarNotificacionSolvencia" 
                                wire:loading.attr="disabled"
                                class="btn btn-success btn-sm"
                                title="Enviar constancia de solvencia por WhatsApp">
                                <i class="ri ri-whatsapp-line me-1"></i>
                                <span wire:loading.remove wire:target="enviarNotificacionSolvencia">Enviar Constancia</span>
                                <span wire:loading wire:target="enviarNotificacionSolvencia">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Enviando...
                                </span>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de amortización -->
    @if($matricula->paymentSchedules && $matricula->paymentSchedules->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tabla de Amortización</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Cuota</th>
                                    <th>Fecha de Vencimiento</th>
                                    <th>Monto</th>
                                    <th>Monto Pagado</th>
                                    <th>Saldo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matricula->paymentSchedules as $schedule)
                                <tr>
                                    <td>{{ $schedule->numero_cuota == 0 ? 'Inicial' : 'Cuota ' . $schedule->numero_cuota }}</td>
                                    <td>{{ format_date($schedule->fecha_vencimiento) }}</td>
                                    <td>@money($schedule->monto)</td>
                                    <td>@money($schedule->monto_pagado)</td>
                                    <td>@money($schedule->saldo_pendiente)</td>
                                    <td>
                                        @if($schedule->esta_pagado)
                                            <span class="badge bg-success">Pagado</span>
                                        @elseif($schedule->estado === 'vencido' || ($schedule->estado === 'pendiente' && $schedule->fecha_vencimiento < now()))
                                            <span class="badge bg-danger">Vencido</span>
                                        @elseif($schedule->monto_pagado > 0)
                                            <span class="badge bg-info">Parcial</span>
                                        @elseif($schedule->estado === 'pendiente')
                                            <span class="badge bg-warning">Pendiente</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($schedule->estado) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Historial de Pagos Realizados -->
    @if($matricula->pagos && $matricula->pagos->where('estado', 'aprobado')->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historial de Pagos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Comprobante</th>
                                    <th>Conceptos</th>
                                    <th>Método</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matricula->pagos->where('estado', 'aprobado') as $pago)
                                <tr>
                                    <td>{{ format_date($pago->fecha) }}</td>
                                    <td>
                                        <a href="{{ route('admin.pagos.print', $pago) }}" target="_blank" class="text-primary">
                                            {{ $pago->numero_completo ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($pago->detalles && $pago->detalles->count() > 0)
                                            @foreach($pago->detalles as $detalle)
                                                {{ $detalle->conceptoPago->nombre ?? 'N/A' }}
                                                @if(!$loop->last), @endif
                                            @endforeach
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($pago->metodo_pago ?? 'N/A') }}</td>
                                    <td>@money($pago->total)</td>
                                    <td>
                                        <span class="badge bg-success">Aprobado</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="d-flex justify-content-end gap-2">
        @can('edit matriculas')
        <a href="{{ route('admin.matriculas.edit', $matricula) }}" class="btn btn-primary">
            <i class="ri ri-pencil-line me-1"></i> Editar Matrícula
        </a>
        @endcan

        @can('delete matriculas')
        <button class="btn btn-danger" wire:click="delete" wire:confirm="¿Estás seguro de eliminar esta matrícula? Esta acción no se puede deshacer.">
            <i class="ri ri-delete-bin-line me-1"></i> Eliminar Matrícula
        </button>
        @endcan
    </div>
</div>