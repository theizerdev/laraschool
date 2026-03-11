<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    private $apiUrl;
    private $timeout;
    
    public function __construct()
    {
        $this->apiUrl = config('app.whatsapp_api_url', 'http://localhost:3002');
        $this->timeout = 30; // segundos
    }
    
    /**
     * Enviar mensaje de WhatsApp
     */
    public function sendMessage($phone, $message, $apiKey)
    {
        try {
            $url = $this->apiUrl . '/api/whatsapp/send';
            
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'phone' => $phone,
                    'message' => $message,
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['success']) && $data['success']) {
                    return [
                        'success' => true,
                        'message_id' => $data['message_id'] ?? null,
                    ];
                }
            }
            
            return [
                'success' => false,
                'error' => 'Error HTTP ' . $response->status() . ': ' . $response->body(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje WhatsApp: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Obtener estado del servicio WhatsApp
     */
    public function getStatus($apiKey)
    {
        try {
            $url = $this->apiUrl . '/api/whatsapp/status';
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                ])
                ->get($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estado WhatsApp: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Formatear número de teléfono
     */
    public function formatPhoneNumber($telefono, $countryCode = '51')
    {
        // Eliminar espacios y caracteres especiales
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);
        
        // Si ya tiene el símbolo +, asumimos que está en formato internacional
        if (str_starts_with($telefono, '+')) {
            return $telefono;
        }

        // Si el teléfono tiene la longitud estándar sin código de país (ej. 9 dígitos para Perú)
        // y no empieza con el código de país, se lo agregamos
        if (strlen($telefono) >= 9 && !str_starts_with($telefono, $countryCode)) {
            return '+' . $countryCode . $telefono;
        }

        // Si ya empieza con el código de país pero sin el +, se lo agregamos
        if (str_starts_with($telefono, $countryCode)) {
            return '+' . $telefono;
        }
        
        // Por defecto retornamos con el código configurado
        return '+' . $countryCode . $telefono;
    }
        
        // Validar formato
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $telefono)) {
            return false;
        }
        
        return $telefono;
    }
    
    /**
     * Generar mensaje de bienvenida para médico
     */
    public function generateDoctorWelcomeMessage($medico)
    {
        $nombreCompleto = $medico->nombres . ' ' . $medico->apellidos;
        $especialidad = $medico->especialidades->first() ? $medico->especialidades->first()->nombre : 'Médico';
        $empresa = $medico->empresa ? $medico->empresa->razon_social : 'la empresa';
        
        return "¡Hola Dr(a). {$nombreCompleto}! 🏥\n\n" .
               "Bienvenido(a) al sistema de {$empresa}.\n\n" .
               "Como {$especialidad}, su cuenta ha sido activada exitosamente.\n\n" .
               "📋 Puede acceder al sistema con su documento: {$medico->documento_identidad}\n" .
               "🔒 Su cuenta está protegida con los más altos estándares de seguridad.\n\n" .
               "¡Gracias por formar parte de nuestro equipo! 🩺\n\n" .
               "Atentamente,\nEl equipo de {$empresa}";
    }
}