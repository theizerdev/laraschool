<?php

namespace App\Livewire\Admin\Whatsapp;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use App\Models\WhatsAppMessage;
use App\Models\User;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $status = 'disconnected';
    public $user = null;
    public $lastSeen = null;
    public $jwtToken = null;
    public $messages = [];
    public $stats = [
        'sent' => 0,
        'delivered' => 0,
        'failed' => 0,
        'pending' => 0,
        'total' => 0
    ];
    public $dailyStats = [];
    public $weeklyStats = [];
    public $monthlyStats = [];
    public $topRecipients = [];
    public $recentActivity = [];

    public function mount()
    {
        $this->generateToken();
        $this->loadDashboard();
    }

    public function generateToken()
    {
        $jwtSecret = config('whatsapp.jwt_secret');
        $payload = [
            'company_id' => 1,
            'company_name' => 'U.E JOSE MARIA VARGAS',
            'iat' => time(),
            'exp' => time() + (365 * 24 * 60 * 60)
        ];
        $this->jwtToken = JWT::encode($payload, $jwtSecret, 'HS256');
    }

    public function loadDashboard()
    {
        $this->checkStatus();
        $this->loadMessages();
    }

    public function checkStatus()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->jwtToken
            ])->get(config('whatsapp.api_url') . '/api/whatsapp/status');

            if ($response->successful()) {
                $data = $response->json();
                $this->status = $data['connectionState'] ?? 'disconnected';
                $this->user = $data['user'] ?? null;
                $this->lastSeen = $data['lastSeen'] ?? null;
            }
        } catch (\Exception $e) {
            // Error silencioso
        }
    }

    public function loadMessages()
    {
        // Cargar mensajes recientes de la base de datos
        $this->messages = WhatsAppMessage::with(['creator', 'template'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message_id' => $message->message_id,
                    'recipient_phone' => $message->recipient_phone,
                    'recipient_name' => $message->recipient_name,
                    'message_content' => $message->message_content,
                    'status' => $message->status,
                    'direction' => $message->direction,
                    'created_at' => $message->created_at,
                    'sent_at' => $message->sent_at,
                    'creator' => $message->creator ? $message->creator->name : 'Sistema',
                    'error_message' => $message->error_message,
                    'retry_count' => $message->retry_count ?? 0
                ];
            })->toArray();

        // Calcular estadísticas generales
        $this->stats = [
            'sent' => WhatsAppMessage::where('status', 'sent')->count(),
            'delivered' => WhatsAppMessage::where('status', 'delivered')->count(),
            'failed' => WhatsAppMessage::where('status', 'failed')->count(),
            'pending' => WhatsAppMessage::where('status', 'pending')->count(),
            'total' => WhatsAppMessage::count()
        ];

        // Estadísticas diarias (últimos 7 días)
        $this->dailyStats = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::now()->subDays($daysAgo);
            return [
                'date' => $date->format('d/m'),
                'sent' => WhatsAppMessage::whereDate('created_at', $date)->where('status', 'sent')->count(),
                'failed' => WhatsAppMessage::whereDate('created_at', $date)->where('status', 'failed')->count()
            ];
        })->toArray();

        // Estadísticas semanales (últimas 4 semanas)
        $this->weeklyStats = collect(range(3, 0))->map(function ($weeksAgo) {
            $startOfWeek = Carbon::now()->subWeeks($weeksAgo)->startOfWeek();
            $endOfWeek = Carbon::now()->subWeeks($weeksAgo)->endOfWeek();
            return [
                'week' => 'Sem ' . ($weeksAgo + 1),
                'sent' => WhatsAppMessage::whereBetween('created_at', [$startOfWeek, $endOfWeek])->where('status', 'sent')->count(),
                'failed' => WhatsAppMessage::whereBetween('created_at', [$startOfWeek, $endOfWeek])->where('status', 'failed')->count()
            ];
        })->toArray();

        // Estadísticas mensuales (últimos 6 meses)
        $this->monthlyStats = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = Carbon::now()->subMonths($monthsAgo);
            return [
                'month' => $date->format('M'),
                'sent' => WhatsAppMessage::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('status', 'sent')->count(),
                'failed' => WhatsAppMessage::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('status', 'failed')->count()
            ];
        })->toArray();

        // Top destinatarios (más mensajes enviados)
        $this->topRecipients = WhatsAppMessage::selectRaw('recipient_phone, recipient_name, COUNT(*) as total_messages, 
                MAX(created_at) as last_message')
            ->groupBy('recipient_phone', 'recipient_name')
            ->orderBy('total_messages', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($recipient) {
                return [
                    'phone' => $recipient->recipient_phone,
                    'name' => $recipient->recipient_name ?: 'Sin nombre',
                    'total_messages' => $recipient->total_messages,
                    'last_message' => Carbon::parse($recipient->last_message)->diffForHumans()
                ];
            })->toArray();

        // Actividad reciente (últimas 24 horas)
        $this->recentActivity = WhatsAppMessage::with('creator')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($message) {
                return [
                    'time' => $message->created_at->diffForHumans(),
                    'action' => $this->getActionText($message),
                    'status' => $message->status,
                    'user' => $message->creator ? $message->creator->name : 'Sistema'
                ];
            })->toArray();
    }

    private function getActionText($message)
    {
        $phone = substr($message->recipient_phone, -4);
        $name = $message->recipient_name ? $message->recipient_name : "***{$phone}";
        
        switch ($message->status) {
            case 'sent':
                return "Mensaje enviado a {$name}";
            case 'delivered':
                return "Mensaje entregado a {$name}";
            case 'failed':
                return "Falló envío a {$name}";
            case 'pending':
                return "Mensaje pendiente para {$name}";
            default:
                return "Actividad con {$name}";
        }
    }

    public function refresh()
    {
        $this->loadDashboard();
        $this->dispatch('dashboard-refreshed');
    }

    public function render()
    {
        return view('livewire.admin.whatsapp.dashboard');
    }
}