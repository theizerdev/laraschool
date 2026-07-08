<?php

namespace App\Livewire\Admin\Students;

use App\Traits\HasDynamicLayout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Student;
use App\Models\StudentAccessLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Notification;

class QrAccess extends Component
{


    use WithPagination,HasDynamicLayout;



    public $search = '';
    public $selectedStudent = null;
    public $accessType = 'entrada'; // entrada o salida
    public $notes = '';
    public $soundEnabled = true;
    public $scanMode = 'camera'; // camera o manual
    public $manualCode = '';
    public $showStudentInfo = false;
    public $todayLogs = [];
    public $stats = [
        'entries' => 0,
        'exits' => 0,
        'total' => 0,
        'activeStudents' => 0
    ];
    public $processing = false;
    public $lastProcessedCode = null;
    public $lastProcessedTime = null;
    public $whatsappStatus = 'checking';
    public $whatsappConnected = false;

    protected $listeners = ['qr-scanned' => 'processQrScan'];

    public function mount()
    {
        // Verificar permiso para acceder al control de acceso
        if (!Auth::user()->can('access students')) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        // Cargar estadísticas iniciales
        $this->loadStats();
        $this->loadTodayLogs();
        
        // Verificar estado de WhatsApp
        $this->checkWhatsAppStatus();
    }

    public function loadStats()
    {
        $today = Carbon::today();

        $this->stats['entries'] = StudentAccessLog::whereDate('access_time', $today)
            ->where('type', 'entrada')
            ->count();

        $this->stats['exits'] = StudentAccessLog::whereDate('access_time', $today)
            ->where('type', 'salida')
            ->count();

        $this->stats['total'] = $this->stats['entries'] + $this->stats['exits'];

        $this->stats['activeStudents'] = Student::where('status', 1)->count();
    }

