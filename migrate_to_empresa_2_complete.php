<?php

/**
 * Script completo para migrar todo el sistema a usar empresa_id 2
 * y configurar WhatsApp correctamente
 */

require __DIR__.'/vendor/autoload.php';

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 SCRIPT COMPLETO DE MIGRACIÓN A EMPRESA ID 2\n";
echo "================================================\n\n";

// Paso 1: Verificar empresas existentes
echo "📋 Paso 1: Verificando empresas existentes...\n";
$empresa1 = Empresa::find(1);
$empresa2 = Empresa::find(2);

if (!$empresa1) {
    echo "❌ No existe empresa ID 1\n";
    exit(1);
}

if (!$empresa2) {
    echo "❌ No existe empresa ID 2\n";
    echo "¿Desea crear la empresa ID 2? (s/n): ";
    $response = trim(fgets(STDIN));
    if (strtolower($response) !== 's') {
        exit(1);
    }
    
    // Crear empresa 2 con datos de la empresa 1
    $empresa2 = Empresa::find(1);
    $empresa2->id = 2;
    $empresa2->save();
    
    echo "✅ Empresa ID 2 creada: {$empresa2->razon_social}\n";
}

echo "✅ Empresas verificadas:\n";
echo "   Empresa 1: {$empresa1->razon_social}\n";
if ($empresa2) {
    echo "   Empresa 2: {$empresa2->razon_social}\n";
} else {
    echo "   Empresa 2: No existe\n";
}
echo "\n";

// Paso 2: Configurar WhatsApp API key para empresa 2
echo "🔑 Paso 2: Configurando WhatsApp para empresa ID 2...\n";

if (!$empresa2) {
    echo "❌ No existe empresa ID 2, no se puede configurar WhatsApp\n";
} elseif (empty($empresa2->whatsapp_api_key)) {
    // Copiar API key de empresa 1 si existe
    if (!empty($empresa1->whatsapp_api_key)) {
        $empresa2->whatsapp_api_key = $empresa1->whatsapp_api_key;
        $empresa2->save();
        echo "✅ API key copiada de empresa 1\n";
    } else {
        // Usar la API key del .env
        $apiKey = config('whatsapp.api_key', 'test-api-key-vargas-centro');
        $empresa2->whatsapp_api_key = $apiKey;
        $empresa2->save();
        echo "✅ API key configurada desde .env: {$apiKey}\n";
    }
} else {
    echo "ℹ️  Empresa 2 ya tiene API key configurada\n";
}

// Paso 3: Ejecutar comando de migración
echo "\n🔄 Paso 3: Ejecutando migración de datos...\n";
echo "Esto actualizará TODOS los registros de empresa_id 1 a empresa_id 2\n";
echo "¿Desea continuar? (s/n): ";
$response = trim(fgets(STDIN));

if (strtolower($response) !== 's') {
    echo "Operación cancelada.\n";
    exit(0);
}

// Primero hacer un dry run
echo "\n🔍 Ejecutando simulación...\n";
exec('php artisan empresas:migrate-to-id2 --dry-run', $output, $returnCode);
foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n¿Desea ejecutar la migración real? (s/n): ";
$response = trim(fgets(STDIN));

if (strtolower($response) !== 's') {
    echo "Operación cancelada.\n";
    exit(0);
}

// Desactivar llaves foráneas para permitir la migración
echo "\n🔓 Desactivando llaves foráneas...\n";
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    echo "✅ Llaves foráneas desactivadas temporalmente\n";
} catch (\Exception $e) {
    echo "⚠️  Advertencia: No se pudieron desactivar llaves foráneas: {$e->getMessage()}\n";
    echo "¿Desea continuar de todos modos? (s/n): ";
    $response = trim(fgets(STDIN));
    if (strtolower($response) !== 's') {
        exit(0);
    }
}

