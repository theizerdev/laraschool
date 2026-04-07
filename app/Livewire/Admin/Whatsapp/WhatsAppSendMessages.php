<?php

namespace App\Livewire\Admin\Whatsapp;

use Livewire\Component;
use App\Traits\HasDynamicLayout;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WhatsAppSendMessages extends Component
{
    use HasDynamicLayout;

    public $jwtToken = null;
    public $status = 'disconnected';
    
    // Form fields
    public $recipient = '';
    public $message = '';
    public $selectedTemplate = null;
    public $templateVariables = [];
    
    // UI state
    public $isSending = false;
    public $sendSuccess = false;
    public $sendError = null;
    public $activeTab = 'manual';
    public $showPreview = false;
    public $messagePreview = '';
    
    // Data
    public $templates = [];
    public $recentContacts = [];
    public $students = [];
    public $messageHistory = [];
    
    // Bulk messaging
    public $bulkMode = false;
    public $selectedStudents = [];
    public $bulkMessage = '';

    protected $rules = [
        'recipient' => 'required_unless:bulkMode,true|string|min:10|max:20',
        'message' => 'required_if:activeTab,manual|string|min:1|max:1000',
        'selectedTemplate' => 'required_if:activeTab,template|exists:whatsapp_templates,id',
        'bulkMessage' => 'required_if:bulkMode,true|string|min:1|max:1000',
        'selectedStudents' => 'required_if:bulkMode,true|array|min:1',
    ];

    protected $messages = [
        'recipient.required' => 'El número de teléfono es requerido.',
        'recipient.min' => 'El número debe tener al menos 10 dígitos.',
        'recipient.max' => 'El número no puede exceder 20 dígitos.',
        'message.required_if' => 'El mensaje es requerido.',
        'message.min' => 'El mensaje debe tener al menos 1 carácter.',
        'message.max' => 'El mensaje no puede exceder 1000 caracteres.',
        'selectedTemplate.required_if' => 'Debes seleccionar una plantilla.',
        'selectedTemplate.exists' => 'La plantilla seleccionada no existe.',
    ];

    public function mount()
    {
        if (!Auth::user()->can('send whatsapp messages')) {
            abort(403, 'No tienes permiso para enviar mensajes de WhatsApp.');
        }
        
        $this->generateToken();
        $this->checkConnection();
        $this->loadTemplates();
        $this->loadRecentContacts();
        $this->loadStudents();
        $this->loadMessageHistory();
    }

    public function generateToken()
    {
        $this->jwtToken = config('whatsapp.api_key', 'test-api-key-vargas-centro');
    }

    public function checkConnection()
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->jwtToken
            ])->timeout(10)->get(config('whatsapp.api_url', 'http://localhost:3001') . '/api/whatsapp/status');

            if ($response->successful()) {
                $data = $response->json();
                $this->status = $data['connectionState'] ?? 'disconnected';
            } else {
                $this->status = 'error';
            }
        } catch (\Exception $e) {
            $this->status = 'error';
        }
    }

    public function loadTemplates()
    {
        try {
            $this->templates = DB::table('whatsapp_templates')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'category' => $template->category,
                        'content' => $template->content,
                        'variables' => json_decode($template->variables, true) ?? []
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->templates = [];
        }
    }

    public function loadRecentContacts()
    {
        try {
            $this->recentContacts = DB::table('whatsapp_messages')
                ->select('recipient', DB::raw('MAX(created_at) as last_message'))
                ->whereNotNull('recipient')
                ->groupBy('recipient')
                ->orderByDesc('last_message')
                ->limit(5)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $this->recentContacts = [];
        }
    }

    public function loadStudents()
    {
        try {
            $this->students = DB::table('students')
                ->select('id', 'nombres', 'apellidos', 'telefono', 'correo_electronico')
                ->whereNotNull('telefono')
                ->where('telefono', '!=', '')
                ->orderBy('nombres')
                ->limit(100)
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->nombres . ' ' . $student->apellidos,
                        'phone' => $student->telefono,
                        'email' => $student->correo_electronico
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->students = [];
        }
    }

    public function loadMessageHistory()
    {
        try {
            $this->messageHistory = DB::table('whatsapp_messages')
                ->select('recipient', 'message', 'status', 'created_at')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $this->messageHistory = [];
        }
    }

    public function updatedSelectedTemplate($templateId)
    {
        if ($templateId) {
            $template = collect($this->templates)->firstWhere('id', $templateId);
            if ($template && !empty($template['variables'])) {
                $this->templateVariables = array_fill_keys($template['variables'], '');
            } else {
                $this->templateVariables = [];
            }
            $this->updatePreview();
        } else {
            $this->templateVariables = [];
            $this->messagePreview = '';
        }
    }

    public function updatedTemplateVariables()
    {
        $this->updatePreview();
    }

    public function updatePreview()
    {
        if ($this->activeTab === 'template' && $this->selectedTemplate) {
            try {
                $this->messagePreview = $this->processTemplateMessage();
            } catch (\Exception $e) {
                $this->messagePreview = 'Error en la plantilla';
            }
        } else {
            $this->messagePreview = $this->message;
        }
    }

    public function updatedActiveTab($tab)
    {
        $this->resetErrorBag();
        $this->sendError = null;
    }

    public function selectRecentContact($recipient)
    {
        $this->recipient = $recipient;
        $this->dispatch('focusMessageInput');
    }

    public function selectStudent($studentId)
    {
        $student = collect($this->students)->firstWhere('id', $studentId);
        if ($student) {
            $this->recipient = $student['phone'];
            $this->dispatch('focusMessageInput');
        }
    }

    public function toggleBulkMode()
    {
        $this->bulkMode = !$this->bulkMode;
        $this->selectedStudents = [];
        $this->resetErrorBag();
    }

    public function toggleStudentSelection($studentId)
    {
        if (in_array($studentId, $this->selectedStudents)) {
            $this->selectedStudents = array_filter($this->selectedStudents, fn($id) => $id !== $studentId);
        } else {
            $this->selectedStudents[] = $studentId;
        }
    }

    public function selectAllStudents()
    {
        $this->selectedStudents = collect($this->students)->pluck('id')->toArray();
    }

    public function clearSelection()
    {
        $this->selectedStudents = [];
    }

    public function sendMessage()
    {
        //$this->validate();
        
        if ($this->status !== 'connected') {
            $this->sendError = 'WhatsApp no está conectado. Por favor, verifica la conexión primero.';
            return;
        }

        if ($this->bulkMode) {
            $this->sendBulkMessages();
        } else {
            $this->sendSingleMessage();
        }
    }

    private function sendSingleMessage()
    {
        $this->isSending = true;
        $this->sendError = null;

        try {
            $messageContent = $this->activeTab === 'template' && $this->selectedTemplate 
                ? $this->processTemplateMessage()
                : $this->message;

            $response = Http::withHeaders([
                'X-API-Key' => $this->jwtToken,
                'Content-Type' => 'application/json'
            ])->timeout(30)->post(config('whatsapp.api_url', 'http://localhost:3001') . '/api/whatsapp/send', [
                'to' => $this->formatPhoneNumber($this->recipient),
                'message' => $messageContent,
                'type' => 'text'
            ]);

            if ($response->successful()) {
                $this->sendSuccess = true;
                $this->sendError = null;
                \Log::info('WhatsApp message sent successfully', ['response' => $response->json()]);
                session()->flash('success', 'Mensaje enviado correctamente.');
                $this->loadRecentContacts();
                $this->loadMessageHistory();
                $this->resetForm();
            } else {
                $errorData = $response->json();
                $this->sendError = $errorData['error'] ?? $errorData['message'] ?? 'Error al enviar el mensaje.';
                \Log::error('WhatsApp send error', ['status' => $response->status(), 'response' => $errorData]);
            }
        } catch (\Exception $e) {
            $this->sendError = 'Error al enviar el mensaje: ' . $e->getMessage();
            \Log::error('WhatsApp send exception', ['error' => $e->getMessage()]);
        }

        $this->isSending = false;
    }

    private function sendBulkMessages()
    {
        $this->isSending = true;
        $this->sendError = null;
        $successCount = 0;
        $errorCount = 0;

        foreach ($this->selectedStudents as $studentId) {
            $student = collect($this->students)->firstWhere('id', $studentId);
            if (!$student) continue;

            try {
                $response = Http::withHeaders([
                    'X-API-Key' => $this->jwtToken,
                    'Content-Type' => 'application/json'
                ])->timeout(30)->post(config('whatsapp.api_url', 'http://localhost:3001') . '/api/whatsapp/send', [
                    'to' => $this->formatPhoneNumber($student['phone']),
                    'message' => $this->bulkMessage,
                    'type' => 'text'
                ]);

                if ($response->successful()) {
                    $successCount++;
                } else {
                    $errorCount++;
                }

                // Delay entre mensajes para evitar spam
                usleep(500000); // 0.5 segundos
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        $this->isSending = false;
        
        if ($successCount > 0) {
            session()->flash('success', "Mensajes enviados: {$successCount} exitosos, {$errorCount} fallidos.");
            $this->resetForm();
            $this->loadMessageHistory();
        } else {
            $this->sendError = 'No se pudo enviar ningún mensaje.';
        }
    }

    private function processTemplateMessage()
    {
        $template = collect($this->templates)->firstWhere('id', $this->selectedTemplate);
        if (!$template) {
            throw new \Exception('Plantilla no encontrada');
        }

        $content = $template['content'];
        foreach ($this->templateVariables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    private function formatPhoneNumber($number)
    {
        // Eliminar espacios y caracteres no numéricos
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Si no tiene código de país, agregar +58 (Venezuela)
        if (strlen($number) === 10) {
            $number = '58' . $number;
        }
        
        return '+' . $number;
    }

    public function resetForm()
    {
        $this->message = '';
        $this->bulkMessage = '';
        $this->selectedTemplate = null;
        $this->templateVariables = [];
        $this->selectedStudents = [];
        $this->messagePreview = '';
        $this->resetErrorBag();
    }

    public function clearMessages()
    {
        $this->sendSuccess = false;
        $this->sendError = null;
    }

    protected function getPageTitle(): string
    {
        return 'WhatsApp - Enviar Mensajes';
    }

    protected function getBreadcrumb(): array
    {
        return [
            'admin.dashboard' => 'Dashboard',
            'admin.whatsapp.dashboard' => 'WhatsApp',
            'admin.whatsapp.send-messages' => 'Enviar Mensajes'
        ];
    }

    public function render()
    {
        return $this->renderWithLayout('livewire.admin.whatsapp.whatsapp-send-messages', [
            'status' => $this->status,
            'recipient' => $this->recipient,
            'message' => $this->message,
            'selectedTemplate' => $this->selectedTemplate,
            'templateVariables' => $this->templateVariables,
            'activeTab' => $this->activeTab,
            'isSending' => $this->isSending,
            'sendSuccess' => $this->sendSuccess,
            'sendError' => $this->sendError,
            'templates' => $this->templates,
            'recentContacts' => $this->recentContacts,
            'students' => $this->students,
            'messageHistory' => $this->messageHistory,
            'bulkMode' => $this->bulkMode,
            'selectedStudents' => $this->selectedStudents,
            'bulkMessage' => $this->bulkMessage
        ], [
            'title' => 'WhatsApp - Enviar Mensajes',
            'description' => 'Envía mensajes de WhatsApp a tus contactos',
            'breadcrumb' => $this->getBreadcrumb()
        ]);
    }
}