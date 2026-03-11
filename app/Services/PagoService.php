<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\PagoDetalle;
use App\Models\PlanPago;
use App\Models\ConceptoPago;
use App\Models\Caja;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppService;
use App\Services\ThermalPrinterService;

class PagoService
{
    public function crearPago(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Crear pago (cabecera)
            $pago = Pago::create([
                'tipo_pago' => $data['tipo_pago'],
                'fecha' => $data['fecha'],
                'matricula_id' => $data['matricula_id'] ?? null,
                'serie_id' => $data['serie_id'] ?? null,
                'serie' => $data['serie'] ?? null,
                'numero' => $data['numero'] ?? null,
                'numero_completo' => $data['numero_completo'] ?? null,
                'caja_id' => $data['caja_id'],
                'user_id' => auth()->id(),
                'metodo_pago' => $data['metodo_pago'] ?? null,
                'referencia' => $data['referencia'] ?? null,
                'descuento' => $data['descuento'] ?? 0,
                'subtotal' => $data['subtotal'] ?? 0,
                'total' => $data['total'] ?? 0,
                'tasa_cambio' => $data['tasa_cambio'] ?? null,
                'total_bolivares' => $data['total_bolivares'] ?? null,
                'es_pago_mixto' => $data['es_pago_mixto'] ?? false,
                'detalles_pago_mixto' => $data['detalles_pago_mixto'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'empresa_id' => $data['empresa_id'],
                'sucursal_id' => $data['sucursal_id'],
                'estado' => $data['estado'] ?? 'pendiente'
            ]);

            foreach ($data['detalles'] as $detalle) {
                $this->agregarDetalle($pago, $detalle);
            }
            $pago->calcularTotales();
            
            DB::afterCommit(function () use ($pago) {
                try {
                    $this->sendWhatsappReceipt($pago);
                } catch (\Exception $e) {
                    Log::warning('WhatsApp receipt send failed', [
                        'pago_id' => $pago->id,
                        'error' => $e->getMessage()
                    ]);
                }
                try {
                    if($pago->estado === 'aprobado' && ThermalPrinterService::isAvailable()) {
                        $result = ThermalPrinterService::printPayment($pago);
                        if (!($result['success'] ?? false)) {
                            Log::warning('Thermal print failed', [
                                'pago_id' => $pago->id,
                                'message' => $result['message'] ?? ''
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Thermal printer error', [
                        'pago_id' => $pago->id,
                        'error' => $e->getMessage()
                    ]);
                }
            });
            
            return $pago->fresh(['detalles']);
        });
    }

    public function agregarDetalle(Pago $pago, array $detalle)
    {
        // Si no hay payment_schedule_id pero hay matricula_id, intentar asignar automáticamente
      if(empty($detalle['payment_schedule_id']) && !empty($pago->matricula_id)) {
            $detalle['payment_schedule_id'] = $this->autoAssignPaymentSchedule(
                $pago->matricula_id,
                $detalle['descripcion'] ?? '',
                $detalle['subtotal'] ?? 0
            );
        }

        $pagoDetalle = PagoDetalle::create([
            'pago_id' => $pago->id,
            'concepto_pago_id' => $detalle['concepto_pago_id'],
            'payment_schedule_id' => $detalle['payment_schedule_id'] ?? null,
            'descripcion' => $detalle['descripcion'],
            'cantidad' => $detalle['cantidad'] ?? 1,
            'precio_unitario' => $detalle['precio_unitario'],
            'subtotal' => ($detalle['cantidad'] ?? 1) * $detalle['precio_unitario']
        ]);

        // Si el detalle está vinculado a una cuota, actualizar el payment schedule
       if (!empty($pagoDetalle->payment_schedule_id)) {
            $schedule = PaymentSchedule::find($pagoDetalle->payment_schedule_id);
          if($schedule) {
               $schedule->syncPaidAmountFromPayments();
           }
        }

        return $pagoDetalle;
    }

    /**
     * Asigna automáticamente un payment_schedule basado en la descripción y monto
     */
   protected function autoAssignPaymentSchedule(int $matriculaId, string $descripcion, float $monto): ?int
    {
        // Intentar extraer número de cuota de la descripción
      if(preg_match('/Cuota\s+#?(\d+)/i', $descripcion, $matches)) {
            $numeroCuota = (int)$matches[1];
            
            $schedule= PaymentSchedule::where('matricula_id', $matriculaId)
                ->where('numero_cuota', $numeroCuota)
                ->first();
            
          if($schedule) {
                // Verificar si el monto coincide (con tolerancia de 0.01)
              if(abs($monto - $schedule->monto) < 0.01 || 
                    abs($monto - $schedule->saldo_pendiente) < 0.01) {
                   return $schedule->id;
                }
            }
        }
        
        // Si no se puede asignar automáticamente, retornar null
        return null;
    }
    
    protected function actualizarEstadoContrato($contratoId)
    {
        $contrato = \App\Models\Contrato::find($contratoId);
        if ($contrato) {
            $pendientes = PlanPago::where('contrato_id', $contratoId)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->count();
                
            if ($pendientes === 0) {
                $contrato->estado = 'finalizado';
                $contrato->save();
            }
            
            // Recalcular saldo pendiente del contrato
            $saldoTotal = PlanPago::where('contrato_id', $contratoId)->sum('saldo_pendiente');
            $contrato->saldo_pendiente = $saldoTotal;
            $contrato->cuotas_pagadas = PlanPago::where('contrato_id', $contratoId)->where('estado', 'pagado')->count();
            $contrato->save();
        }
    }

    public function pagarCuota($planPagoId, array $data)
    {
        $planPago = PlanPago::findOrFail($planPagoId);
        $conceptoMensualidad = ConceptoPago::where('nombre', 'Mensualidad')->first();

        return $this->crearPago([
            'tipo_pago' => $data['tipo_pago'] ?? 'recibo',
            'fecha' => $data['fecha'] ?? now(),
            'cliente_id' => $planPago->contrato->cliente_id,
            'caja_id' => $data['caja_id'],
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'referencia' => $data['referencia'] ?? null,
            'descuento' => $data['descuento'] ?? 0,
            'observaciones' => $data['observaciones'] ?? null,
            'empresa_id' => $planPago->empresa_id,
            'sucursal_id' => $planPago->sucursal_id,
            'estado' => 'aprobado',
            'detalles' => [
                [
                    'concepto_pago_id' => $conceptoMensualidad?->id,
                    'plan_pago_id' => $planPago->id,
                    'descripcion' => "Cuota #{$planPago->numero_cuota} - Vencimiento: {$planPago->fecha_vencimiento->format('d/m/Y')}",
                    'cantidad' => 1,
                    'precio_unitario' => $data['monto'] ?? $planPago->saldo_pendiente
                ]
            ]
        ]);
    }

    private function sendWhatsappReceipt(Pago $pago): void
    {
        try {
            $cliente = $pago->cliente;
            $phone = $cliente->telefono ?? $cliente->phone ?? null;
            if (!$phone) {
                return;
            }
            $service = WhatsAppService::forCompany($pago->empresa_id);
            $conceptos = $pago->detalles->pluck('descripcion')->filter()->implode(', ');
            $esCuota = $pago->detalles->contains(function ($d) {
                return !empty($d->plan_pago_id);
            });
            $titulo = $esCuota ? 'Confirmación de cancelación de cuota' : 'Confirmación de pago';
            $link = route('admin.pagos.print', $pago->id);
            $msg = "✅ {$titulo}\n\n";
            $msg .= "Recibo: {$pago->numero_completo}\n";
            $msg .= "Fecha: " . optional($pago->fecha)->format('d/m/Y H:i') . "\n";
            $msg .= "Monto: $" . number_format($pago->total, 2) . "\n";
            if ($pago->metodo_pago) {
                $msg .= "Método: " . ucfirst($pago->metodo_pago) . "\n";
            }
            if ($conceptos) {
                $msg .= "Detalle: {$conceptos}\n";
            }
            $msg .= "\n📄 Comprobante: {$link}\n";
            $msg .= "\nGracias por tu pago.";
            $service->sendMessage($phone, $msg);
        } catch (\Exception $e) {
            Log::error('Error enviando comprobante por WhatsApp', [
                'pago_id' => $pago->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function aprobarPago(Pago $pago)
    {
        $pago->update(['estado' => 'aprobado']);
        
        // Actualizar totales de la caja si está asociada
        if ($pago->caja) {
            $pago->caja->calcularTotales();
        }
        
        return $pago;
    }

    public function cancelarPago(Pago $pago, $motivo = null)
    {
        DB::transaction(function () use ($pago, $motivo) {
            // Revertir pagos en plan de pagos
            foreach ($pago->detalles as $detalle) {
                if ($detalle->plan_pago_id) {
                    $planPago = PlanPago::find($detalle->plan_pago_id);
                    if ($planPago) {
                        $planPago->monto_pagado -= $detalle->subtotal;
                        
                        if ($planPago->monto_pagado < $planPago->monto_total) {
                            $planPago->estado = 'pendiente'; // O 'parcial' si monto_pagado > 0
                            if ($planPago->monto_pagado > 0) {
                                $planPago->estado = 'parcial';
                            }
                            $planPago->fecha_pago_real = null;
                        }
                        
                        $planPago->saldo_pendiente = $planPago->monto_total - $planPago->monto_pagado;
                        $planPago->save();
                    }
                }
            }

            $pago->update([
                'estado' => 'cancelado', // Use string literals as in Create.php
                'observaciones' => ($pago->observaciones ? $pago->observaciones . "\n" : '') . "Cancelado: $motivo"
            ]);
        });

        return $pago;
    }
}
