<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\StudentAccessLog;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class SendAccessWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $student;
    public $accessLog;
    public $company;

    public function __construct(Student $student, StudentAccessLog $accessLog, $company = null)
    {
        $this->student = $student;
        $this->accessLog = $accessLog;
        $this->company = $company;
    }

    public function handle(): void
    {
        // Verificar que el estudiante es menor de edad
        if (!$this->student->es_menor_de_edad) {
            return;
        }

        // Verificar que el representante tenga teléfono
        $telefonos = $this->student->representante_telefonos;
        
        // Manejar caso donde representante_telefonos puede ser string o array
        if (is_string($telefonos)) {
            // Si es string, tratar de convertirlo a array
            $telefonos = array_map('trim', explode(',', $telefonos));
        } elseif (!is_array($telefonos)) {
            // Si no es ni string ni array, intentar decodificar JSON
            try {
                $decoded = json_decode($telefonos, true);
                if (is_array($decoded)) {
                    $telefonos = $decoded;
                } else {
                    $telefonos = [];
                }
            } catch (\Exception $e) {
                $telefonos = [];
            }
        }
        
        if (empty($telefonos)) {
            \Log::info('El representante no tiene teléfonos registrados', [
                'student_id' => $this->student->id,
                'student_name' => $this->student->nombres . ' ' . $this->student->apellidos,
                'telefonos_type' => gettype($this->student->representante_telefonos),
                'telefonos_value' => $this->student->representante_telefonos
            ]);
            return;
        }

        try {
            $company = $this->company ?? $this->student->empresa ?? auth()->user()->empresa;
            
            if (!$company) {
                \Log::warning('No se pudo determinar la empresa del estudiante', [
                    'student_id' => $this->student->id
                ]);
                return;
            }

            // Obtener el país de la empresa para el código telefónico
            $pais = $company->pais;
            $codigoPais = $pais ? $pais->codigo_telefonico : '+58'; // Default a Venezuela

            // Calcular tiempo en el instituto si es salida
            $timeInSchool = null;
            if ($this->accessLog->type === 'salida') {
                $entryLog = StudentAccessLog::where('student_id', $this->student->id)
                    ->whereDate('access_time', Carbon::today())
                    ->where('type', 'entrada')
                    ->orderBy('access_time', 'desc')
                    ->first();
                    
                if ($entryLog) {
                    $entryTime = Carbon::parse($entryLog->access_time);
                    $exitTime = Carbon::parse($this->accessLog->access_time);
                    $diff = $entryTime->diff($exitTime);
                    
                    $hours = $diff->h;
                    $minutes = $diff->i;
                    
                    if ($hours > 0) {
                        $timeInSchool = "{$hours} hora" . ($hours != 1 ? 's' : '') . " y {$minutes} minuto" . ($minutes != 1 ? 's' : '');
                    } else {
                        $timeInSchool = "{$minutes} minuto" . ($minutes != 1 ? 's' : '');
                    }
                }
            }

            // Construir el mensaje de WhatsApp
            $message = $this->buildWhatsAppMessage($timeInSchool);

            // Enviar a todos los teléfonos del representante
            $whatsappService = app(WhatsAppService::class);
            $sentCount = 0;
            $failedCount = 0;

            foreach ($telefonos as $telefono) {
                try {
                    // Formatear el número con el código de país
                    $telefonoFormateado = $this->formatPhoneNumber($telefono, $codigoPais);
                    
                    $resultado = $whatsappService->sendMessage($telefonoFormateado, $message);
                    
                    if ($resultado) {
                        $sentCount++;
                        \Log::info('Notificación WhatsApp enviada exitosamente', [
                            'student_id' => $this->student->id,
                            'phone' => $telefonoFormateado,
                            'access_type' => $this->accessLog->type
                        ]);
                    } else {
                        $failedCount++;
                        \Log::error('Error al enviar notificación WhatsApp', [
                            'student_id' => $this->student->id,
                            'phone' => $telefonoFormateado,
                            'access_type' => $this->accessLog->type
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    \Log::error('Excepción al enviar notificación WhatsApp', [
                        'student_id' => $this->student->id,
                        'phone' => $telefono,
                        'error' => $e->getMessage(),
                        'access_type' => $this->accessLog->type
                    ]);
                }
            }

            \Log::info('Resumen de envío de notificaciones WhatsApp', [
                'student_id' => $this->student->id,
                'student_name' => $this->student->nombres . ' ' . $this->student->apellidos,
                'access_type' => $this->accessLog->type,
                'sent' => $sentCount,
                'failed' => $failedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Error general al enviar notificación WhatsApp de acceso', [
                'student_id' => $this->student->id,
                'error' => $e->getMessage(),
                'access_type' => $this->accessLog->type
            ]);
        }
    }

    private function buildWhatsAppMessage($timeInSchool = null)
    {
        $studentName = $this->student->nombres . ' ' . $this->student->apellidos;
        $accessTime = Carbon::parse($this->accessLog->access_time)->format('H:i');
        $accessDate = Carbon::parse($this->accessLog->access_time)->format('d/m/Y');
        
        $emoji = $this->accessLog->type === 'entrada' ? '📥' : '📤';
        $action = $this->accessLog->type === 'entrada' ? 'ingreso' : 'salida';
        $actionCapitalized = ucfirst($action);

        $message = "¡Hola! 👋\n\n";
        $message .= "{$emoji} **{$actionCapitalized} registrada** 📚\n\n";
        $message .= "**Estudiante:** {$studentName}\n";
        $message .= "**Código:** {$this->student->codigo}\n";
        $message .= "**Fecha:** {$accessDate}\n";
        $message .= "**Hora:** {$accessTime}\n";
        
        if ($this->accessLog->notes) {
            $message .= "**Notas:** {$this->accessLog->notes}\n";
        }

        if ($timeInSchool && $this->accessLog->type === 'salida') {
            $message .= "\n⏱️ **Tiempo en el instituto:** {$timeInSchool}\n";
        }

        $message .= "\n🏫 U.E JOSE MARIA VARGAS\n";
        $message .= "💡 Este es un mensaje automático";

        return $message;
    }

    private function formatPhoneNumber($phone, $countryCode)
    {
        // Limpiar el número de teléfono
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si el número ya tiene el código de país, devolverlo tal cual
        if (str_starts_with($phone, ltrim($countryCode, '+'))) {
            return $phone;
        }
        
        // Si el número comienza con 0, quitarlo
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        
        // Agregar el código de país
        return ltrim($countryCode, '+') . $phone;
    }
}