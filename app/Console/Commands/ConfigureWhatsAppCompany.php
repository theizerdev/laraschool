<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Services\WhatsAppApiIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ConfigureWhatsAppCompany extends Command
{
    /**
     * The name and signature of the command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:configure-company 
                            {empresa_id : ID de la empresa a configurar}
                            {--register : Registrar la empresa en la API de WhatsApp}
                            {--force : Forzar la actualización incluso si ya está configurada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configurar una empresa específica para usar con la API de WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $empresaId = $this->argument('empresa_id');
        $register = $this->option('register');
        $force = $this->option('force');

        $this->info("🔧 Configurando empresa ID {$empresaId} para WhatsApp...");

        // Buscar la empresa
        $empresa = Empresa::find($empresaId);
        if (!$empresa) {
            $this->error("❌ No se encontró la empresa con ID {$empresaId}");
            return 1;
        }

        $this->info("📋 Empresa encontrada: {$empresa->razon_social}");

        // Verificar si ya tiene API key
        if ($empresa->whatsapp_api_key && !$force) {
            $this->warn("⚠️  La empresa ya tiene una API key configurada: " . substr($empresa->whatsapp_api_key, 0, 10) . '...');
            if (!$this->confirm('¿Desea regenerarla?')) {
                $this->info("Operación cancelada");
                return 0;
            }
        }

        // Generar nueva API key
        $this->info("🔄 Generando nueva API key...");
        $apiKey = $empresa->regenerateWhatsAppApiKey();
        $this->info("✅ API key generada: " . substr($apiKey, 0, 15) . '...');

        // Registrar en la API si se solicita
        if ($register) {
            $this->registerInWhatsAppApi($empresa, $apiKey);
        }

        // Verificar la conexión
        $this->info("🔍 Verificando conexión con la API...");
        $this->checkConnection($empresa);

        // Mostrar resumen
        $this->line("");
        $this->info("📊 Resumen de configuración:");
        $this->line("Empresa ID: {$empresa->id}");
        $this->line("Nombre: {$empresa->razon_social}");
        $this->line("API Key: " . substr($apiKey, 0, 15) . '...');
        $this->line("WhatsApp Activo: " . ($empresa->whatsapp_active ? 'Sí' : 'No'));

        return 0;
    }

    /**
     * Registrar la empresa en la API de WhatsApp
     */
    private function registerInWhatsAppApi(Empresa $empresa, string $apiKey): void
    {
        $this->info("📡 Registrando empresa en la API de WhatsApp...");

        try {
            $integrationService = new WhatsAppApiIntegrationService();
            $result = $integrationService->createCompany($empresa);

            if ($result) {
                $this->info("✅ Empresa registrada exitosamente en la API");
                $this->info("✅ API Key generada: " . substr($result, 0, 20) . '...');
            } else {
                $this->error("❌ Error al registrar empresa en la API");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error al registrar empresa: {$e->getMessage()}");
        }
    }

    /**
     * Verificar la conexión con la API
     */
    private function checkConnection(Empresa $empresa): void
    {
        try {
            // Crear servicio con la empresa específica
            $whatsappService = app(\App\Services\WhatsAppService::class);
            
            // Forzar la empresa específica
            $reflection = new \ReflectionClass($whatsappService);
            $companyIdProperty = $reflection->getProperty('companyId');
            $companyIdProperty->setAccessible(true);
            $companyIdProperty->setValue($whatsappService, $empresa->id);
            
            $apiKeyProperty = $reflection->getProperty('apiKey');
            $apiKeyProperty->setAccessible(true);
            $apiKeyProperty->setValue($whatsappService, $empresa->whatsapp_api_key);

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
                    $this->warn("🔑 La API key podría ser inválida o la empresa no está registrada");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Error al verificar conexión: {$e->getMessage()}");
        }
    }
}