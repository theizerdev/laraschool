<?php

namespace App\Livewire\Admin\Pagos;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\Pago;
use App\Models\Matricula;
use App\Models\ConceptoPago;
use App\Models\PaymentSchedule;
use App\Models\Serie;
use App\Models\Caja;
use App\Services\PagoService;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use DB;
class Create extends Component
{
    use HasDynamicLayout, HasRegionalFormatting;


    public $matricula_id;
    public $tipo_pago = 'recibo';
    public $fecha;
    public $metodo_pago = 'efectivo';
    public $referencia;
    public $descuento = 0;
    public $observaciones;
    public $detalles = [];
    public $tasa_cambio;
    public $mostrar_bolivares = false;

    // Propiedades para pago mixto
    public $es_pago_mixto = false;
    public $metodos_pago_mixto = [];

    // Propiedades adicionales para la vista
    public $fecha_pago;
    public $concepto_id;
    public $monto;

    public $matriculas = [];
    public $conceptos = [];
    public $cuotasPendientes = [];
    public $serie_actual;
    public $numero_documento;
    public $caja_abierta;

    // Propiedades para búsqueda
    public $busqueda_estudiante = '';
    public $matriculas_filtradas = [];

    // Propiedades para mejoras
    public $monto_recibido = 0;
    public $pagos_anteriores = [];
    public $plantillas_pago = [];
    public $whatsappStatus = 'disconnected';

    protected $rules = [
        'matricula_id' => 'required|exists:matriculas,id',
        'tipo_pago' => 'required|in:factura,boleta,nota_credito,recibo,comunidad educativa,educacion adulto',
        'fecha' => 'required|date',
        'fecha_pago' => 'required|date',
        'metodo_pago' => 'required',
        'detalles' => 'required|array|min:1',
        'detalles.*.concepto_pago_id' => 'required|exists:conceptos_pago,id',
        'detalles.*.descripcion' => 'required|string',
        'detalles.*.cantidad' => 'required|numeric|min:0.01',
        'detalles.*.precio_unitario' => 'required|numeric|min:0',
        'metodos_pago_mixto.*.metodo' => 'required',
        'metodos_pago_mixto.*.monto' => 'required|numeric|min:0',
    ];



    public function mount()
    {
        $this->fecha = now()->format('Y-m-d');
        $this->fecha_pago = now()->format('Y-m-d');
        $this->cargarTasaCambio();
        $this->cargarDatos();
        $this->verificarCajaAbierta();
        $this->agregarDetalle();
        $this->inicializarPagoMixto();
        $this->cargarPlantillasPago();
        $this->checkWhatsAppStatus();

        //dd($this->checkWhatsAppStatus());
    }

    public function inicializarPagoMixto()
    {
        $this->metodos_pago_mixto = [
            ['metodo' => 'efectivo_dolares', 'monto' => 0, 'referencia' => '-'],
            ['metodo' => 'transferencia', 'monto' => 0, 'referencia' => '-']
        ];
    }

    public function cargarTasaCambio()
    {
        $fecha = $this->fecha_pago ?: $this->fecha;
        if ($fecha) {
            $service = new ExchangeRateService();
            $rate = $service->getUsdRateForDate(Carbon::parse($fecha));
            if ($rate) {
                $this->tasa_cambio = $rate;
                $this->mostrar_bolivares = true;
                return;
            }
        }
        
        // Intentar obtener la tasa del día actual
        $tasaHoy = \App\Models\ExchangeRate::getTodayRate();
        if ($tasaHoy) {
            $this->tasa_cambio = $tasaHoy->usd_rate;
            $this->mostrar_bolivares = true;
            return;
        }
        
        // Si no hay tasa para hoy, intentar obtener la tasa más reciente disponible
        $ultimaTasa = \App\Models\ExchangeRate::orderBy('date', 'desc')
            ->whereNotNull('usd_rate')
            ->first();
            
        if ($ultimaTasa) {
            $this->tasa_cambio = $ultimaTasa->usd_rate;
            $this->mostrar_bolivares = true;
            
            // Mostrar advertencia si la tasa no es de hoy
            if ($ultimaTasa->date->lt(today())) {
                session()->flash('warning', 'No se encontró tasa de cambio para la fecha seleccionada ni para hoy. Se está utilizando la tasa más reciente disponible del ' . $ultimaTasa->date->format('d/m/Y'));
            }
        } else {
            // Si no hay ninguna tasa registrada, usar una tasa por defecto
            $this->tasa_cambio = 36.50; // Tasa por defecto
            $this->mostrar_bolivares = true;
            session()->flash('warning', 'No se encontró ninguna tasa de cambio registrada. Se está utilizando una tasa por defecto de 36.50 Bs/$');
        }
    }

