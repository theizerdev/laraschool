<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $baseUrl;
    private $apiKey;
    private $companyId;
    private $timeout;

    /**
     * Constructor del servicio WhatsApp
     * 
     * @param Empresa|int|null $empresa - Empresa, ID de empresa, o null para usar la del usuario actual
     */
    public function __construct($empresa = null)
    {
        $this->baseUrl = config('whatsapp.api_url', 'http://localhost:3001');
        $this->timeout = config('whatsapp.timeout', 30);
        
        // Resolver la empresa y obtener su API key
        $this->resolveCompany($empresa);
    }

    /**
     * Resuelve la empresa y configura la API key
     */
    private function resolveCompany($empresa = null): void
    {
        // Si se pasa una empresa directamente
        if ($empresa instanceof Empresa) {
            $this->companyId = $empresa->id;
            $this->apiKey = $empresa->whatsapp_api_key;
            return;
        }

        // Si se pasa un ID de empresa
        if (is_numeric($empresa)) {
            $empresaModel = Empresa::find($empresa);
            if ($empresaModel) {
                $this->companyId = $empresaModel->id;
                $this->apiKey = $empresaModel->whatsapp_api_key;
                return;
            }
        }

        // Intentar obtener la empresa del usuario autenticado
        if (auth()->check() && auth()->user()->empresa_id) {
            $empresaModel = Empresa::find(auth()->user()->empresa_id);
            if ($empresaModel) {
                $this->companyId = $empresaModel->id;
                $this->apiKey = $empresaModel->whatsapp_api_key;
                return;
            }
        }

        // Fallback a la configuración global (para compatibilidad)
        $this->companyId = 1;
        $this->apiKey = config('whatsapp.api_key', 'test-api-key-vargas-centro');
    }

    /**
     * Obtiene los headers necesarios para la API
     */
    private function getHeaders(): array
    {
        return [
            'X-API-Key' => $this->apiKey,
            'X-Company-Id' => (string) $this->companyId,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Crea una instancia del servicio para una empresa específica
     */
    public static function forCompany($empresa): self
    {
        return new self($empresa);
    }

    /**
     * Obtener el estado de la conexión WhatsApp
     */
    public function getStatus()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/api/whatsapp/status");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('WhatsApp Status Error: ' . $e->getMessage(), [
                'company_id' => $this->companyId
            ]);
            return null;
        }
    }

    /**
     * Obtener código QR para conectar WhatsApp
     */
    public function getQRCode()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/api/whatsapp/qr");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('WhatsApp QR Error: ' . $e->getMessage(), [
                'company_id' => $this->companyId
            ]);
            return null;
        }
    }

    /**
     * Enviar mensaje de texto
     */
    public function sendMessage(string $to, string $message)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/api/whatsapp/send", [
                    'to' => $to,
                    'message' => $message,
                    'type' => 'text'
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp mensaje enviado', [
                    'company_id' => $this->companyId,
                    'to' => $to,
                    'message_id' => $response->json('messageId')
                ]);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Message Error: ' . $e->getMessage(), [
                'company_id' => $this->companyId,
                'to' => $to
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar documento (PDF, Excel, Word, etc.)
     */
    public function sendDocument(string $to, string $filePath, string $caption = '')
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'X-Company-Id' => (string) $this->companyId,
                ])
                ->attach('document', file_get_contents($filePath), basename($filePath))
                ->post("{$this->baseUrl}/api/whatsapp/send-document", [
                    'to' => $to,
                    'caption' => $caption
                ]);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Document Error: ' . $e->getMessage(), [
                'company_id' => $this->companyId,
                'to' => $to
            ]);
            return null;
        }
    }

    /**
     * Obtener historial de mensajes
     */
    public function getMessages(array $filters = [])
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/api/whatsapp/messages", $filters);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('WhatsApp Get Messages Error: ' . $e->getMessage(), [
                'company_id' => $this->companyId
            ]);
            return null;
        }
    }

    /**
     * Conectar WhatsApp
     */
    public function connect()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/api/whatsapp/connect");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('WhatsApp Connect Error: ' . $e->getMessage(), [
                'company_id' => $this->companyId
            ]);
            return null;
        }
    }

    /**
     * Desconectar WhatsApp
     */
    public function disconnect()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->delete("{$this->baseUrl}/api/whatsapp/disconnect");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('WhatsApp Disconnect Error: ' . $e->getMessage(), [
                'company_id' => $this->companyId
            ]);
            return null;
        }
    }

    /**
     * Reconectar WhatsApp
     */
    public function reconnect()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/api/whatsapp/reconnect");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('WhatsApp Reconnect Error: ' . $e->getMessage(), [
                'company_id' => $this->companyId
            ]);
            return null;
        }
    }

    /**
     * Eliminar sesión (logout completo)
     */
    public function removeSession()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->delete("{$this->baseUrl}/api/whatsapp/session");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('WhatsApp Remove Session Error: ' . $e->getMessage(), [
                'company_id' => $this->companyId
            ]);
            return null;
        }
    }

    /**
     * Obtener estadísticas del manager (todas las empresas)
     */
    public function getManagerStats()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/api/whatsapp/manager/stats");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('WhatsApp Manager Stats Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene el ID de la empresa actual
     */
    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    /**
     * Verifica si el servicio tiene configuración válida
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->companyId);
    }
}