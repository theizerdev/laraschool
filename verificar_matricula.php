<?php

/**
 * Script para verificar la información de una matrícula específica
 * 
 * Uso: php verificar_matricula.php [id_matricula]
 * Ejemplo: php verificar_matricula.php 164
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Matricula;

// Obtener el ID de la matrícula del argumento o usar 164 por defecto
$idMatricula = isset($argv[1]) ? (int)$argv[1] : 164;

echo "=== Verificando Matrícula #{$idMatricula} ===" . PHP_EOL . PHP_EOL;

$matricula = Matricula::with(['estudiante', 'programa', 'periodo', 'paymentSchedules'])->find($idMatricula);

if (!$matricula) {
    echo "❌ No se encontró la matrícula #{$idMatricula}" . PHP_EOL;
    exit(1);
}

echo "📋 Información General:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
echo "Matrícula ID: {$matricula->id}" . PHP_EOL;
echo "Estudiante: {$matricula->estudiante->nombres} {$matricula->estudiante->apellidos}" . PHP_EOL;
echo "Documento: {$matricula->estudiante->documento_identidad}" . PHP_EOL;
echo "Programa: {$matricula->programa->nombre}" . PHP_EOL;
echo "Período: {$matricula->periodo->name}" . PHP_EOL;
echo "Estado: {$matricula->estado}" . PHP_EOL;
echo "Solvente: " . ($matricula->solvente ? '✅ SI' : '❌ NO') . PHP_EOL;
echo "Costo: $" . number_format($matricula->costo, 2) . PHP_EOL;
echo "Fecha Matrícula: {$matricula->fecha_matricula->format('d/m/Y')}" . PHP_EOL;

echo PHP_EOL . "💰 Resumen Financiero:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
$resumen = $matricula->resumen_financiero;
echo "Total Cuotas: $" . number_format($resumen['total_cuotas'], 2) . PHP_EOL;
echo "Total Pagado: $" . number_format($resumen['total_pagado'], 2) . PHP_EOL;
echo "Total Pendiente: $" . number_format($resumen['total_pendiente'], 2) . PHP_EOL;
echo "Porcentaje Pagado: {$resumen['porcentaje_pagado']}%" . PHP_EOL;
echo "Cuotas Pagadas: {$resumen['cuotas_pagadas']} de {$resumen['cuotas_totales']}" . PHP_EOL;
echo "Cuotas Vencidas: {$resumen['cuotas_vencidas']}" . PHP_EOL;

echo PHP_EOL . "📅 Cronograma de Pagos:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
if ($matricula->paymentSchedules->isEmpty()) {
    echo "No hay cronograma de pagos registrado" . PHP_EOL;
} else {
    foreach ($matricula->paymentSchedules as $index => $schedule) {
        echo "Cuota " . ($index + 1) . ":" . PHP_EOL;
        echo "  - Monto: $" . number_format($schedule->monto, 2) . PHP_EOL;
        echo "  - Pagado: $" . number_format($schedule->monto_pagado, 2) . PHP_EOL;
        echo "  - Saldo Pendiente: $" . number_format($schedule->saldo_pendiente, 2) . PHP_EOL;
        echo "  - Fecha Vencimiento: {$schedule->fecha_vencimiento->format('d/m/Y')}" . PHP_EOL;
        echo "  - Estado: {$schedule->estado}" . PHP_EOL;
        echo "  - ¿Está Pagada?: " . ($schedule->esta_pagado ? '✅ SI' : '❌ NO') . PHP_EOL;
        echo PHP_EOL;
    }
}

echo PHP_EOL . "🔍 Análisis de Solvencia:" . PHP_EOL;
echo "━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
$esSolvente = $matricula->calcularEsSolvente();
echo "¿Debería estar solvente?: " . ($esSolvente ? '✅ SI' : '❌ NO') . PHP_EOL;
echo "¿Estado actual correcto?: " . ($matricula->solvente === $esSolvente ? '✅ SI' : '❌ NO - NECESITA ACTUALIZACIÓN') . PHP_EOL;

if ($matricula->solvente !== $esSolvente) {
    echo PHP_EOL . "⚠️  ATENCIÓN: La matrícula necesita actualización de solvencia" . PHP_EOL;
    echo "Para actualizar, ejecuta: php recalcular_solvente.php" . PHP_EOL;
}

echo PHP_EOL;