    public function updatedFechaPago($value)
    {
        $this->cargarTasaCambio();
    }

    public function verificarCajaAbierta()
    {
        $this->caja_abierta = Caja::obtenerCajaAbierta(
            auth()->user()->empresa_id,
            auth()->user()->sucursal_id
        );
    }

    public function cargarDatos()
    {
        $query = Matricula::with(['student', 'programa']);

        if (auth()->check() && !auth()->user()->hasRole('Super Administrador')) {
            $query->where('empresa_id', auth()->user()->empresa_id)
                  ->where('sucursal_id', auth()->user()->sucursal_id);
        }

        $this->matriculas = $query->get();
        $this->conceptos = ConceptoPago::where('activo', true)->get();
        $this->cargarSerieActual();
    }

    public function updatedBusquedaEstudiante($value)
    {
        if (strlen($value) >= 2) {
            $query = Matricula::with(['student', 'programa'])
                ->whereHas('student', function($q) use ($value) {
                    $q->where('nombres', 'like', '%' . $value . '%')
                      ->orWhere('apellidos', 'like', '%' . $value . '%')
                      ->orWhere('documento_identidad', 'like', '%' . $value . '%')
                      ->orWhere('codigo', 'like', '%' . $value . '%');
                })
                ->orWhereHas('programa', function($q) use ($value) {
                    $q->where('nombre', 'like', '%' . $value . '%');
                })
                ->whereHas('paymentSchedules', function($q) {
                    $q->whereIn('estado', ['pendiente', 'vencido']);
                });

            if (auth()->check() && !auth()->user()->hasRole('Super Administrador')) {
                $query->where('empresa_id', auth()->user()->empresa_id)
                      ->where('sucursal_id', auth()->user()->sucursal_id);
            }

            $this->matriculas_filtradas = $query->limit(10)->get();
        } else {
            $this->matriculas_filtradas = [];
        }
    }

    public function seleccionarMatricula($matriculaId)
    {
        $this->matricula_id = $matriculaId;
        $this->busqueda_estudiante = '';
        $this->matriculas_filtradas = [];
        $this->updatedMatriculaId($matriculaId);
        $this->cargarPagosAnteriores($matriculaId);
    }

