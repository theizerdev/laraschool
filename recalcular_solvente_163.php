<?php

// Cargar Bootstrap de Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Matricula;

$matriculaId = 163;

echo "Recalculando solvencia de la matrícula #{$matriculaId}...\n\n";

$matricula = Matricula::with('paymentSchedules')->find($matriculaId);

if (!$matricula) {
    echo "Matrícula no encontrada\n";
    exit(1);
}

echo "Matrícula: #{$matricula->id}\n";
echo "Estudiante: {$matricula->estudiante->nombre_completo}\n";
echo "Periodo: {$matricula->periodo->nombre}\n";
echo "Estado actual de solvencia: " . ($matricula->solvente ? 'SOLVENTE' : 'NO SOLVENTE') . "\n\n";

// Verificar cuotas pendientes
$cuotasPendientes = $matricula->paymentSchedules->where('estado', 'pendiente');
echo "Cuotas pendientes: {$cuotasPendientes->count()}\n";
echo "Total pendiente: $ {$cuotasPendientes->sum('saldo_pendiente')}\n\n";

// Recalcular solvencia
$esSolvente = $matricula->calcularEsSolvente();
echo "Nuevo estado de solvencia: " . ($esSolvente ? 'SOLVENTE' : 'NO SOLVENTE') . "\n";

// Guardar el cambio
$matricula->solvente = $esSolvente;
$matricula->save();

echo "\n✅ Solvencia actualizada correctamente\n";