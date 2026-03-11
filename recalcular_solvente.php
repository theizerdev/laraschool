<?php

/**
 * Script para recalculary actualizar el estado de solvencia de todas las matrículas
 * 
 * Uso: php recalcular_solvente.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Matricula;

echo "=== Recalculando Solvencia de Matrículas ===" . PHP_EOL . PHP_EOL;

$matriculas = Matricula::with('paymentSchedules')->get();

$actualizados = 0;
$solventes = 0;
$con_deudas = 0;

foreach ($matriculas as $matricula) {
    $solventeAnterior = $matricula->solvente;
    $nuevoEstado = $matricula->updateSolvencia();
    
   if($solventeAnterior !== $nuevoEstado) {
        $actualizados++;
        echo "Matrícula #{$matricula->id}: ";
        echo "Cambiado de " . ($solventeAnterior ? 'SOLVENTE' : 'CON DEUDAS');
        echo " a " . ($nuevoEstado ? 'SOLVENTE ✓' : 'CON DEUDAS ✗') . PHP_EOL;
    }
    
   if($nuevoEstado) {
        $solventes++;
    } else {
        $con_deudas++;
    }
}

echo PHP_EOL . "=== Resumen ===" . PHP_EOL;
echo "Total matrículas procesadas: " . $matriculas->count() . PHP_EOL;
echo "Actualizados: {$actualizados}" . PHP_EOL;
echo "Solventes: {$solventes}" . PHP_EOL;
echo "Con deudas: {$con_deudas}" . PHP_EOL;
echo PHP_EOL;