    public function cargarPagosAnteriores($matriculaId)
    {
        $this->pagos_anteriores = Pago::where('matricula_id', $matriculaId)
            ->where('estado', 'aprobado')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function cargarPlantillasPago()
    {
        $this->plantillas_pago = [
            'mensualidad' => ['nombre' => 'Mensualidad', 'conceptos' => ['Mensualidad']],
            'inscripcion' => ['nombre' => 'Inscripción', 'conceptos' => ['Inscripción', 'Materiales']],
            'materiales' => ['nombre' => 'Materiales', 'conceptos' => ['Materiales Escolares']]
        ];
    }

    public function aplicarPlantilla($tipo)
    {
        $this->detalles = [];
        $plantilla = $this->plantillas_pago[$tipo] ?? null;

        if ($plantilla) {
            foreach ($plantilla['conceptos'] as $nombreConcepto) {
                $concepto = ConceptoPago::where('nombre', 'like', '%' . $nombreConcepto . '%')
                    ->where('activo', true)
                    ->first();

                if ($concepto) {
                    $this->detalles[] = [
                        'concepto_pago_id' => $concepto->id,
                        'payment_schedule_id' => null,
                        'descripcion' => $concepto->nombre,
                        'cantidad' => 1,
                        'precio_unitario' => $concepto->precio_sugerido ?? 0
                    ];
                }
            }
        }

        if (empty($this->detalles)) {
            $this->agregarDetalle();
        }
    }

    public function updatedDetalles($value, $key)
    {
        if (str_contains($key, 'precio_unitario') || str_contains($key, 'cantidad')) {
            $this->validateOnly("detalles.{$key}");
        }
    }

    public function getCambioProperty()
    {
        return max(0, $this->monto_recibido - $this->total);
    }

    public function updatedMatriculaId($value)
    {
        if ($value) {
            if (class_exists('\App\Models\PaymentSchedule')) {
                $this->cuotasPendientes = PaymentSchedule::where('matricula_id', $value)
                    ->whereIn('estado', ['pendiente', 'vencido'])
                    ->orderBy('numero_cuota')
                    ->get();
            }
        }
    }

    public function updatedTipoPago($value)
    {
        $this->cargarSerieActual();
    }

    public function cargarSerieActual()
    {
        $query = Serie::where('tipo_documento', $this->tipo_pago)
                     ->where('activo', true);


        $this->serie_actual = $query->first();

        if ($this->serie_actual) {
            $siguienteNumero = $this->serie_actual->correlativo_actual + 1;
            $this->numero_documento = $this->serie_actual->serie . '-' .
                str_pad($siguienteNumero, $this->serie_actual->longitud_correlativo, '0', STR_PAD_LEFT);
        } else {
            $this->numero_documento = null;
        }
    }

    public function agregarDetalle()
    {
        $conceptoDefault = ConceptoPago::where('activo', true)->first();

        $this->detalles[] = [
            'concepto_pago_id' => $conceptoDefault?->id ?? '',
            'payment_schedule_id' => null,
            'descripcion' => '',
            'cantidad' => 1,
            'precio_unitario' => 0.01
        ];
    }

    public function eliminarDetalle($index)
    {
        unset($this->detalles[$index]);
        $this->detalles = array_values($this->detalles);
    }

    public function seleccionarCuota($scheduleId)
    {
        $schedule = PaymentSchedule::find($scheduleId);
        if ($schedule) {
            $conceptoMensualidad = ConceptoPago::where('nombre', 'Mensualidad')->first();
            $conceptoRecargo = ConceptoPago::where('nombre', 'Recargo por Mora')->first();

            // Agregar cuota principal
            $this->detalles[] = [
                'concepto_pago_id' => $conceptoMensualidad?->id,
                'payment_schedule_id' => $schedule->id,
                'descripcion' => "Cuota #{$schedule->numero_cuota} - {$schedule->fecha_vencimiento->format('M Y')}",
                'cantidad' => 1,
                'precio_unitario' => $schedule->saldo_pendiente
            ];

            // Agregar recargo si existe
            if ($schedule->recargo_morosidad > 0) {
                if (!$conceptoRecargo) {
                    $conceptoRecargo = ConceptoPago::create([
                        'nombre' => 'Recargo por Mora',
                        'descripcion' => 'Recargo aplicado por pagos vencidos',
                        'activo' => true
                    ]);
                }

                $this->detalles[] = [
                    'concepto_pago_id' => $conceptoRecargo->id,
                    'payment_schedule_id' => null,
                    'descripcion' => "Recargo por mora - Cuota #{$schedule->numero_cuota}",
                    'cantidad' => 1,
                    'precio_unitario' => $schedule->recargo_morosidad
                ];
            }
        }
    }

    public function agregarAbono($scheduleId, $monto = null)
    {
        $schedule = PaymentSchedule::find($scheduleId);
        if ($schedule) {
            $conceptoMensualidad = ConceptoPago::where('nombre', 'Mensualidad')->first();
            $montoAbono = $monto ?? $schedule->saldo_pendiente;

            $this->detalles[] = [
                'concepto_pago_id' => $conceptoMensualidad?->id,
                'payment_schedule_id' => $schedule->id,
                'descripcion' => "Abono Cuota #{$schedule->numero_cuota}",
                'cantidad' => 1,
                'precio_unitario' => $montoAbono
            ];
        }
    }

    public function calcularSubtotal($index)
    {
        $detalle = $this->detalles[$index];
        return ($detalle['cantidad'] ?? 0) * ($detalle['precio_unitario'] ?? 0);
    }

    public function getSubtotalProperty()
    {
        return collect($this->detalles)->sum(fn($d) => ($d['cantidad'] ?? 0) * ($d['precio_unitario'] ?? 0));
    }

    public function getTotalProperty()
    {
        return $this->subtotal - $this->descuento;
    }

    public function getTotalBolivaresProperty()
    {
        if ($this->tasa_cambio) {
            return $this->total * $this->tasa_cambio;
        }
        return 0;
    }

    public function getSubtotalBolivaresProperty()
    {
        if ($this->tasa_cambio) {
            return $this->subtotal * $this->tasa_cambio;
        }
        return 0;
    }

    public function getDescuentoBolivaresProperty()
    {
        if ($this->tasa_cambio) {
            return $this->descuento * $this->tasa_cambio;
        }
        return 0;
    }

    public function updatedMetodoPago($value)
    {
        $this->es_pago_mixto = ($value === 'pago mixto');
        if (!$this->es_pago_mixto) {
            $this->inicializarPagoMixto();
        }
    }

    public function agregarMetodoPago()
    {
        $this->metodos_pago_mixto[] = ['metodo' => 'efectivo_dolares', 'monto' => 0, 'referencia' => ''];
    }

    public function eliminarMetodoPago($index)
    {
        if (count($this->metodos_pago_mixto) > 1) {
            unset($this->metodos_pago_mixto[$index]);
            $this->metodos_pago_mixto = array_values($this->metodos_pago_mixto);
        }
    }

    public function getTotalPagoMixtoProperty()
    {
        return collect($this->metodos_pago_mixto)->sum('monto');
    }

    private function enviarNotificacionWhatsApp($pago, $matricula)
    {
        $result = ['sent' => false, 'attempted' => false, 'destinatario' => null];



        try {
              $estudiante = $matricula->student;
            $esMayorDeEdad = \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->age >= 18;

            $telefono = null;
            $nombreDestino = null;

            if (!$esMayorDeEdad && $estudiante->representante_telefonos) {
                $telefonos = explode(',', $estudiante->representante_telefonos);
                $telefono = trim($telefonos[0] ?? '');
                $nombreDestino = $estudiante->representante_nombres . ' ' . $estudiante->representante_apellidos;
            }
            else {
                $telefono = $estudiante->telefono;
                $nombreDestino = $estudiante->nombres . ' ' . $estudiante->apellidos;
            }


            $result['attempted'] = true;
            $result['destinatario'] = $nombreDestino;

            $mensaje = $this->generarMensajePago($pago, $estudiante, $esMayorDeEdad);
            $telefonoFormateado = $this->formatPhoneNumber($telefono);

            $whatsappService = new \App\Services\WhatsAppService();
            $whatsappResult = $whatsappService->sendMessage($telefonoFormateado, $mensaje, true);

            $result['sent'] = $whatsappResult && ($whatsappResult['success'] ?? false);

        } catch (\Exception $e) {

            \Log::error('Error enviando notificación WhatsApp de pago: ' . $e->getMessage());
            $result['attempted'] = true;
        }

        return $result;
    }

    private function generarMensajePago($pago, $estudiante, $esMayorDeEdad)
    {
        $nombreEstudiante = $estudiante->nombres . ' ' . $estudiante->apellidos;
        $totalFormateado = '$' . number_format($pago->total, 2, ',', '.');

        if ($esMayorDeEdad) {
            $mensaje = "💳 *Pago Recibido - INSTITUTO VARGAS II*\n\n";
            $mensaje .= "Estimado/a {$nombreEstudiante},\n\n";
        } else {
            $representante = $estudiante->representante_nombres . ' ' . $estudiante->representante_apellidos;
            $mensaje = "💳 *Pago Recibido - INSTITUTO VARGAS II*\n\n";
            $mensaje .= "Estimado/a {$representante},\n\n";
            $mensaje .= "Hemos recibido el pago del estudiante *{$nombreEstudiante}*.\n\n";
        }

        $mensaje .= "📄 *Detalles del Pago:*\n";
        $mensaje .= "• Número de Recibo: *{$pago->numero_completo}*\n";
        $mensaje .= "• Fecha: {$pago->fecha->format('d/m/Y')}\n";
        $mensaje .= "• Método: " . ucfirst(str_replace('_', ' ', $pago->metodo_pago)) . "\n";
        if ($pago->referencia) {
            $mensaje .= "• Referencia: {$pago->referencia}\n";
        }

        $mensaje .= "\n📋 *Conceptos Pagados:*\n";
        foreach ($pago->detalles as $detalle) {
            $montoDetalle = '$' . number_format($detalle->precio_unitario * $detalle->cantidad, 2, ',', '.');
            $mensaje .= "• {$detalle->descripcion}: {$montoDetalle}\n";
        }

        $mensaje .= "\n💰 *Total Pagado: {$totalFormateado}*\n\n";
        $mensaje .= "Gracias por su pago puntual.\n\n";
        $mensaje .= "*INSTITUTO VARGAS II*";

        return $mensaje;
    }

    private function formatPhoneNumber($number)
    {
        $empresa = \DB::table('empresas')->where('id', 1)->first();
        $pais = $empresa ? \DB::table('pais')->where('id', $empresa->pais_id)->first() : null;
        $codigoPais = $pais ? $pais->codigo_telefonico : '58';

        $cleaned = preg_replace('/[^0-9]/', '', $number);

        if (strlen($cleaned) > 10 && str_starts_with($cleaned, $codigoPais)) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '0')) {
            $cleaned = substr($cleaned, 1);
        }

