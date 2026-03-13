<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Matricula;
use App\Models\Student;
use App\Models\Programa;
use App\Models\SchoolPeriod;
use App\Models\PaymentSchedule;

echo "=== Prueba: Creación de Nueva Matrícula con Solvencia Correcta ===\n\n";

// Buscar un estudiante de prueba
$estudiante = Student::where('documento_identidad', 'V-20000000')->first();

if (!$estudiante) {
    echo "❌ No se encontró un estudiante de prueba. Creando uno...\n";
    $estudiante = Student::create([
        'nombres' => 'TEST',
        'apellidos' => 'STUDENT',
        'documento_identidad' => 'V-20000000',
        'fecha_nacimiento' => '2010-01-01',
        'correo_electronico' => 'test@example.com',
        'telefono' => '04121234567'
    ]);
    echo "✅ Estudiante de prueba creado: {$estudiante->nombres} {$estudiante->apellidos}\n";
}

// Obtener programa y período
$programa = Programa::first();
$periodo = SchoolPeriod::where('activo', true)->first();

if (!$programa || !$periodo) {
    echo "❌ No se encontró programa o período activo\n";
    exit(1);
}

echo "✅ Programa: {$programa->nombre}\n";
echo "✅ Período: {$periodo->name}\n\n";

// Crear matrícula de prueba
echo "📝 Creando matrícula de prueba...\n";
$matricula = Matricula::create([
    'empresa_id' => 1,
    'sucursal_id' => 1,
    'estudiante_id' => $estudiante->id,
    'programa_id' => $programa->id,
    'periodo_id' => $periodo->id,
    'fecha_matricula' => now(),
    'estado' => 'activo',
    'costo' => 100.00,
    'cuota_inicial' => 20.00,
    'numero_cuotas' => 4
]);

echo "✅ Matrícula #{$matricula->id} creada\n";
echo "   Costo: \$100.00 | Inicial: \$20.00 | Cuotas: 4\n\n";

// Crear cronograma de pagos manualmente (simulando lo que hace Create.php)
echo "💳 Creando cronograma de pagos...\n";
$montoCuota = (100 - 20) / 4; // $20 por cuota

for ($i = 1; $i <= 4; $i++) {
    PaymentSchedule::create([
        'matricula_id' => $matricula->id,
        'numero_cuota' => $i,
        'monto' => $montoCuota,
        'fecha_vencimiento' => now()->addMonths($i),
        'estado' => 'pendiente',
        'empresa_id' => 1,
        'sucursal_id' => 1,
    ]);
    echo "   Cuota #{$i}: \${$montoCuota} creada\n";
}

echo "\n📊 Verificando estado ANTES de syncPaymentSchedules:\n";
echo "   Solvente: " . ($matricula->solvente ? 'SOLVENTE ❌ (INCORRECTO)' : 'NO SOLVENTE ✅') . "\n";

// Ejecutar syncPaymentSchedules como lo hace el código corregido
echo "\n🔄 Ejecutando syncPaymentSchedules()...\n";
$matricula->syncPaymentSchedules();

// Recargar datos
$matricula->refresh();

echo "\n📊 Verificando estado DESPUÉS de syncPaymentSchedules:\n";
echo "   Solvente: " . ($matricula->solvente ? 'SOLVENTE ❌' : 'NO SOLVENTE ✅ (CORRECTO)') . "\n";

// Mostrar detalle de cuotas
echo "\n💰 Detalle de cuotas:\n";
foreach ($matricula->paymentSchedules as $schedule) {
    $saldoPendiente = $schedule->monto - $schedule->monto_pagado;
    echo "   Cuota #{$schedule->numero_cuota}: \${$schedule->monto} | ";
    echo "Pagado: \${$schedule->monto_pagado} | ";
    echo "Pendiente: \${$saldoPendiente} | ";
    echo "Estado: {$schedule->estado}\n";
}

$resumen = $matricula->resumen_financiero;
echo "\n📋 Resumen financiero:\n";
echo "   Total Cuotas: \$" . number_format($resumen['total_cuotas'], 2) . "\n";
echo "   Total Pagado: \$" . number_format($resumen['total_pagado'], 2) . "\n";
echo "   Saldo Pendiente: \$" . number_format($resumen['total_pendiente'], 2) . "\n";
echo "   Es Solvente: " . ($resumen['es_solvente'] ? 'SÍ ❌' : 'NO ✅') . "\n";

// Limpieza: eliminar matrícula de prueba
echo "\n🗑️  Limpiando matrícula de prueba...\n";
$matricula->delete();
echo "✅ Matrícula eliminada\n\n";

if (!$matricula->solvente) {
    echo "✅ PRUEBA EXITOSA: La matrícula nueva se crea correctamente como NO SOLVENTE\n";
} else {
    echo "❌ PRUEBA FALLIDA: La matrícula nueva sigue apareciendo como SOLVENTE\n";
}
