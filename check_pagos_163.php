<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PagoDetalle;
use App\Models\PaymentSchedule;

echo "=== Verificando Pagos de Matrícula #163 ===" . PHP_EOL . PHP_EOL;

$detalles = PagoDetalle::whereHas('pago', function($q) {
        $q->where('matricula_id', 163);
    })->with('pago')->get();

echo "Detalles encontrados: " . $detalles->count() . PHP_EOL . PHP_EOL;

foreach ($detalles as $detalle) {
    echo "ID Detalle: {$detalle->id}" . PHP_EOL;
    echo "  -Pago ID: {$detalle->pago_id}" . PHP_EOL;
    echo "  -Payment Schedule ID: " . ($detalle->payment_schedule_id ?? 'NULL') . PHP_EOL;
    echo "  - Concepto: {$detalle->descripcion}" . PHP_EOL;
    echo "  - Subtotal: $" . number_format($detalle->subtotal, 2) . PHP_EOL;
    echo "  -Pago Fecha: {$detalle->pago->fecha_pago}" . PHP_EOL;
    echo PHP_EOL;
}

echo PHP_EOL . "=== Payment Schedules de Matrícula #163 ===" . PHP_EOL . PHP_EOL;

$schedules = PaymentSchedule::where('matricula_id', 163)->orderBy('numero_cuota')->get();

foreach ($schedules as $schedule) {
    echo "Cuota #" . $schedule->numero_cuota . ":" . PHP_EOL;
    echo "  - Monto: $" . number_format($schedule->monto, 2) . PHP_EOL;
    echo "  - Monto Pagado: $" . number_format($schedule->monto_pagado, 2) . PHP_EOL;
    echo "  - Saldo: $" . number_format($schedule->saldo_pendiente, 2) . PHP_EOL;
    echo "  - Estado: {$schedule->estado}" . PHP_EOL;
    echo "  - Esta Pagado: " . ($schedule->esta_pagado ? 'SI' : 'NO') . PHP_EOL;
    echo PHP_EOL;
}