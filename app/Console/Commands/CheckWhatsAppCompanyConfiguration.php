<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckWhatsAppCompanyConfiguration extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:check-company {--empresa-id= : ID de la empresa a verificar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar y configurar la empresa correcta para la API de WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando configuración de empresas para WhatsApp...');
        
        // Obtener todas las empresas
        $empresas = Empresa::all();
        
        if ($empresas->isEmpty()) {
            $this->error('❌ No se encontraron empresas en la base de datos');
            return 1;
        }

        $this->info("📋 Se encontraron {$empresas->count()} empresas:");
        
        foreach ($empresas as $empresa) {
            $this->line("");
            $this->line("ID: {$empresa->id}");
            $this->line("Razón Social: {$empresa->razon_social}");
            $this->line("API Key: " . ($empresa->whatsapp_api_key ? '✅ Configurada' : '❌ No configurada'));
            $this->line("WhatsApp Activo: " . ($empresa->whatsapp_active ? '✅ Sí' : '❌ No'));
            $this->line("Estado WhatsApp: {$empresa->whatsapp_status}");
            $this->line("Teléfono: " . ($empresa->whatsapp_phone ?: 'No configurado'));
            
            // Verificar conexión con la API
            if ($empresa->whatsapp_api_key) {
                $this->checkApiConnection($empresa);
            }
        }

        // Si se especifica un ID de empresa, verificar específicamente
        $empresaId = $this->option('empresa-id');
        if ($empresaId) {
            $empresa = Empresa::find($empresaId);
            if ($empresa) {
                $this->line("");
                $this->info("🔍 Verificación detallada de empresa ID {$empresaId}:");
                $this->checkDetailedConfiguration($empresa);
            } else {
                $this->error("❌ No se encontró la empresa con ID {$empresaId}");
            }
        }

        // Mostrar configuración actual del .env
        $this->line("");
        $this->info('📄 Configuración actual del .env:');
        $this->line("WHATSAPP_API_URL: " . config('whatsapp.api_url'));
        $this->line("WHATSAPP_API_KEY: " . config('whatsapp.api_key'));

        return 0;
    }

    /**
     * Verificar la conexión con la API de WhatsApp
     */
    private function checkApiConnection(Empresa $empresa): void
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $empresa->whatsapp_api_key,
                'X-Company-Id' => (string) $empresa->id,
            ])->timeout(5)->get(config('whatsapp.api_url') . '/api/whatsapp/status');

            if ($response->successful()) {
                $this->info("✅ Conexión exitosa con la API WhatsApp");
                $data = $response->json();
                if (isset($data['connection'])) {
                    $this->line("Estado de conexión: {$data['connection']}");
                }
            } else {
                $this->error("❌ Error en la conexión: HTTP {$response->status()}");
                if ($response->status() === 401) {
                    $this->warn("🔑 La API key podría ser inválida o la empresa no está registrada en la API");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Error al conectar con la API: {$e->getMessage()}");
        }
    }

    /**
     * Verificación detallada de la configuración
     */
    private function checkDetailedConfiguration(Empresa $empresa): void
    {
        $this->line("Razón Social: {$empresa->razon_social}");
        $this->line("API Key WhatsApp: {$empresa->whatsapp_api_key}");
        $this->line("Empresa ID: {$empresa->id}");
        
        // Verificar si el usuario autenticado tiene esta empresa
        if (auth()->check()) {
            $this->line("Usuario autenticado - Empresa ID: " . (auth()->user()->empresa_id ?: 'No asignada'));
            if (auth()->user()->empresa_id == $empresa->id) {
                $this->info("✅ El usuario autenticado pertenece a esta empresa");
            } else {
                $this->warn("⚠️  El usuario autenticado NO pertenece a esta empresa");
            }
        }

        // Probar el servicio de WhatsApp
        try {
            $whatsappService = app(\App\Services\WhatsAppService::class);
            $this->line("Servicio WhatsApp - Company ID: {$whatsappService->getCompanyId()}");
            $this->line("Servicio WhatsApp - API Key: " . substr($whatsappService->getApiKey(), 0, 10) . '...');
        } catch (\Exception $e) {
            $this->error("Error al crear el servicio WhatsApp: {$e->getMessage()}");
        }
    }
}