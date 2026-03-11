<?php

namespace App\Livewire\Admin\Matriculas;

use App\Traits\HasDynamicLayout;
use App\Traits\HasRegionalFormatting;
use Livewire\Component;
use App\Models\Matricula;
use Illuminate\Auth\Access\AuthorizationException;

class Show extends Component
{
    use HasDynamicLayout;
    use HasRegionalFormatting;

   public $matricula;

   public function mount(Matricula $matricula)
    {
        // Autorización: verificar permisos en el backend
       if (!auth()->user()->can('view matriculas', $matricula)) {
            throw new AuthorizationException('No tienes permiso para ver esta matrícula.');
        }

        // Eager loading completo para evitar N+1 queries
        $this->matricula = $matricula->load([
            'estudiante',
            'programa',
            'periodo',
            'paymentSchedules' => function ($query) {
                $query->orderBy('numero_cuota');
            },
            'pagos.detalles.conceptoPago'
        ]);
    }

  public function delete()
    {
        // Verificar permiso para eliminar matrículas
      if (!auth()->user()->can('delete matriculas')) {
            session()->flash('error', 'No tienes permiso para eliminar matrículas.');
           return;
        }

        try {
            $this->matricula->delete();
            session()->flash('success', 'Matrícula eliminada correctamente.');
           return redirect()->route('admin.matriculas.index');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    /**
     * Enviar notificación de solvencia por WhatsApp
     */
  public function enviarNotificacionSolvencia()
    {
        try {
            $matricula = $this->matricula;
            
            // Verificar si la matrícula está solvente
          if (!$matricula->solvente) {
                session()->flash('warning', 'La matrícula NO está solvente. No se puede enviar constancia de solvencia.');
               return;
            }

            // Determinar a quién enviar el mensaje (estudiante o representante)
            $destinatario = $this->obtenerDestinatarioMensaje();
            $telefono = $this->formatPhoneNumber($destinatario['telefono']);
            
            // Generar mensaje de solvencia
            $mensaje = $this->generarMensajeSolvencia($destinatario['nombre']);
            
            // Enviar por WhatsApp
            $resultado = $this->enviarWhatsApp($telefono, $mensaje);
            
          if($resultado['sent']) {
                session()->flash('success', "Constancia de solvencia enviada exitosamente a {$destinatario['nombre']}");
                
                // Registrar el envío
                $this->registrarEnvioSolvencia($destinatario);
            } else {
                session()->flash('error', 'No se pudo enviar la notificación: ' . ($resultado['error'] ?? 'Error desconocido'));
            }
            
        } catch (\Throwable $th) {
            session()->flash('error', 'Error al enviar notificación: ' . $th->getMessage());
        }
    }

    /**
     * Obtener el destinatario del mensaje (estudiante o representante)
     */
   private function obtenerDestinatarioMensaje(): array
    {
        $estudiante = $this->matricula->estudiante;
        
        // Si es menor de edad y tiene representante, enviar al representante
     if($estudiante->es_menor_de_edad && $estudiante->representante) {
          return [
                'nombre' => $estudiante->representante['nombre_completo'],
                'telefono' => $estudiante->representante['telefono'] ?? $estudiante->telefono,
                'relacion' => 'representante'
            ];
        }
        
        // Si es mayor de edad o no tiene representante, enviar al estudiante
      return [
            'nombre' => $estudiante->nombres . ' ' . $estudiante->apellidos,
            'telefono' => $estudiante->telefono,
            'relacion' => 'estudiante'
        ];
    }

    /**
     * Generar mensaje de constancia de solvencia
     */
    private function generarMensajeSolvencia(string $nombreDestinatario): string
    {
        $matricula = $this->matricula;
        $estudiante = $matricula->estudiante;
        
        $mensaje = "🎓 *CONSTANCIA DE SOLVENCIA ACADÉMICA*\n\n";
        $mensaje .= "U.E. JOSÉ MARÍA VARGAS\n";
        $mensaje .= "RIF: J-12345678-9\n\n";
        $mensaje .= "📄 *Datos del Estudiante:*\n";
        $mensaje .= "• Nombre: {$estudiante->nombres} {$estudiante->apellidos}\n";
        $mensaje .= "• Cédula: {$estudiante->documento_identidad}\n";
        $mensaje .= "• Programa: {$matricula->programa->nombre}\n";
        $mensaje .= "• Período: {$matricula->periodo->name}\n\n";
        
        $mensaje .= "✅ *ESTADO FINANCIERO: SOLVENTE*\n\n";
        $mensaje .= "Por medio de la presente se hace constar que el(la) estudiante {$estudiante->nombres} {$estudiante->apellidos} se encuentra SOLVENTE con esta institución educativa en concepto de pagos académicos.\n\n";
        
        $resumen = $matricula->resumen_financiero;
        $mensaje .= "📊 *Resumen Financiero:*\n";
        $mensaje .= "• Total Cuotas: $" . number_format($resumen['total_cuotas'], 2, ',', '.') . "\n";
        $mensaje .= "• Total Pagado: $" . number_format($resumen['total_pagado'], 2, ',', '.') . "\n";
        $mensaje .= "• Saldo Pendiente: $" . number_format($resumen['total_pendiente'], 2, ',', '.') . "\n";
        $mensaje .= "• Progreso: {$resumen['porcentaje_pagado']}%\n\n";
        
        $fechaEmision = now()->format('d/m/Y H:i:s');
        $mensaje .= "📅 Fecha de Emisión: {$fechaEmision}\n";
        $mensaje .= "🔖 Código de Verificación: " . strtoupper(uniqid('SOLV-')) . "\n\n";
        
        $mensaje .= "_Esta constancia se emite a solicitud del interesado para los fines que estime convenientes._\n\n";
        $mensaje .= "*U.E. JOSÉ MARÍA VARGAS*";
        
       return $mensaje;
    }

    /**
     * Enviar mensaje por WhatsApp
     */
    private function enviarWhatsApp(string $telefono, string $mensaje): array
    {
        try {
            $apiUrl = config('whatsapp.api_url', 'http://localhost:3001');
            $apiKey = config('whatsapp.api_key', 'test-api-key-vargas-centro');
            
            // Preparar datos para envío
            $datos = [
                'phone' => $telefono,
                'message' => $mensaje
            ];
            
            // Enviar request a la API de WhatsApp
            $response = \Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Content-Type' => 'application/json'
            ])
            ->timeout(10)
            ->post($apiUrl . '/api/send/message', $datos);
            
          if($response->successful()) {
               return [
                    'sent' => true,
                    'data' => $response->json()
                ];
            }
            
           return [
                'sent' => false,
                'error' => 'Error en la respuesta de WhatsApp API'
            ];
            
        } catch (\Throwable $th) {
           return [
                'sent' => false,
                'error' => $th->getMessage()
            ];
        }
    }

    /**
     * Formatear número de teléfono para WhatsApp
     */
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

    /**
     * Registrar el envío de la constancia de solvencia
     */
    private function registrarEnvioSolvencia(array $destinatario)
    {
        // Opcional: Guardar registro en base de datos
        // Esto podría ir en una tabla de 'constancias_solvencia'
        
        \Log::info('Constancia de solvencia enviada', [
            'matricula_id' => $this->matricula->id,
            'estudiante_id' => $this->matricula->estudiante_id,
            'destinatario' => $destinatario['nombre'],
            'relacion' => $destinatario['relacion'],
            'fecha' => now()
        ]);
    }

  public function render()
    {
       return view('livewire.admin.matriculas.show')
            ->layout($this->getLayout());
    }
}