    public function loadTodayLogs()
    {
        $this->todayLogs = StudentAccessLog::with(['student', 'registeredBy'])
            ->whereDate('access_time', Carbon::today())
            ->orderBy('access_time', 'desc')
            ->limit(20)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'access_time' => $log->access_time,
                    'type' => $log->type,
                    'notes' => $log->notes,
                    'student' => $log->student ? [
                        'nombres' => $log->student->nombres,
                        'apellidos' => $log->student->apellidos,
                        'codigo' => $log->student->codigo,
                    ] : null,
                    'registered_by_user' => $log->registeredBy ? [
                        'name' => $log->registeredBy->name,
                    ] : null,
                ];
            })
            ->toArray();
    }

    public function processQrScan($qrData)
    {
        // Extraer el código del estudiante del QR
        $code = $this->extractStudentCode($qrData);

        if ($code) {
            $this->findStudentByCode($code);
        } else {
            $this->dispatch('show-error', 'Código QR no válido');
            $this->playSound('error');
        }
    }

    public function extractStudentCode($qrData)
    {
        // El formato del QR es: "Código: XXXXX\nNombre: ..."
        if (preg_match('/Código:\s*([\w\d]+)/', $qrData, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function searchByManualCode()
    {
        if (empty($this->manualCode)) {
            $this->dispatch('show-error', 'Por favor ingrese un código');
            return;
        }

        $this->findStudentByCode($this->manualCode);
        $this->manualCode = ''; // Limpiar el campo después de procesar
    }

    public function findStudentByCode($code)
    {
        // Prevenir procesamiento múltiple rápido
        if ($this->processing) {
            return;
        }

        // Prevenir el mismo código en menos de 3 segundos
        if ($this->lastProcessedCode === $code &&
            $this->lastProcessedTime &&
            now()->diffInSeconds($this->lastProcessedTime) < 3) {
            $this->dispatch('show-error', 'Espere 3 segundos antes de volver a escanear el mismo código');
            return;
        }

        $this->processing = true;

        $student = Student::with(['nivelEducativo', 'turno'])
            ->where('codigo', $code)
            ->where('status', 1)
            ->first();

        if (!$student) {
            $this->dispatch('show-error', 'Estudiante no encontrado o inactivo');
            $this->playSound('error');
            $this->processing = false;
            return;
        }

        $this->selectedStudent = $student;
        $this->determineAccessType($student);

        // Registrar acceso directamente sin mostrar información
        $this->registerAccessDirect();

        // Guardar último código procesado
        $this->lastProcessedCode = $code;
        $this->lastProcessedTime = now();

        $this->processing = false;
    }

    public function registerAccessDirect()
    {
        if (!$this->selectedStudent) {
            return;
        }

        $accessLog = StudentAccessLog::create([
            'student_id' => $this->selectedStudent->id,
            'type' => $this->accessType,
            'access_time' => now(),
            'registered_by' => auth()->id(),
            'notes' => ''
        ]);

        $this->sendAccessNotification($accessLog);

        // Crear notificación
        Notification::create([
            'user_id' => auth()->id(),
            'type' => $this->accessType === 'entrada' ? 'info' : 'warning',
            'title' => ucfirst($this->accessType) . ' registrada',
            'message' => "{$this->selectedStudent->nombres} {$this->selectedStudent->apellidos} - " . ucfirst($this->accessType) . " registrada a las " . now()->format('H:i'),
            'data' => ['student_id' => $this->selectedStudent->id, 'access_log_id' => $accessLog->id]
        ]);

        $this->dispatch('notification-created');
        $this->dispatch('show-success', ucfirst($this->accessType) . ' registrada: ' . $this->selectedStudent->nombres . ' ' . $this->selectedStudent->apellidos);
        $this->playSound('notification');
        $this->resetForm();
        $this->loadStats();
        $this->loadTodayLogs();
    }

    public function determineAccessType($student)
    {
        $today = Carbon::today();

        // Verificar si hay entradas sin salida de días anteriores
        $incompleteEntry = StudentAccessLog::where('student_id', $student->id)
            ->whereDate('access_time', '<', $today)
            ->where('type', 'entrada')
            ->whereNotExists(function($query) use ($student) {
                $query->select('id')
                    ->from('student_access_logs as sal')
                    ->whereColumn('sal.student_id', 'student_access_logs.student_id')
                    ->whereColumn('sal.access_time', '>', 'student_access_logs.access_time')
                    ->whereRaw('DATE(sal.access_time) = DATE(student_access_logs.access_time)')
                    ->where('sal.type', 'salida');
            })
            ->orderBy('access_time', 'desc')
            ->first();

        if ($incompleteEntry) {
            // Registrar salida automática del día anterior
            $exitTime = Carbon::parse($incompleteEntry->access_time)->endOfDay()->subMinutes(30);

            StudentAccessLog::create([
                'student_id' => $student->id,
                'type' => 'salida',
                'access_time' => $exitTime,
                'registered_by' => auth()->id(),
                'notes' => 'Salida automática - entrada sin salida detectada del ' . $incompleteEntry->access_time->format('d/m/Y')
            ]);

            // Crear notificación sobre la corrección
            Notification::create([
                'user_id' => auth()->id(),
                'type' => 'warning',
                'title' => 'Salida automática registrada',
                'message' => "Se registró salida automática para {$student->nombres} {$student->apellidos} del día {$incompleteEntry->access_time->format('d/m/Y')}",
                'data' => ['student_id' => $student->id, 'auto_exit' => true]
            ]);
        }

        // Obtener el último acceso del día actual
        $lastAccess = StudentAccessLog::where('student_id', $student->id)
            ->whereDate('access_time', $today)
            ->orderBy('access_time', 'desc')
            ->first();

        // Si no hay accesos hoy o el último fue salida, sugerir entrada
        // Si el último fue entrada, sugerir salida
        if (!$lastAccess || $lastAccess->type === 'salida') {
            $this->accessType = 'entrada';
        } else {
            $this->accessType = 'salida';
        }
    }

    public function registerAccess()
    {
        if (!$this->selectedStudent) {
            $this->dispatch('show-error', 'No hay estudiante seleccionado');
            return;
        }

        $accessLog = StudentAccessLog::create([
            'student_id' => $this->selectedStudent->id,
            'type' => $this->accessType,
            'access_time' => now(),
            'registered_by' => Auth::id(),
            'notes' => $this->notes
        ]);

        $this->sendAccessNotification($accessLog);

        // Crear notificación
        Notification::create([
            'user_id' => auth()->id(),
            'type' => $this->accessType === 'entrada' ? 'info' : 'warning',
            'title' => ucfirst($this->accessType) . ' registrada',
            'message' => "{$this->selectedStudent->nombres} {$this->selectedStudent->apellidos} - " . ucfirst($this->accessType) . " registrada a las " . now()->format('H:i'),
            'data' => ['student_id' => $this->selectedStudent->id, 'access_log_id' => $accessLog->id]
        ]);

        $this->dispatch('notification-created');
        $this->dispatch('show-success', $this->accessType . ' registrada correctamente');
        $this->playSound('notification');
        $this->resetForm();
        $this->loadStats();
        $this->loadTodayLogs();
    }

    private function sendAccessNotification($accessLog)
    {
        // Enviar notificación por correo electrónico
        if ($this->selectedStudent->representante_correo) {
            try {
                $student = Student::with(['nivelEducativo', 'turno'])->find($this->selectedStudent->id);
                $timeInSchool = null;

                if ($accessLog->type === 'salida') {
                    $entryLog = StudentAccessLog::where('student_id', $student->id)
                        ->whereDate('access_time', Carbon::today())
                        ->where('type', 'entrada')
                        ->orderBy('access_time', 'desc')
                        ->first();

                    if ($entryLog) {
                        $entryTime = Carbon::parse($entryLog->access_time);
                        $exitTime = Carbon::parse($accessLog->access_time);
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

                \Mail::to($student->representante_correo)
                    ->send(new \App\Mail\StudentAccessNotificationMail($student, $accessLog, $timeInSchool));

            } catch (\Exception $e) {
                \Log::error('Error enviando notificación por correo: ' . $e->getMessage());
            }
        }

        // Enviar notificación por WhatsApp si el estudiante es menor de edad
        if ($this->selectedStudent->es_menor_de_edad && $this->selectedStudent->representante_telefonos) {
            try {
                // Obtener la empresa del estudiante o del usuario autenticado
                $company = $this->selectedStudent->empresa ?? auth()->user()->empresa;
                
                if ($company) {
                    // Enviar notificación WhatsApp directamente (sin usar colas)
                    $whatsappService = app(\App\Services\WhatsAppService::class);
                    $sentCount = 0;
                    $failedCount = 0;

                    // Procesar teléfonos (manejar string o array)
                    $telefonos = $this->selectedStudent->representante_telefonos;
                    if (is_string($telefonos)) {
                        // Si es string, intentar decodificar JSON o separar por comas
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
                            $telefonoFormateado = $this->formatPhoneNumber($telefono, $company);
                            
                            // Construir mensaje
                            $message = $this->buildWhatsAppMessage($this->selectedStudent, $accessLog);
                            
                            // Enviar mensaje
                            $resultado = $whatsappService->sendMessage($telefonoFormateado, $message);
                            
                            if ($resultado['success']) {
                                $sentCount++;
                                \Log::info('Notificación WhatsApp enviada exitosamente', [
                                    'student_id' => $this->selectedStudent->id,
                                    'phone' => $telefonoFormateado,
                                    'message_id' => $resultado['message_id'] ?? null
                                ]);
                            } else {
                                $failedCount++;
                                \Log::error('Error al enviar notificación WhatsApp', [
                                    'student_id' => $this->selectedStudent->id,
                                    'phone' => $telefonoFormateado,
                                    'error' => $resultado['error'] ?? 'Error desconocido'
                                ]);
                            }
                        } catch (\Exception $e) {
                            $failedCount++;
                            \Log::error('Excepción al enviar notificación WhatsApp', [
                                'student_id' => $this->selectedStudent->id,
                                'phone' => $telefono,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    \Log::info('Resumen de envío de notificaciones WhatsApp', [
                        'student_id' => $this->selectedStudent->id,
                        'student_name' => $this->selectedStudent->nombres . ' ' . $this->selectedStudent->apellidos,
                        'access_type' => $accessLog->type,
                        'total_phones' => count($telefonos),
                        'sent_count' => $sentCount,
                        'failed_count' => $failedCount
                    ]);

                } else {
                    \Log::warning('No se pudo obtener la empresa para la notificación WhatsApp', [
                        'student_id' => $this->selectedStudent->id
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error enviando notificación WhatsApp: ' . $e->getMessage(), [
                    'student_id' => $this->selectedStudent->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function resetForm()
    {
        $this->selectedStudent = null;
        $this->accessType = 'entrada';
        $this->notes = '';
        $this->manualCode = '';
        $this->showStudentInfo = false;
        $this->processing = false;
    }

    public function toggleSound()
    {
        $this->soundEnabled = !$this->soundEnabled;
    }

    public function playSound($type)
    {
        if (!$this->soundEnabled) return;

        $this->dispatch('play-sound', $type);
    }

    public function deleteLog($logId)
    {
        // Solo administradores pueden eliminar registros
        if (!Auth::user()->hasRole('Admin')) {
            $this->dispatch('show-error', 'No tienes permiso para eliminar registros');
            return;
        }

        $log = StudentAccessLog::find($logId);
        if ($log) {
            $log->delete();
            $this->dispatch('show-success', 'Registro eliminado correctamente');
            $this->loadStats();
            $this->loadTodayLogs();
        }
    }

    /**
     * Verificar el estado de conexión de WhatsApp
     */
    public function checkWhatsAppStatus()
    {
        try {
            $whatsappService = app(\App\Services\WhatsAppService::class);
            $status = $whatsappService->getStatus();
            
            \Log::info('WhatsApp Status Response:', ['status' => $status]);
            
            if ($status && isset($status['connected'])) {
                $this->whatsappConnected = $status['connected'];
                $this->whatsappStatus = $status['connected'] ? 'connected' : 'disconnected';
            } elseif ($status && isset($status['isConnected'])) {
                // Algunas versiones usan isConnected en lugar de connected
                $this->whatsappConnected = $status['isConnected'];
                $this->whatsappStatus = $status['isConnected'] ? 'connected' : 'disconnected';
            } else {
                $this->whatsappConnected = false;
                $this->whatsappStatus = 'error';
                \Log::warning('WhatsApp status no válido:', ['status' => $status]);
            }
        } catch (\Exception $e) {
            \Log::error('Error al verificar estado de WhatsApp: ' . $e->getMessage());
            $this->whatsappConnected = false;
            $this->whatsappStatus = 'error';
        }
    }

    public function render()
    {
        return $this->renderWithLayout('livewire.admin.students.qr-access', [], [
            'title' => 'Control de Acceso QR',
            'description' => 'Control de entrada y salida de estudiantes',
            'breadcrumb' => [
                'admin.dashboard' => 'Dashboard',
                'admin.students.qr-access' => 'Control QR'
            ]
        ]);
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

    /**
     * Construir mensaje de WhatsApp para notificación de acceso
     */
    private function buildWhatsAppMessage($student, $accessLog)
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
        $message .= "\n🏫 U.E JOSE MARIA VARGAS\n";
        $message .= "💡 Este es un mensaje automático";

        return $message;
    }
}