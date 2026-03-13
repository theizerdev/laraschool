<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Matricula;
use App\Models\PaymentSchedule;

echo "=== Verificación y Corrección de Solvencia en Matrículas ===\n\n";

// Obtener todas las matrículas
$matriculas = Matricula::with('paymentSchedules')->get();

$totalMatriculas = $matriculas->count();
$matriculasConError = 0;
$matriculasCorregidas = 0;

echo "Total de matrículas verificadas: {$totalMatriculas}\n\n";

foreach ($matriculas as $matricula) {
    $tieneCronograma = $matricula->paymentSchedules->count() > 0;
    
    // Calcular solvencia real basada en las cuotas
    $solventeReal = true;
    
    if ($tieneCronograma) {
        foreach ($matricula->paymentSchedules as $schedule) {
            $saldoPendiente = $schedule->monto - $schedule->monto_pagado;
            
            // Si hay saldo pendiente, no es solvente
            if ($saldoPendiente > 0.01) {
                $solventeReal = false;
                break;
            }
        }
    }
    
    // Verificar si hay discrepancia
    $solventeActual = $matricula->solvente;
    
    if ($solventeActual !== $solventeReal) {
        $matriculasConError++;
        
        echo "❌ Matrícula #{$matricula->id} - Estudiante: {$matricula->estudiante->nombres} {$matricula->estudiante->apellidos}\n";
        echo "   Estado actual: " . ($solventeActual ? 'SOLVENTE' : 'NO SOLVENTE') . "\n";
        echo "   Estado real: " . ($solventeReal ? 'SOLVENTE' : 'NO SOLVENTE') . "\n";
        echo "   Cuotas: {$matricula->paymentSchedules->count()} | Pagadas: " . 
             $matricula->paymentSchedules->filter(fn($s) => $s->esta_pagado)->count() . "\n";
        
        // Corregir la solvencia
        $matricula->solvente = $solventeReal;
        $matricula->save();
        
        $matriculasCorregidas++;
        echo "   ✅ Corregido a: " . ($solventeReal ? 'SOLVENTE' : 'NO SOLVENTE') . "\n\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Matrículas con error de solvencia: {$matriculasConError}\n";
echo "Matrículas corregidas: {$matriculasCorregidas}\n";

if ($matriculasConError === 0) {
    echo "\n✅ ¡Todas las matrículas tienen el estado de solvencia correcto!\n";
} else {
    echo "\n✅ Se han corregido {$matriculasCorregidas} matrículas con solvencia incorrecta.\n";
}
