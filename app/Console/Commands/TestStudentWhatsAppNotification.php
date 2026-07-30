<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentAccessLog;
use App\Jobs\SendAccessWhatsAppNotificationJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestStudentWhatsAppNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:student-whatsapp-notification {student_id} {type=entrada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el envío de notificaciones WhatsApp para estudiantes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $studentId = $this->argument('student_id');
        $type = $this->argument('type');

        // Buscar el estudiante
        $student = Student::with(['empresa.pais'])->find($studentId);

        if (!$student) {
            $this->error('Estudiante no encontrado');
            return 1;
        }

        // Verificar que el estudiante sea menor de edad
        if (!$student->es_menor_de_edad) {
            $this->error('El estudiante no es menor de edad');
            return 1;
        }

        // Verificar que tenga teléfonos del representante
        if (!$student->representante_telefonos || empty($student->representante_telefonos)) {
            $this->error('El estudiante no tiene teléfonos del representante registrados');
            return 1;
        }

        // Crear un registro de acceso de prueba
        $accessLog = StudentAccessLog::create([
            'student_id' => $student->id,
            'type' => $type,
            'access_time' => Carbon::now(),
            'registered_by' => 1, // Usuario de prueba
            'notes' => 'Registro de prueba para notificación WhatsApp'
        ]);

        $this->info('Estudiante encontrado:');
        $this->line("Nombre: {$student->nombres} {$student->apellidos}");
        $this->line("Código: {$student->codigo}");
        $this->line("Edad: {$student->edad} años");
        $this->line("¿Es menor?: " . ($student->es_menor_de_edad ? 'Sí' : 'No'));
        $this->line("Representante: {$student->representante_nombres} {$student->representante_apellidos}");
        
        // Manejar representante_telefonos que puede ser string o array
        $telefonos = $student->representante_telefonos;
        if (is_string($telefonos)) {
            $decoded = json_decode($telefonos, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $telefonos = $decoded;
            } else {
                $telefonos = array_map('trim', explode(',', $telefonos));
            }
        }
        $this->line("Teléfonos: " . implode(', ', $telefonos));
        
        if ($student->empresa) {
            $this->line("Empresa: {$student->empresa->razon_social}");
            if ($student->empresa->pais) {
                $this->line("País: {$student->empresa->pais->nombre} (Código: {$student->empresa->pais->codigo_telefonico})");
            }
        }

        $this->info("\nTipo de acceso: {$type}");
        $this->info("Hora del acceso: " . Carbon::now()->format('d/m/Y H:i:s'));

        // Enviar la notificación directamente (síncrono)
        try {
            $whatsappService = app(\App\Services\WhatsAppService::class);
            $sentCount = 0;
            $failedCount = 0;

            // Procesar teléfonos (manejar string o array)
            $telefonos = $student->representante_telefonos;
            if (is_string($telefonos)) {
                $decoded = json_decode($telefonos, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $telefonos = $decoded;
                } else {
                    $telefonos = array_map('trim', explode(',', $telefonos));
                }
            }

            foreach ($telefonos as $telefono) {
                try {
                    // Formatear el número con el código de país
                    $telefonoFormateado = $this->formatPhoneNumber($telefono, $student->empresa);
                    
                    // Construir mensaje
                    $message = $this->buildTestMessage($student, $accessLog);
                    
                    // Enviar mensaje
                    $resultado = $whatsappService->sendMessage($telefonoFormateado, $mensaje, true)
                    
                    if ($resultado['success']) {
                        $sentCount++;
                        $this->info("✅ Mensaje enviado a {$telefonoFormateado}");
                    } else {
                        $failedCount++;
                        $this->error("❌ Error al enviar a {$telefonoFormateado}: " . ($resultado['error'] ?? 'Error desconocido'));
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->error("❌ Excepción al enviar a {$telefono}: " . $e->getMessage());
                }
            }

            $this->info("\n📊 Resumen de envío:");
            $this->info("Total de teléfonos: " . count($telefonos));
            $this->info("Mensajes enviados: {$sentCount}");
            $this->info("Mensajes fallidos: {$failedCount}");
            
            if ($sentCount > 0) {
                $this->info("\n📱 Mensaje de WhatsApp enviado:");
                $this->line($this->buildTestMessage($student, $accessLog));
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar la notificación: ' . $e->getMessage());
            // Eliminar el registro de prueba
            $accessLog->delete();
            return 1;
        }

        return 0;
    }

    private function buildTestMessage($student, $accessLog)
    {
        $studentName = $student->nombres . ' ' . $student->apellidos;
        $accessTime = Carbon::parse($accessLog->access_time)->format('H:i');
        $accessDate = Carbon::parse($accessLog->access_time)->format('d/m/Y');
        
        $emoji = $accessLog->type === 'entrada' ? '📥' : '📤';
        $action = $accessLog->type === 'entrada' ? 'ingreso' : 'salida';
        $actionCapitalized = ucfirst($action);

        $message = "¡Hola! 👋\n\n";
        $message .= "{$emoji} **{$actionCapitalized} registrada** 📚\n\n";
        $message .= "**Estudiante:** {$studentName}\n";
        $message .= "**Código:** {$student->codigo}\n";
        $message .= "**Fecha:** {$accessDate}\n";
        $message .= "**Hora:** {$accessTime}\n";
        $message .= "\n🏫 INSTITUTO VARGAS II\n";
        $message .= "💡 Este es un mensaje automático";

        return $message;
    }

    /**
     * Formatear número de teléfono con código de país
     */
    private function formatPhoneNumber($phone, $company)
    {
        // Limpiar el número de teléfono
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si el número empieza con 0, quitarlo
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        
        // Si el número no tiene código de país y la empresa tiene país asociado
        if (strlen($phone) <= 10 && $company->pais && $company->pais->codigo_telefonico) {
            $phone = $company->pais->codigo_telefonico . $phone;
        }
        
        return $phone;
    }
}