        return $codigoPais . $cleaned;
    }

    public function checkWhatsAppStatus()
    {
        try {
            $apiUrl = config('whatsapp.api_url', 'http://localhost:3001');
            $apiKey = config('whatsapp.api_key', 'test-api-key-vargas-centro');

            // Verificar si el servicio está disponible
            $healthResponse = \Http::timeout(3)->get($apiUrl . '/health');
            if (!$healthResponse->successful()) {
                $this->whatsappStatus = 'disconnected';
                return;
                }

                // Obtener estado de conexión
                $response = \Http::withHeaders(['X-API-Key' => $apiKey])
                ->timeout(5)
                ->get($apiUrl . '/api/whatsapp/status');

                if ($response->successful()) {
                    $data = $response->json();
                    $this->whatsappStatus = $data['connectionState'] ?? 'disconnected';
            } else {
                $this->whatsappStatus = 'disconnected';
            }
        } catch (\Exception $e) {
            $this->whatsappStatus = 'disconnected';
        }
    }

    public function guardar()
    {
        // Validación especial para pago mixto
       if($this->es_pago_mixto && $this->totalPagoMixto != $this->total) {
            session()->flash('error', 'El total configurado en el pago mixto debe coincidir con el total a pagar.');
            return;
        }

        // Validar referencias duplicadas antes de guardar
       if($this->es_pago_mixto) {
            foreach ($this->metodos_pago_mixto as $index => $metodo) {
               if (!empty($metodo['referencia'])) {
                    $valorLimpio = preg_replace('/[^a-zA-Z0-9\-]/', '', $metodo['referencia']);

                    // Buscar en el campo referencia directo
                    $existeReferenciaDirecta = \App\Models\Pago::where('referencia', $valorLimpio)
                        ->where('estado', 'aprobado')
                        ->exists();

                    // Buscar en el campo detalles_pago_mixto (JSON)
                    $existeEnDetalles = \App\Models\Pago::where('estado', 'aprobado')
                        ->whereJsonContains('detalles_pago_mixto', [['referencia' => $valorLimpio]])
                        ->exists();

                   if($existeReferenciaDirecta || $existeEnDetalles) {
                        session()->flash('error', 'La referencia "' . $metodo['referencia'] . '" ya fue utilizada en otro pago.');
                        return;
                    }
                }
            }
        } elseif (!empty($this->referencia)) {
            // Validar referencia para pago no mixto
            $valorLimpio = preg_replace('/[^a-zA-Z0-9\-]/', '', $this->referencia);

            $existeReferenciaDirecta = \App\Models\Pago::where('referencia', $valorLimpio)
                ->where('estado', 'aprobado')
                ->exists();

            $existeEnDetalles = \App\Models\Pago::where('estado', 'aprobado')
                ->whereJsonContains('detalles_pago_mixto', [['referencia' => $valorLimpio]])
                ->exists();

           if($existeReferenciaDirecta || $existeEnDetalles) {
                session()->flash('error', 'La referencia "' . $this->referencia . '" ya fue utilizada en otro pago.');
                return;
            }
        }

        // AUTO-ASIGNAR payment_schedule_id basado en descripción si no está asignado
       if($this->matricula_id && !empty($this->detalles)) {
            $this->detalles = $this->autoAsignarPaymentSchedules($this->detalles, $this->matricula_id);
        }

       try {
        DB::transaction(function () {


            $matricula = Matricula::find($this->matricula_id);

            $pagoService = new PagoService();
            $pago = $pagoService->crearPago([
                'tipo_pago' => $this->tipo_pago,
                'fecha' => $this->fecha_pago,
                'matricula_id' => $this->matricula_id,
                'serie_id' => $this->serie_actual?->id,
                'metodo_pago' => $this->metodo_pago,
                'referencia' => $this->referencia,
                'descuento' => $this->descuento,
                'observaciones' => $this->observaciones,
                'tasa_cambio' => $this->tasa_cambio,
                'es_pago_mixto' => $this->es_pago_mixto,
                'detalles_pago_mixto' => $this->es_pago_mixto ? $this->metodos_pago_mixto : null,
                'empresa_id' => auth()->user()->empresa_id,
                'sucursal_id' => auth()->user()->sucursal_id,
                'caja_id' => $this->caja_abierta->id,
                'estado' => Pago::ESTADO_APROBADO,
                'detalles' => $this->detalles
            ]);

            $this->dispatch('pago-registrado', ['mensaje' => 'Pago registrado exitosamente: ' . $pago->numero_completo]);

            // Enviar notificación por WhatsApp y esperar respuesta
            $whatsappResult = $this->enviarNotificacionWhatsApp($pago, $matricula);
            $mensaje = 'Pago registrado exitosamente: ' . $pago->numero_completo;
           if($whatsappResult['sent']) {
                $mensaje .= ' - Notificación WhatsApp enviada a ' . $whatsappResult['destinatario'];
            } elseif($whatsappResult['attempted']) {
                $mensaje .= ' - No se pudo enviar notificación WhatsApp';
            }

            session()->flash('message', $mensaje);
            return redirect()->route('admin.pagos.create');
        });
        } catch (\Throwable $th) {
            dd($th);
            session()->flash('error', 'Error al crear el pago: ' . $th->getMessage());
        }
    }

    /**
     * Auto-asigna payment_schedule_id a los detalles basándose en la descripción
     * cuando no está explícitamente asignado
     */
    private function autoAsignarPaymentSchedules(array $detalles, int $matriculaId): array
    {
       if (!$matriculaId) {
            return $detalles;
        }

        // Cargar todos los payment schedules de la matrícula
        $schedules = \App\Models\PaymentSchedule::where('matricula_id', $matriculaId)
            ->orderBy('numero_cuota')
            ->get()
            ->keyBy('numero_cuota'); // Indexar por número de cuota para acceso rápido

        foreach ($detalles as &$detalle) {
            // Si ya tiene payment_schedule_id, saltar
           if (!empty($detalle['payment_schedule_id'])) {
                continue;
            }

            $descripcion = $detalle['descripcion'] ?? '';
            $monto = $detalle['precio_unitario'] ?? 0;

            // Intentar extraer número de cuota de la descripción
          if(preg_match('/Cuota\s+#?(\d+)/i', $descripcion, $matches)) {
                $numeroCuota = (int)$matches[1];

              if(isset($schedules[$numeroCuota])) {
                    $schedule = $schedules[$numeroCuota];

                    // Verificar si el monto coincide (con tolerancia de 0.01)
                  if(abs($monto - $schedule->monto) < 0.01 ||
                       abs($monto- $schedule->saldo_pendiente) < 0.01) {

                        // Asignar payment_schedule_id
                       $detalle['payment_schedule_id'] = $schedule->id;
                    }
                }
            }
        }

        return $detalles;
    }

    /**
     * Método updated general para detectar cambios en metodos_pago_mixto
     */
    public function updatedMetodosPagoMixto($value, $key)
    {


        // Si el cambio es en una referencia, procesar la validación
        if (str_contains($key, 'referencia')) {
            $this->validarReferenciaMixto($value, $key);
        }
    }

    public function updated($property, $value)
    {
        if ($property === 'fecha_pago' || $property === 'fecha') {
            $this->cargarTasaCambio();
        }
    }

    /**
     * Validar referencia en tiempo real
     */
    private function validarReferenciaMixto($value, $key)
    {
        // Extraer el índice del array
        $parts = explode('.', $key);
        $index = $parts[0] ?? null;

        if ($index !== null && isset($this->metodos_pago_mixto[$index])) {
            // Limpiar la referencia
            $valorLimpio = trim($value);
            $valorLimpio = preg_replace('/[^a-zA-Z0-9\-]/', '', $valorLimpio);

            // Validar que la referencia no exista en la base de datos
            if (!empty($valorLimpio)) {
                // Buscar en el campo referencia directo
                $existeReferenciaDirecta = \App\Models\Pago::where('referencia', $valorLimpio)
                    ->where('estado', 'aprobado')
                    ->exists();

                // Buscar en el campo detalles_pago_mixto (JSON)
                $existeEnDetalles = \App\Models\Pago::where('estado', 'aprobado')
                    ->whereJsonContains('detalles_pago_mixto', [['referencia' => $valorLimpio]])
                    ->exists();

                // Verificar si existe en cualquiera de los dos lugares
                if ($existeReferenciaDirecta || $existeEnDetalles) {
                    // Usar $this->addError para mostrar el error en el campo específico
                    $this->addError('metodos_pago_mixto.' . $index . '.referencia', 'Esta referencia ya fue utilizada en otro pago.');
                    $this->metodos_pago_mixto[$index]['referencia'] = '';
                } else {
                    $this->metodos_pago_mixto[$index]['referencia'] = $valorLimpio;
                    // Limpiar el error si existe
                    $this->resetErrorBag('metodos_pago_mixto.' . $index . '.referencia');
                }
            }
        }
    }

    public function render()
    {
        $tipos = [
            'factura' => 'Factura',
            'boleta' => 'Boleta',
            'nota_credito' => 'Nota de Crédito',
            'recibo' => 'Recibo',
            'comunidad educativa' => 'Comunidad Educativa',
            'educacion adulto' => 'Educación de Adultos',
        ];

        return view('livewire.admin.pagos.create', compact('tipos'))->layout($this->getLayout());
    }

    function updatedReferencia($value)
    {
        if (!$this->es_pago_mixto) {
            // Limpiar la referencia
            $valorLimpio = trim($value);
            $valorLimpio = preg_replace('/[^a-zA-Z0-9\-]/', '', $valorLimpio);

            if (!empty($valorLimpio)) {
                // Buscar en el campo referencia directo
                $existeReferenciaDirecta = \App\Models\Pago::where('referencia', $valorLimpio)
                    ->where('estado', 'aprobado')
                    ->exists();

                // Buscar en el campo detalles_pago_mixto (JSON)
                $existeEnDetalles = \App\Models\Pago::where('estado', 'aprobado')
                    ->whereJsonContains('detalles_pago_mixto', [['referencia' => $valorLimpio]])
                    ->exists();

                // Verificar si existe en cualquiera de los dos lugares
                if ($existeReferenciaDirecta || $existeEnDetalles) {
                    $this->addError('referencia', 'Esta referencia ya fue utilizada en otro pago.');
                    $this->referencia = '';
                } else {
                    $this->referencia = $valorLimpio;
                    $this->resetErrorBag('referencia');
                }
            }
        }
    }

    /**
     * Función updated para manejar cambios en referencias de pago mixto
     * Se ejecuta cuando se actualiza cualquier referencia en metodos_pago_mixto
     */
    public function updatedMetodosPagoMixtoReferencia($value, $key)
    {
        // Usar el método de validación
        $this->validarReferenciaMixto($value, $key);

            // Actualizar el valor limpio
            $this->metodos_pago_mixto[$index]['referencia'] = $valorLimpio;

            // Si el método de pago es efectivo, la referencia debe estar vacía
            $metodoPago = $this->metodos_pago_mixto[$index]['metodo'] ?? '';
            if (in_array($metodoPago, ['efectivo_bolivares', 'efectivo_dolares'])) {
                $this->metodos_pago_mixto[$index]['referencia'] = '';
            }

            // Emitir evento para notificar cambios
            $this->dispatch('referencia-mixto-actualizada', [
                'index' => $index,
                'valor' => $valorLimpio,
                'metodo' => $metodoPago
            ]);
        }

}