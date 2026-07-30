<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncCompanyToWhatsAppAPI extends Command
{
    protected $signature = 'whatsapp:sync-company {company_id=1}';
    protected $description = 'Sincroniza empresa con API de WhatsApp';

    public function handle()
    {
        $companyId = $this->argument('company_id');
        
        // Obtener empresa de vargasCentro
        $empresa = DB::table('empresas')->where('id', $companyId)->first();
        
        if (!$empresa) {
            $this->error("Empresa {$companyId} no encontrada");
            return;
        }

        // Insertar/actualizar en base de datos de WhatsApp API
        $whatsappDb = config('database.connections.whatsapp_api');
        $whatsappDb['database'] = 'larawhatsapp';
        
        config(['database.connections.whatsapp_temp' => $whatsappDb]);
        
        DB::connection('whatsapp_temp')->table('companies')->updateOrInsert(
            ['id' => $companyId],
            [
                'name' => $empresa->name ?? 'U.E JOSE MARIA VARGAS',
                'apiKey' => $empresa->api_key,
                'webhookUrl' => null,
                'rateLimitPerMinute' => 60,
                'isActive' => 1,
                'createdAt' => now(),
                'updatedAt' => now()
            ]
        );

        $this->info("✅ Empresa U.E JOSE MARIA VARGAS sincronizada con API de WhatsApp");
        $this->info("🔑 Token: {$empresa->api_key}");
    }
}