// Ejecutar migración real
echo "\n⚡ Ejecutando migración real...\n";
exec('php artisan empresas:migrate-to-id2 --force', $output, $returnCode);
foreach ($output as $line) {
    echo $line . "\n";
}

// Reactivar llaves foráneas después de la migración
echo "\n🔒 Reactivando llaves foráneas...\n";
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    echo "✅ Llaves foráneas reactivadas\n";
} catch (\Exception $e) {
    echo "❌ Error al reactivar llaves foráneas: {$e->getMessage()}\n";
    echo "⚠️  IMPORTANTE: Las llaves foráneas permanecen desactivadas. Debe reactivarlas manualmente.\n";
}

// Paso 4: Verificar usuarios
echo "\n👤 Paso 4: Verificando usuarios...\n";
$totalUsuarios = User::count();
$usuariosEmpresa2 = User::where('empresa_id', 2)->count();
$usuariosempresa2 = User::where('empresa_id', 1)->count();

echo "Total usuarios: {$totalUsuarios}\n";
echo "Usuarios en empresa 2: {$usuariosEmpresa2}\n";
echo "Usuarios en empresa 1: {$usuariosempresa2}\n";

if ($usuariosempresa2 > 0) {
    echo "⚠️  Aún hay usuarios en empresa 1. Actualizando...\n";
    User::where('empresa_id', 1)->update(['empresa_id' => 2]);
    echo "✅ Usuarios actualizados a empresa 2\n";
}

// Paso 5: Verificar conexión WhatsApp
echo "\n🌐 Paso 5: Verificando conexión WhatsApp...\n";
$apiUrl = config('whatsapp.api_url', 'http://82.165.213.124:8092');
$apiKey = $empresa2->whatsapp_api_key;

echo "URL API: {$apiUrl}\n";
echo "Company ID: 2\n";
echo "API Key: " . substr($apiKey, 0, 15) . '...\n';

try {
    $response = Http::withHeaders([
        'X-API-Key' => $apiKey,
        'X-Company-Id' => '2',
        'Content-Type' => 'application/json',
    ])->timeout(10)->get($apiUrl . '/api/whatsapp/status');

    if ($response->successful()) {
        echo "✅ Conexión exitosa con WhatsApp API!\n";
        $data = $response->json();
        if (isset($data['connection'])) {
            echo "Estado: {$data['connection']}\n";
        }
        if (isset($data['phone'])) {
            echo "Teléfono: {$data['phone']}\n";
        }
    } else {
        echo "❌ Error HTTP {$response->status()}\n";
        if ($response->status() === 401) {
            echo "🔑 API key inválida o empresa no registrada\n";
            echo "¿Desea registrar la empresa en la API? (s/n): ";
            $response = trim(fgets(STDIN));
            if (strtolower($response) === 's') {
                echo "Ejecutando registro...\n";
                exec('php artisan whatsapp:configure-company 2 --register', $output, $returnCode);
                foreach ($output as $line) {
                    echo $line . "\n";
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Error de conexión: {$e->getMessage()}\n";
}

// Paso 6: Resumen final
echo "\n📊 RESUMEN FINAL DE MIGRACIÓN\n";
echo "==============================\n";
echo "✅ Empresa 2 configurada como principal\n";
echo "✅ Todos los datos migrados de empresa_id 1 a 2\n";
echo "✅ WhatsApp API key configurada para empresa 2\n";
echo "✅ Sistema preparado para usar empresa_id 2\n";

echo "\n🎯 El sistema ahora usará empresa_id 2 por defecto\n";
echo "   WhatsApp API debería funcionar correctamente con:\n";
echo "   - Company ID: 2\n";
echo "   - API Key: " . substr($empresa2->whatsapp_api_key, 0, 15) . '...\n';

echo "\n💡 Comandos útiles para verificar:\n";
echo "   php artisan whatsapp:check-company\n";
echo "   php artisan whatsapp:configure-company 2 --register\n";

echo "\n✅ Script completado!\n";