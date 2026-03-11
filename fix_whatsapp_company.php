<?php

/**
 * Script para solucionar el problema de WhatsApp API Company ID
 * 
 * Problema: Laravel está usando empresa ID 1 pero la API WhatsApp espera empresa ID 2
 * Este script configura la empresa correcta y verifica la conexión
 */

require __DIR__.'/vendor/autoload.php';

use App\Models\Empresa;
use Illuminate\Support\Facades\Http;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 Script de configuración WhatsApp API\n";
echo "==========================================\n\n";

// Paso 1: Verificar empresas existentes
echo "📋 Paso 1: Verificando empresas existentes...\n";
$empresas = Empresa::all();

if ($empresas->isEmpty()) {
    echo "❌ No se encontraron empresas en la base de datos\n";
    exit(1);
}

echo "Se encontraron {$empresas->count()} empresas:\n";
foreach ($empresas as $empresa) {
    echo "  - ID: {$empresa->id} | Nombre: {$empresa->razon_social}\n";
    echo "    API Key: " . ($empresa->whatsapp_api_key ? substr($empresa->whatsapp_api_key, 0, 15).'...' : 'No configurada') . "\n";
}

// Paso 2: Identificar la empresa correcta
echo "\n🔍 Paso 2: Identificando empresa correcta para WhatsApp...\n";

// Buscar empresa con ID 2 (la que menciona el usuario)
$empresa2 = Empresa::find(2);
$empresa1 = Empresa::find(1);

if ($empresa2) {
    echo "✅ Encontrada empresa ID 2: {$empresa2->razon_social}\n";
    $empresaCorrecta = $empresa2;
} else {
    echo "⚠️  No se encontró empresa ID 2\n";
    if ($empresa1) {
        echo "ℹ️  Usando empresa ID 1: {$empresa1->razon_social}\n";
        $empresaCorrecta = $empresa1;
    } else {
        echo "❌ No se encontró ninguna empresa válida\n";
        exit(1);
    }
}

// Paso 3: Configurar API key para la empresa correcta
echo "\n🔑 Paso 3: Configurando API key para empresa ID {$empresaCorrecta->id}...\n";

$apiKey = 'test-api-key-vargas-centro'; // La misma que está en .env
if (empty($empresaCorrecta->whatsapp_api_key)) {
    $empresaCorrecta->whatsapp_api_key = $apiKey;
    $empresaCorrecta->save();
    echo "✅ API key configurada: {$apiKey}\n";
} else {
    echo "ℹ️  La empresa ya tiene API key: " . substr($empresaCorrecta->whatsapp_api_key, 0, 15) . '...\n';
    echo "¿Desea actualizarla? (s/n): ";
    $response = trim(fgets(STDIN));
    if (strtolower($response) === 's') {
        $empresaCorrecta->whatsapp_api_key = $apiKey;
        $empresaCorrecta->save();
        echo "✅ API key actualizada\n";
    }
}

// Paso 4: Verificar conexión con la API
echo "\n🌐 Paso 4: Verificando conexión con WhatsApp API...\n";
$apiUrl = config('whatsapp.api_url', 'http://82.165.213.124:8092');

echo "URL de API: {$apiUrl}\n";
echo "Company ID: {$empresaCorrecta->id}\n";
echo "API Key: " . substr($apiKey, 0, 15) . '...\n';

try {
    $response = Http::withHeaders([
        'X-API-Key' => $apiKey,
        'X-Company-Id' => (string) $empresaCorrecta->id,
        'Content-Type' => 'application/json',
    ])->timeout(10)->get($apiUrl . '/api/whatsapp/status');

    if ($response->successful()) {
        echo "✅ Conexión exitosa!\n";
        $data = $response->json();
        if (isset($data['connection'])) {
            echo "Estado de conexión: {$data['connection']}\n";
        }
        if (isset($data['phone'])) {
            echo "Teléfono: {$data['phone']}\n";
        }
    } else {
        echo "❌ Error en la conexión: HTTP {$response->status()}\n";
        if ($response->status() === 401) {
            echo "🔑 La API key es inválida o la empresa no está registrada en la API\n";
        }
        $errorBody = $response->body();
        if ($errorBody) {
            echo "Respuesta de error: {$errorBody}\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error al conectar con la API: {$e->getMessage()}\n";
}

// Paso 5: Actualizar configuración de usuario si es necesario
echo "\n👤 Paso 5: Verificando usuario autenticado...\n";

// Verificar si hay usuarios con empresa_id = 1 que deberían usar empresa_id = 2
$usuariosEmpresa1 = \App\Models\User::where('empresa_id', 1)->get();
if ($usuariosEmpresa1->isNotEmpty() && $empresaCorrecta->id == 2) {
    echo "⚠️  Se encontraron usuarios con empresa_id = 1:\n";
    foreach ($usuariosEmpresa1 as $usuario) {
        echo "  - Usuario: {$usuario->name} (ID: {$usuario->id})\n";
    }
    echo "\n¿Desea actualizar estos usuarios a empresa_id = 2? (s/n): ";
    $response = trim(fgets(STDIN));
    if (strtolower($response) === 's') {
        foreach ($usuariosEmpresa1 as $usuario) {
            $usuario->empresa_id = 2;
            $usuario->save();
            echo "✅ Usuario {$usuario->name} actualizado a empresa ID 2\n";
        }
    }
}

// Paso 6: Resumen final
echo "\n📊 Resumen de configuración:\n";
echo "===========================\n";
echo "Empresa configurada: {$empresaCorrecta->razon_social} (ID: {$empresaCorrecta->id})\n";
echo "API Key: " . substr($empresaCorrecta->whatsapp_api_key, 0, 15) . '...\n';
echo "WhatsApp Activo: " . ($empresaCorrecta->whatsapp_active ? 'Sí' : 'No') . "\n";
echo "Estado: {$empresaCorrecta->whatsapp_status}\n";

echo "\n✅ Script completado!\n";
echo "\nPróximos pasos:\n";
echo "1. Verificar que la API WhatsApp tenga registrada la empresa ID {$empresaCorrecta->id}\n";
echo "2. Si el problema persiste, ejecutar: php artisan whatsapp:configure-company {$empresaCorrecta->id} --register\n";
echo "3. Para verificar el estado: php artisan whatsapp:check-company\n";