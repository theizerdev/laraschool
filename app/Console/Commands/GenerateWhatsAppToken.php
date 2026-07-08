<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;

class GenerateWhatsAppToken extends Command
{
    protected $signature = 'whatsapp:generate-token {company_id=1}';
    protected $description = 'Genera JWT token para empresa WhatsApp';

    public function handle()
    {
        $companyId = $this->argument('company_id');
        $jwtSecret = config('whatsapp.jwt_secret', 'base64:ItiVlmjSSgrh2LFDfR0JGtPXHRAthPOWSMw6WyrgwIk=');
        
        $payload = [
            'company_id' => (int)$companyId,
            'company_name' => 'U.E JOSE MARIA VARGAS',
            'iat' => time(),
            'exp' => time() + (365 * 24 * 60 * 60) // 1 año
        ];

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        // Actualizar campo api_key en la tabla empresas
        DB::table('empresas')
            ->where('id', $companyId)
            ->update(['api_key' => $token]);

        $this->info("✅ JWT Token generado y guardado para empresa {$companyId}:");
        $this->line($token);
        
        $this->info("\nUsar en headers:");
        $this->line("Authorization: Bearer {$token}");
    }
}