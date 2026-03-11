<div>
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">WhatsApp Dashboard</h4>
                        <p class="text-muted mb-0">Resumen general de tu conexión WhatsApp Business API</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button wire:click="refresh" class="btn btn-primary" {{ $isLoading ? 'disabled' : '' }}>
                            @if($isLoading)
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Actualizando...
                            @else
                                <i class="ri-refresh-line me-2"></i>
                                Actualizars
                            @endif
                        </button>
                        <a href="{{ route('admin.whatsapp.connection') }}" class="btn btn-outline-primary">
                            <i class="ri-link me-2"></i>
                            Gestionar Conexión
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded bg-{{ $this->statusColor }}">
                                    <i class="{{ $this->statusIcon }} mdi-24px"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1">Estado de Conexión</h5>
                                <h3 class="mb-0 text-{{ $this->statusColor }}">
                                    {{ ucfirst($status) }}
                                </h3>
                                @if($user)
                                    <small class="text-muted">
                                        <i class="ri-user-line me-1"></i>
                                        {{ $user['name'] ?? 'Usuario' }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-6">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-primary">
                                        <i class="ri-send-plane-line mdi-20px"></i>
                                    </span>
                                </div>
                                <h4 class="mb-1">{{ $stats['sent'] }}</h4>
                                <p class="text-muted mb-0">Mensajes Enviados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-success">
                                        <i class="ri-inbox-line mdi-20px"></i>
                                    </span>
                                </div>
                                <h4 class="mb-1">{{ $stats['received'] }}</h4>
                                <p class="text-muted mb-0">Mensajes Recibidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-danger">
                                        <i class="ri-error-warning-line mdi-20px"></i>
                                    </span>
                                </div>
                                <h4 class="mb-1">{{ $stats['failed'] }}</h4>
                                <p class="text-muted mb-0">Mensajes Fallidos</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-info">
                                        <i class="ri-bar-chart-2-line mdi-20px"></i>
                                    </span>
                                </div>
                                <h6 class="mb-1">Enviados (servicio)</h6>
                                <h5 class="mb-0">{{ number_format($metrics['messagesSent']) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-warning">
                                        <i class="ri-alert-line mdi-20px"></i>
                                    </span>
                                </div>
                                <h6 class="mb-1">Fallidos (servicio)</h6>
                                <h5 class="mb-0">{{ number_format($metrics['messagesFailed']) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-secondary">
                                        <i class="ri-time-line mdi-20px"></i>
                                    </span>
                                </div>
                                <h6 class="mb-1">p95 Latencia (s)</h6>
                                <h5 class="mb-0">{{ $metrics['latencyP95'] !== null ? number_format($metrics['latencyP95'], 2) : 'N/A' }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Messages -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="ri-message-3-line me-2"></i>
                                Mensajes Recientes
                            </h5>
                            <a href="{{ route('admin.whatsapp.send-messages') }}" class="btn btn-sm btn-outline-primary">
                                <i class="ri-add-line me-1"></i>
                                Nuevo Mensaje
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(count($recentMessages) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>De/Para</th>
                                            <th>Mensaje</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentMessages as $message)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="ri-{{ $message['direction'] === 'outbound' ? 'send-plane' : 'inbox' }}-line me-2"></i>
                                                        {{ $message['from'] ?? $message['to'] ?? 'Desconocido' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 300px;">
                                                        {{ $message['body'] ?? 'Sin contenido' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ 
                                                        $message['status'] === 'sent' ? 'primary' : 
                                                        ($message['status'] === 'delivered' ? 'success' : 
                                                        ($message['status'] === 'failed' ? 'danger' : 'secondary'))
                                                    }}">
                                                        {{ ucfirst($message['status'] ?? 'unknown') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($message['created_at'] ?? now())->diffForHumans() }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="ri-inbox-line ri-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay mensajes recientes</p>
                               
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h5 class="card-title mb-0">
                            <i class="ri-lightbulb-line me-2"></i>
                            Acciones Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <a href="{{ route('admin.whatsapp.connection') }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-3">
                                    <i class="ri-link me-2"></i>
                                    <div class="text-start">
                                        <div class="fw-bold">Conexión</div>
                                        <small class="text-muted">Gestionar conexión</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="{{ route('admin.whatsapp.send-messages') }}" class="btn btn-outline-success w-100 d-flex align-items-center justify-content-center py-3">
                                    <i class="ri-send-plane-line me-2"></i>
                                    <div class="text-start">
                                        <div class="fw-bold">Enviar Mensajes</div>
                                        <small class="text-muted">Nuevo mensaje</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="{{ route('admin.whatsapp.templates.index') }}" class="btn btn-outline-info w-100 d-flex align-items-center justify-content-center py-3">
                                    <i class="ri-file-text-line me-2"></i>
                                    <div class="text-start">
                                        <div class="fw-bold">Plantillas</div>
                                        <small class="text-muted">Gestionar plantillas</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="{{ route('admin.whatsapp.history') }}" class="btn btn-outline-warning w-100 d-flex align-items-center justify-content-center py-3">
                                    <i class="ri-history-line me-2"></i>
                                    <div class="text-start">
                                        <div class="fw-bold">Historial</div>
                                        <small class="text-muted">Ver historial completo</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Messages -->
    @if (session()->has('message'))
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ri-check-line me-2"></i>
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif
</div>
