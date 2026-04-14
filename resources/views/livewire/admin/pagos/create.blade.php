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

    @if (session()->has('whatsapp_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri ri-whatsapp-line me-2"></i>
            {{ session('whatsapp_success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('whatsapp_error'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri ri-whatsapp-line me-2"></i>
            {{ session('whatsapp_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!$caja_abierta)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="ri ri-alert-line me-2"></i>
                <div>
                    <strong>¡Atención!</strong> No hay una caja abierta para el día de hoy.
                    <br><small>Los pagos se registrarán sin asociar a ninguna caja. Se recomienda abrir una caja antes de registrar pagos.</small>
                </div>
            </div>
            <div class="mt-2">
                <a href="{{ route('admin.cajas.create') }}" class="btn btn-sm btn-warning">
                    <i class="ri ri-safe-line me-1"></i> Abrir Caja
                </a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @else
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="ri ri-checkbox-circle-line me-2"></i>
                <div>
                    <strong>Caja Abierta:</strong> {{ $caja_abierta->fecha->format('d/m/Y') }}
                    <br><small>Monto inicial: ${{ number_format($caja_abierta->monto_inicial, 2) }} | Usuario: {{ $caja_abierta->usuario->name }}</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif



    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Nuevo Pago</h4>
            <div class="mt-2">
                <span class="badge bg-{{ $whatsappStatus === 'connected' ? 'success' : 'secondary' }} me-2">
                    <i class="ri ri-whatsapp-line me-1"></i>
                    WhatsApp: {{ $whatsappStatus === 'connected' ? 'Conectado' : 'Desconectado' }}
                </span>
                <button wire:click="checkWhatsAppStatus" class="btn btn-sm btn-outline-secondary" title="Refrescar estado">
                    <i class="ri ri-refresh-line"></i>
                </button>
            </div>
        </div>
        <a href="{{ route('admin.pagos.index') }}" class="btn btn-secondary">
            <i class="ri ri-arrow-left-line me-1"></i> Volver
        </a>
    </div>

    <!-- Información del Pago -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <i class="ri ri-file-text-line text-primary me-2" style="font-size: 1.2rem;"></i>
                        <h5 class="mb-0">Información del Pago</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Tipo y Número de Documento -->
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri ri-file-list-line text-info me-2"></i>
                                    <label class="form-label mb-0 fw-medium">Documento</label>
                                </div>
                                <select wire:model.change="tipo_pago" class="form-select form-select-sm mb-2 @error('tipo_pago') is-invalid @enderror">
                                    @foreach($tipos as $key => $tipo)
                                        <option value="{{ $key }}">{{ $tipo }}</option>
                                    @endforeach
                                </select>
                                <input type="text" value="{{ $numero_documento ?? 'Seleccione tipo' }}" class="form-control form-control-sm" readonly>
                                @if(!$serie_actual && $tipo_pago)
                                    <small class="text-danger mt-1">Sin series configuradas</small>
                                @endif
                                @error('tipo_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Fecha -->
                        <div class="col-lg-2 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri ri-calendar-line text-success me-2"></i>
                                    <label class="form-label mb-0 fw-medium">Fecha</label>
                                </div>
                                <input type="date" wire:model.change="fecha_pago" class="form-control form-control-sm @error('fecha_pago') is-invalid @enderror">
                                @error('fecha_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if($fecha_pago && $tasa_cambio)
                                <div class="alert alert-info mt-2 py-2 px-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-medium">Tasa según fecha</span>
                                        <span class="fw-bold">{{ number_format($tasa_cambio, 4, ',', '.') }} Bs/$</span>
                                    </div>
                                    <small class="text-muted">Fecha: {{ \Carbon\Carbon::parse($fecha_pago)->format('d/m/Y') }}</small>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Método de Pago -->
                        <div class="col-lg-3 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri ri-bank-card-line text-warning me-2"></i>
                                    <label class="form-label mb-0 fw-medium">Método de Pago</label>
                                </div>
                                <select wire:model.change="metodo_pago" class="form-select form-select-sm @error('metodo_pago') is-invalid @enderror">
                                    <option value="efectivo Bs.">Efectivo Bs.</option>
                                    <option value="efectivo Divisas.">Efectivo Divisas</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="punto de venta">Punto de Venta</option>
                                    <option value="pago mixto">Pago Mixto</option>
                                    <option value="pago movil">Pago Móvil</option>
                                </select>
                                @error('metodo_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Referencia -->
                        <div class="col-lg-2 col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri ri-hashtag text-secondary me-2"></i>
                                    <label class="form-label mb-0 fw-medium">Referencia</label>
                                </div>
                                <input type="text" wire:model.live="referencia" class="form-control form-control-sm @error('referencia') is-invalid @enderror" placeholder="Opcional" @if($es_pago_mixto) disabled @endif>
                                @error('referencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Monto Recibido (solo para efectivo) -->
                        @if(str_contains($metodo_pago, 'efectivo'))
                        <div class="col-lg-2 col-md-6">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri ri-money-dollar-circle-line text-success me-2"></i>
                                    <label class="form-label mb-0 fw-medium">Recibido</label>
                                </div>
                                <input type="number" step="0.01" wire:model.live="monto_recibido" class="form-control form-control-sm" placeholder="0.00">
                                @if($monto_recibido > $this->total)
                                    <div class="mt-2 p-2 bg-success bg-opacity-10 rounded">
                                        <small class="text-success fw-bold">Cambio: @money($this->cambio)</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuración de Pago Mixto -->
    @if($es_pago_mixto)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Configuración de Pago Mixto</h5>
                    <button type="button" wire:click="agregarMetodoPago" class="btn btn-sm btn-primary">
                        <i class="ri ri-add-line me-1"></i> Agregar Método
                    </button>
                </div>
                <div class="card-body">
                    @foreach($metodos_pago_mixto as $index => $metodo)
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Método de Pago</label>
                            <select wire:model="metodos_pago_mixto.{{ $index }}.metodo" class="form-select">
                                <option value="efectivo_dolares">Efectivo Dólares</option>
                                <option value="efectivo_bolivares">Efectivo Bolívares</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="pago_movil">Pago Móvil</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Monto ($)</label>
                            <input type="number" step="0.01" wire:model.live="metodos_pago_mixto.{{ $index }}.monto" class="form-control" placeholder="0.00">
                             @if(in_array($metodo['metodo'], ['transferencia', 'pago_movil', 'efectivo_bolivares']) && $metodo['monto'] > 0 && $tasa_cambio)
                                <small class="text-success mt-1 d-block">
                                    <strong>Bs. {{ number_format($metodo['monto'] * $tasa_cambio, 2, ',', '.') }}</strong>
                                </small>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Referencia</label>
                            <input type="text" 
                                   wire:model.live="metodos_pago_mixto.{{ $index }}.referencia" 
                                   class="form-control @error('metodos_pago_mixto.' . $index . '.referencia') is-invalid @enderror" 
                                   placeholder="Número de referencia">
                            @error('metodos_pago_mixto.' . $index . '.referencia')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2">
                            @if(count($metodos_pago_mixto) > 1)
                            <button type="button" wire:click="eliminarMetodoPago({{ $index }})" class="btn btn-danger btn-sm">
                                <i class="ri ri-delete-bin-line"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="alert alert-info">
                                <strong>Total Configurado:</strong> ${{ number_format($this->totalPagoMixto, 2) }}
                                 @if($tasa_cambio != null)
                                <small class="text-success mt-1 d-block">
                                   <strong>Total en Bolívares:</strong> <strong>Bs. {{ number_format($this->totalPagoMixto * $tasa_cambio, 2) }}</strong>
                                </small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert {{ $this->totalPagoMixto == $this->total ? 'alert-success' : 'alert-warning' }}">
                                <strong>Total a Pagar:</strong> ${{ number_format($this->total, 2) }}
                                @if($tasa_cambio != null)
                                <small class="text-success mt-1 d-block">
                                   <strong>Total en Bolívares:</strong> <strong>Bs. {{ number_format($this->total * $tasa_cambio, 2) }}</strong>
                                </small>
                                @endif
                                @if($this->totalPagoMixto != $this->total)
                                    <br><small>Los montos no coinciden</small>
                                 @else
                                    <br><small class="text-success">Los montos coinciden</small>
                                @endif
                            </div>
                        </div>
                        @if($tasa_cambio != null)
                        <div class="col-md-4">
                            <div class="alert alert-secondary">
                                <strong>Tasa del día:</strong> {{ number_format($tasa_cambio, 4) }} Bs/$
                                <br><small class="text-muted">Para transferencias y pago móvil</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Selección de matrícula y cuotas -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Seleccionar Estudiante</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" wire:click="aplicarPlantilla('mensualidad')" class="btn btn-outline-primary" title="Plantilla Mensualidad">
                            <i class="ri ri-calendar-line"></i>
                        </button>
                        <button type="button" wire:click="aplicarPlantilla('inscripcion')" class="btn btn-outline-success" title="Plantilla Inscripción">
                            <i class="ri ri-user-add-line"></i>
                        </button>
                        <button type="button" wire:click="aplicarPlantilla('materiales')" class="btn btn-outline-info" title="Plantilla Materiales">
                            <i class="ri ri-book-line"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Buscar Estudiante *</label>
                        <div class="position-relative">
                            <input type="text"
                                   wire:model.live.debounce.300ms="busqueda_estudiante"
                                   class="form-control @error('matricula_id') is-invalid @enderror"
                                   placeholder="Buscar por nombre, documento o código..."
                                   autocomplete="off">
                            <i class="ri ri-search-line position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                        </div>
                        @error('matricula_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        @if($busqueda_estudiante && count($matriculas_filtradas) > 0)
                        <div class="border rounded mt-2" style="max-height: 200px; overflow-y: auto;">
                            @foreach($matriculas_filtradas as $matricula)
                            <div class="p-2 border-bottom cursor-pointer hover-bg-light"
                                 wire:click="seleccionarMatricula({{ $matricula->id }})"
                                 style="cursor: pointer;"
                                 onmouseover="this.style.backgroundColor='#f8f9fa'"
                                 onmouseout="this.style.backgroundColor='transparent'">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        @if($matricula->student->foto)
                                            <img src="{{ asset('storage/' . $matricula->student->foto) }}" alt="Foto" class="rounded-circle" width="32" height="32">
                                        @else
                                            <div class="avatar-initial bg-primary rounded-circle">
                                                {{ substr($matricula->student->nombres, 0, 1) }}{{ substr($matricula->student->apellidos, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium">{{ $matricula->student->nombres }} {{ $matricula->student->apellidos }}</div>
                                        <small class="text-muted">{{ $matricula->student->documento_identidad }} • {{ $matricula->programa->nombre }}</small><br>
                                        <small class="text-muted">{{ $matricula->periodo->name  }} • {{ $matricula->periodo->start_date->format('d/m/Y') }} - {{ $matricula->periodo->end_date->format('d/m/Y') }}</small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @elseif($busqueda_estudiante && count($matriculas_filtradas) === 0)
                        <div class="alert alert-warning mt-2 mb-0">
                            <i class="ri ri-search-line me-1"></i> No se encontraron estudiantes con "{{ $busqueda_estudiante }}"
                        </div>
                        @endif
                    </div>

                    @if($matricula_id)
                        @php $matricula = $matriculas->firstWhere('id', $matricula_id); @endphp
                        @if($matricula)
                        <div class="card border-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar me-3">
                                        @if($matricula->student->foto)
                                            <img src="{{ asset('storage/' . $matricula->student->foto) }}" alt="Foto" class="rounded-circle" width="48" height="48">
                                        @else
                                            <div class="avatar-initial bg-primary rounded-circle">
                                                {{ substr($matricula->student->nombres, 0, 1) }}{{ substr($matricula->student->apellidos, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">{{ $matricula->student->nombres }} {{ $matricula->student->apellidos }}</h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-info">{{ $matricula->student->documento_identidad }}</span>
                                            <span class="badge bg-success">Activa</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="ri ri-graduation-cap-line text-primary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Programa</small>
                                                <span class="fw-medium">{{ $matricula->programa->nombre }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="ri ri-calendar-line text-success me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Fecha Matrícula</small>
                                                <span class="fw-medium">{{ format_date($matricula->fecha_matricula) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="ri ri-money-dollar-circle-line text-warning me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Costo Total</small>
                                                <span class="fw-bold text-primary">@money($matricula->costo ?? 0)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="ri ri-user-line text-info me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Código Estudiante</small>
                                                <span class="fw-medium">{{ $matricula->student->codigo ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="ri ri-calendar-line text-success me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Periodo</small>
                                                <span class="fw-medium">{{ $matricula->periodo->name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="ri ri-calendar-line text-success me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Inicio - Fin</small>
                                                <span class="fw-medium">{{ $matricula->periodo->start_date->format('d/m/Y') .' - '. $matricula->periodo->end_date->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($matricula->student->telefono || $matricula->student->email)
                                <div class="border-top pt-3 mt-3">
                                    <div class="row g-2">
                                        @if($matricula->student->telefono)
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <i class="ri ri-phone-line text-success me-2"></i>
                                                <small>{{ $matricula->student->telefono }}</small>
                                            </div>
                                        </div>
                                        @endif
                                        @if($matricula->student->email)
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <i class="ri ri-mail-line text-primary me-2"></i>
                                                <small>{{ $matricula->student->email }}</small>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        @endif

                        {{-- Historial de pagos --}}
                        @if($matricula_id && count($pagos_anteriores) > 0)
                        <div class="card mt-3">
                            <div class="card-header py-2">
                                <h6 class="mb-0"><i class="ri ri-history-line me-1"></i> Últimos Pagos</h6>
                            </div>
                            <div class="card-body py-2">
                                @foreach($pagos_anteriores as $pago)
                                <div class="d-flex justify-content-between align-items-center py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div>
                                        <small class="fw-medium">{{ $pago->created_at->format('d/m/Y') }}</small>
                                        <br><small class="text-muted">{{ $pago->numero_completo }}</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold text-success">@money($pago->total)</span>
                                        <br><small class="text-muted">{{ ucfirst($pago->metodo_pago) }}</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="border rounded p-4 text-center">
                            <i class="ri ri-user-search-line ri ri-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Seleccione una matrícula</p>
                            <small class="text-muted">para ver la información del estudiante</small>
                        </div>
                    @endif
                </div>
            </div>
          </div>
          <div class="col-lg-6 mb-4">
              @if($matricula_id && count($cuotasPendientes) > 0)
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Cuotas Pendientes</h6>
                </div>
                <div class="card-body p-0">
                    <div class="cuotas-scroll" style="max-height: 21.875rem; overflow-y: auto;">
                        <div class="p-3">
                            @foreach($cuotasPendientes as $cuota)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h6 class="mb-1">Cuota #{{ $cuota->numero_cuota }}</h6>
                                        <small class="text-muted">{{ format_date($cuota->fecha_vencimiento, 'M Y') }}</small>
                                        @if($cuota->fecha_vencimiento < now())
                                            <span class="badge bg-label-danger ms-2">Vencida</span>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-primary">@money($cuota->saldo_pendiente)</div>
                                        @if($cuota->recargo_morosidad > 0)
                                            <div class="text-danger small">+ Recargo: @money($cuota->recargo_morosidad)</div>
                                            <div class="fw-bold text-success">Total: @money($cuota->monto_con_recargo)</div>
                                        @endif
                                        @if($cuota->saldo_pendiente != $cuota->monto)
                                            <small class="text-muted">Original: @money($cuota->monto)</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button wire:click="seleccionarCuota({{ $cuota->id }})" class="btn btn-sm btn-primary flex-fill">
                                        <i class="ri ri-add-line"></i> Pagar Completa
                                    </button>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Abono
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" wire:click="agregarAbono({{ $cuota->id }}, {{ $cuota->saldo_pendiente * 0.5 }})">50%</a></li>
                                            <li><a class="dropdown-item" href="#" wire:click="agregarAbono({{ $cuota->id }}, {{ $cuota->saldo_pendiente * 0.25 }})">25%</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="promptAbono({{ $cuota->id }}, {{ $cuota->saldo_pendiente }})">Personalizado</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
          </div>

        <!-- Carrito de pagos -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detalle del Pago</h5>
                    <button wire:click="agregarDetalle" class="btn btn-sm btn-outline-primary">
                        <i class="ri ri-add-line"></i> Agregar
                    </button>
                </div>
                <div class="card-body">
                    @if(count($detalles) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Descripción</th>
                                        <th width="120">Cant.</th>
                                        <th width="150">Precio</th>
                                        <th width="100">Total</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($detalles as $index => $detalle)
                                    <tr>
                                        <td>
                                            <select wire:model="detalles.{{ $index }}.concepto_pago_id" class="form-select form-select-sm">
                                                <option value="">Seleccionar...</option>
                                                @foreach($conceptos as $concepto)
                                                    <option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" wire:model="detalles.{{ $index }}.descripcion" class="form-control form-control-sm" placeholder="Descripción">
                                        </td>
                                        <td>
                                            <input type="number" wire:model.blur="detalles.{{ $index }}.cantidad" class="form-control" min="1" step="1">
                                        </td>
                                        <td>
                                            <input type="number" wire:model.blur="detalles.{{ $index }}.precio_unitario" class="form-control" min="0" step="0.01">
                                        </td>
                                        <td class="text-end">
                                            @money($this->calcularSubtotal($index))
                                        </td>
                                        <td>
                                            <button wire:click="eliminarDetalle({{ $index }})" class="btn btn-sm btn-outline-danger">
                                                <i class="ri ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Totales -->
                        <div class="border-top pt-3 mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Descuento</label>
                                        <input type="number" wire:model.blur="descuento" class="form-control" min="0" step="0.01">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Observaciones</label>
                                        <textarea wire:model="observaciones" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-gradient-primary text-white mb-3">
                                        <div class="card-body text-center py-3">
                                            <h3 class="mb-1">@money($this->total)</h3>
                                            <p class="mb-0 opacity-75">Total a Pagar</p>
                                            @if($tasa_cambio != null)
                                                <div class="mt-2">
                                                    <small class="opacity-75 d-block">Tasa: {{ number_format($tasa_cambio, 4, ',', '.') }} Bs/$</small>
                                                    <h5 class="mb-0 text-warning">Bs. {{ number_format($this->totalBolivares, 2, ',', '.') }}</h5>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="bg-light p-3 rounded">
                                      
                                        @if($descuento > 0)
                                        <div class="d-flex justify-content-between mb-2 text-danger">
                                            <span>Descuento:</span>
                                            <span>-@money($descuento)</span>
                                        </div>
                                        @if($tasa_cambio && $descuento > 0)
                                        <div class="d-flex justify-content-between mb-2 text-danger">
                                            <small class="text-muted">Descuento Bs:</small>
                                            <small>-Bs. {{ number_format($this->descuentoBolivares, 2, ',', '.') }}</small>
                                        </div>
                                        @endif
                                        @endif
                                        
                                        @if($tasa_cambio != null)
                                        <div class="d-flex justify-content-between fw-bold fs-5 mt-2 text-success">
                                            <span>Total Bs:</span>
                                            <span>Bs. {{ number_format($this->totalBolivares, 2, ',', '.') }}</span>
                                        </div>
                                        @endif
                                        @if($mostrar_bolivares && $tasa_cambio && !$es_pago_mixto)
                                        <div class="mt-2 pt-2 border-top">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="text-muted">Tasa del día:</small>
                                                <small class="text-muted">{{ number_format($tasa_cambio, 4, ',', '.') }} Bs/$</small>
                                            </div>
                                            <div class="d-flex justify-content-between fw-bold text-success">
                                                <span>Total Bolívares:</span>
                                                <span>Bs. {{ number_format($this->totalBolivares, 2, ',', '.') }}</span>
                                            </div>
                                        </div>
                                        @endif
                                        
                                        @if($es_pago_mixto)
                                        <div class="mt-2 pt-2 border-top">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="text-muted">Configurado:</small>
                                                <small class="text-muted">@money($this->totalPagoMixto)</small>
                                            </div>
                                            <div class="d-flex justify-content-between fw-bold {{ $this->totalPagoMixto == $this->total ? 'text-success' : 'text-warning' }}">
                                                <span>Estado:</span>
                                                <span>{{ $this->totalPagoMixto == $this->total ? 'Completo' : 'Pendiente' }}</span>
                                            </div>
                                        </div>
                                        @endif
                                    </div>

                                    <button wire:click="guardar"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50"
                                            class="btn btn-success w-100 mt-3"
                                            @if($this->total <= 0 || !$matricula_id || !$this->serie_actual || ($es_pago_mixto && $this->totalPagoMixto != $this->total)) disabled @endif>
                                        <span wire:loading.remove wire:target="guardar">
                                            <i class="ri ri-save-line me-1"></i>
                                            @if($es_pago_mixto && $this->totalPagoMixto != $this->total)
                                                Ajustar Montos
                                            @else
                                                Registrar Pago (Ctrl+S)
                                            @endif
                                        </span>
                                        <span wire:loading wire:target="guardar">
                                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                            Procesando...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ri ri-shopping-cart-2-line ri ri-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No hay conceptos agregados</p>
                            <p class="text-muted small">Seleccione cuotas o agregue conceptos manualmente</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.cuotas-scroll {
    scrollbar-width: thin;
    scrollbar-color: #6c757d #f8f9fa;
}

.cuotas-scroll::-webkit-scrollbar {
    width: .5rem;
}

.cuotas-scroll::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: .25rem;
}

.cuotas-scroll::-webkit-scrollbar-thumb {
    background: #6c757d;
    border-radius: .25rem;
}

.cuotas-scroll::-webkit-scrollbar-thumb:hover {
    background: #495057;
}
</style>
@endpush

@push('scripts')
<script>
function promptAbono(cuotaId, saldoMaximo) {
    const monto = prompt(`Ingrese el monto del abono (máximo: ${saldoMaximo.toFixed(2)}):`);
    if (monto && !isNaN(monto) && parseFloat(monto) > 0 && parseFloat(monto) <= saldoMaximo) {
        @this.call('agregarAbono', cuotaId, parseFloat(monto));
    } else if (monto) {
        alert('Monto inválido. Debe ser mayor a 0 y no exceder el saldo pendiente.');
    }
}

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl+S para guardar
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        @this.call('guardar');
    }
    // F2 para enfocar búsqueda
    if (e.key === 'F2') {
        e.preventDefault();
        const searchInput = document.querySelector('[wire\\:model*="busqueda_estudiante"]');
        if (searchInput) searchInput.focus();
    }
    // F3 para agregar detalle
    if (e.key === 'F3') {
        e.preventDefault();
        @this.call('agregarDetalle');
    }
});

// Notificaciones
document.addEventListener('livewire:init', function () {
    Livewire.on('pago-registrado', (event) => {
        // Mostrar notificación de éxito
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Pago Registrado!',
                text: event.mensaje,
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
});
</script>
@endpush