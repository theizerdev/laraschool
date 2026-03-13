<div>
    <!-- Header con Estado Principal -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-border-shadow-{{ $statusColor }}">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-lg">
                                <span class="avatar-initial rounded-circle bg-label-{{ $statusColor }}">
                                    <i class="{{ $statusIcon }} ri-24px"></i>
                                </span>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h4 class="mb-0">WhatsApp Business</h4>
                                    <span class="badge bg-{{ $statusColor }}">{{ $statusText }}</span>
                                </div>
                                @if($user)
                                    <p class="mb-0 text-muted">
                                        <i class="ri ri-phone-line me-1"></i>{{ $user['id'] ?? 'N/A' }}
                                        <span class="mx-2">•</span>
                                        <i class="ri ri-user-line me-1"></i>{{ $user['name'] ?? 'Usuario' }}
                                    </p>
                                @elseif($connectionError)
                                    <p class="mb-0 text-danger small">
                                        <i class="ri ri-error-warning-line me-1"></i>{{ $connectionError }}
                                    </p>
                                @else
                                    <p class="mb-0 text-muted">No hay sesión activa de WhatsApp</p>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button wire:click="refresh" class="btn btn-label-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="refresh">
                                    <i class="ri ri-refresh-line me-1"></i>Actualizar
                                </span>
                                <span wire:loading wire:target="refresh">
                                    <span class="spinner-border spinner-border-sm me-1"></span>Actualizando...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    @php
        $totalMsgs = $stats['total'] > 0 ? $stats['total'] : 1;
        $sentPercent = ($stats['sent'] / $totalMsgs) * 100;
        $deliveredPercent = ($stats['delivered'] / $totalMsgs) * 100;
        $readPercent = ($stats['read'] / $totalMsgs) * 100;
        $failedPercent = ($stats['failed'] / $totalMsgs) * 100;
    @endphp
    <div class="row g-4 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ri ri-send-plane-fill ri-24px"></i>
                            </span>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $stats['sent'] }}</h4>
                            <span class="text-muted small">Enviados</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: {{ $sentPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ri ri-check-double-fill ri-24px"></i>
                            </span>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $stats['delivered'] }}</h4>
                            <span class="text-muted small">Entregados</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: {{ $deliveredPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-3">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ri ri-eye-fill ri-24px"></i>
                            </span>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $stats['read'] }}</h4>
                            <span class="text-muted small">Leídos</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-info" style="width: {{ $readPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-3">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ri ri-error-warning-fill ri-24px"></i>
                            </span>
                        </div>
                        <div>
                            <h4 class="mb-0">{{ $stats['failed'] }}</h4>
                            <span class="text-muted small">Fallidos</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-danger" style="width: {{ $failedPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="nav-align-top mb-4">
        <ul class="nav nav-pills nav-fill flex-column flex-sm-row mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link {{ $activeTab === 'dashboard' ? 'active' : '' }}"
                        wire:click="setActiveTab('dashboard')">
                    <i class="ri ri-dashboard-line me-1 ri-20px"></i>
                    <span class="d-none d-sm-inline">Dashboard</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button"
                        class="nav-link {{ $activeTab === 'conexion' ? 'active' : '' }}"
                        wire:click="setActiveTab('conexion')">
                    <i class="ri ri-link me-1 ri-20px"></i>
                    <span class="d-none d-sm-inline">Conexión</span>
                    @if($status !== 'connected')
                        <span class="badge bg-danger ms-1">!</span>
                    @endif
                </button>
            </li>
            
        </ul>

        <!-- Tab Content -->
        <div class="tab-content p-0">
            <!-- Dashboard Tab -->
            @if($activeTab === 'dashboard')
                <div class="tab-pane fade show active">
                    <div class="row g-4">
                        <!-- Información de Cuenta -->
                        @if($user && $status === 'connected')
                            <div class="col-lg-4">
                                <div class="card h-100">
                                    <div class="card-header pb-0">
                                        <h5 class="card-title mb-0">
                                            <i class="ri ri-account-circle-line me-2"></i>Cuenta Conectada
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex flex-column align-items-center text-center mb-4">
                                            <div class="avatar avatar-xl mb-3">
                                                <span class="avatar-initial rounded-circle bg-success">
                                                    <i class="ri ri-whatsapp-line ri-36px"></i>
                                                </span>
                                            </div>
                                            <h5 class="mb-1">{{ $user['name'] ?? 'Usuario' }}</h5>
                                            <span class="text-muted">{{ $user['id'] ?? 'N/A' }}</span>
                                        </div>

                                        <div class="info-container">
                                            <ul class="list-unstyled mb-0">
                                                <li class="mb-3 d-flex align-items-center">
                                                    <i class="ri ri-checkbox-circle-fill text-success me-2"></i>
                                                    <span class="fw-medium me-1">Estado:</span>
                                                    <span class="text-success">Activo</span>
                                                </li>
                                                @if($lastSeen)
                                                <li class="mb-3 d-flex align-items-center">
                                                    <i class="ri ri-time-line text-muted me-2"></i>
                                                    <span class="fw-medium me-1">Última vez:</span>
                                                    <span>{{ \Carbon\Carbon::parse($lastSeen)->diffForHumans() }}</span>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Mensajes Recientes -->
                        <div class="{{ $user && $status === 'connected' ? 'col-lg-8' : 'col-12' }}">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="ri ri-chat-3-line me-2"></i>Mensajes Recientes
                                    </h5>
                                    @if($status === 'connected')
                                        <button class="btn btn-sm btn-primary" wire:click="setActiveTab('mensajes')">
                                            <i class="ri ri-add-line me-1"></i>Nuevo
                                        </button>
                                    @endif
                                </div>
                                <div class="card-body p-0">
                                    @if(count($messages) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-nowrap">Fecha</th>
                                                        <th class="text-nowrap">Contacto</th>
                                                        <th>Mensaje</th>
                                                        <th class="text-center">Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($messages as $message)
                                                        <tr>
                                                            <td class="text-nowrap">
                                                                <small class="text-muted">
                                                                    {{ \Carbon\Carbon::parse($message['createdAt'] ?? now())->format('d/m H:i') }}
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    @if(($message['status'] ?? '') === 'sent')
                                                                        <span class="badge bg-label-primary me-2">
                                                                            <i class="ri ri-arrow-right-up-line"></i>
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-label-success me-2">
                                                                            <i class="ri ri-arrow-left-down-line"></i>
                                                                        </span>
                                                                    @endif
                                                                    <span>{{ $message['to'] ?? $message['from'] ?? 'Desconocido' }}</span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 250px;">
                                                                    {{ Str::limit($message['message'] ?? $message['body'] ?? '', 50) }}
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                @php
                                                                    $msgStatus = $message['status'] ?? 'unknown';
                                                                    $badgeConfig = match($msgStatus) {
                                                                        'sent' => ['class' => 'bg-primary', 'icon' => 'ri-check-line', 'label' => 'Enviado'],
                                                                        'delivered' => ['class' => 'bg-success', 'icon' => 'ri-check-double-line', 'label' => 'Entregado'],
                                                                        'read' => ['class' => 'bg-info', 'icon' => 'ri-eye-line', 'label' => 'Leído'],
                                                                        'failed' => ['class' => 'bg-danger', 'icon' => 'ri-close-line', 'label' => 'Fallido'],
                                                                        'pending' => ['class' => 'bg-warning', 'icon' => 'ri-time-line', 'label' => 'Pendiente'],
                                                                        default => ['class' => 'bg-secondary', 'icon' => 'ri-question-line', 'label' => ucfirst($msgStatus)]
                                                                    };
                                                                @endphp
                                                                <span class="badge {{ $badgeConfig['class'] }}" title="{{ $badgeConfig['label'] }}">
                                                                    <i class="{{ $badgeConfig['icon'] }} me-1"></i>{{ $badgeConfig['label'] }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <div class="avatar avatar-lg mb-3">
                                                <span class="avatar-initial rounded-circle bg-label-secondary">
                                                    <i class="ri ri-chat-off-line ri-24px"></i>
                                                </span>
                                            </div>
                                            <h6 class="mb-1">No hay mensajes</h6>
                                            <p class="text-muted mb-3">Los mensajes recientes aparecerán aquí</p>
                                            @if($status === 'connected')
                                                <button class="btn btn-primary btn-sm" wire:click="setActiveTab('mensajes')">
                                                    <i class="ri ri-send-plane-line me-1"></i>Enviar mensaje
                                                </button>
                                            @elseif($status !== 'connected')
                                                <button class="btn btn-success btn-sm" wire:click="setActiveTab('conexion')">
                                                    <i class="ri ri-link me-1"></i>Conectar WhatsApp
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones Rápidas -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card bg-transparent shadow-none border">
                                <div class="card-body">
                                    <h6 class="mb-3">
                                        <i class="ri ri-flashlight-line me-1"></i>Acciones Rápidas
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-6 col-md-3">
                                            <button class="btn btn-label-primary w-100 py-3" wire:click="setActiveTab('conexion')">
                                                <i class="ri ri-link d-block ri-24px mb-1"></i>
                                                <span class="d-block small">Conexión</span>
                                            </button>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <button class="btn btn-label-success w-100 py-3"
                                                    wire:click="setActiveTab('mensajes')"
                                                    @if($status !== 'connected') disabled @endif>
                                                <i class="ri ri-send-plane-line d-block ri-24px mb-1"></i>
                                                <span class="d-block small">Enviar</span>
                                            </button>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <button class="btn btn-label-info w-100 py-3" wire:click="refresh">
                                                <i class="ri ri-refresh-line d-block ri-24px mb-1"></i>
                                                <span class="d-block small">Actualizar</span>
                                            </button>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <a href="{{ route('admin.dashboard') }}" class="btn btn-label-secondary w-100 py-3">
                                                <i class="ri ri-arrow-left-line d-block ri-24px mb-1"></i>
                                                <span class="d-block small">Volver</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Conexión Tab -->
            @if($activeTab === 'conexion')
                <div class="tab-pane fade show active">
                    <livewire:admin.whatsapp.conexion :key="'conexion-'.now()" />
                </div>
            @endif

            <!-- Mensajes Tab -->
            @if($activeTab === 'mensajes')
                <div class="tab-pane fade show active">
                    @if($status === 'connected')
                        <livewire:admin.whatsapp.envio-mensajes :key="'envio-'.now()" />
                    @else
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <div class="avatar avatar-lg mb-3">
                                    <span class="avatar-initial rounded-circle bg-label-warning">
                                        <i class="ri ri-wifi-off-line ri-24px"></i>
                                    </span>
                                </div>
                                <h5 class="mb-2">WhatsApp no conectado</h5>
                                <p class="text-muted mb-3">Necesitas conectar WhatsApp antes de enviar mensajes</p>
                                <button class="btn btn-success" wire:click="setActiveTab('conexion')">
                                    <i class="ri ri-link me-1"></i>Ir a Conexión
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Loading Overlay -->
    <div wire:loading.flex wire:target="refresh, loadDashboard" class="position-fixed top-0 start-0 w-100 h-100 justify-content-center align-items-center" style="background: rgba(255,255,255,0.7); z-index: 1050;">
        <div class="text-center">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <p class="mb-0">Cargando...</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('notify', (data) => {
            if (typeof toastr !== 'undefined') {
                toastr[data.type](data.message);
            }
        });
    });


</script>
@endpush
