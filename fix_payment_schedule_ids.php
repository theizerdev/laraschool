<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PagoDetalle;
use App\Models\PaymentSchedule;
use App\Models\Matricula;

echo "=== Corrigiendo payment_schedule_id para Matrícula #163 ===" . PHP_EOL . PHP_EOL;

$matricula = Matricula::find(163);
if (!$matricula) {
    echo "❌ Matrícula #163 no encontrada" . PHP_EOL;
    exit;
}

echo "Estudiante: {$matricula->student->nombres} {$matricula->student->apellidos}" . PHP_EOL;
echo "Programa: {$matricula->programa->nombre}" . PHP_EOL . PHP_EOL;

// Obtener todos los payment schedules de esta matrícula
$schedules = PaymentSchedule::where('matricula_id', 163)
    ->orderBy('numero_cuota')
    ->get();

echo "Payment Schedules encontrados: {$schedules->count()}" . PHP_EOL . PHP_EOL;

// Obtener todos los detalles de pago SIN payment_schedule_id
$detallesSinVincular = PagoDetalle::whereHas('pago', function($q) use ($matricula) {
        $q->where('matricula_id', $matricula->id);
    })
    ->whereNull('payment_schedule_id')
    ->with('pago')
    ->get();

echo "Detalles sin vincular: {$detallesSinVincular->count()}" . PHP_EOL . PHP_EOL;

$actualizados = 0;
$noEncontrados = 0;

foreach ($detallesSinVincular as $detalle) {
    echo "Procesando detalle ID {$detalle->id}: {$detalle->descripcion}" . PHP_EOL;
    
    // Intentar encontrar el payment schedule basado en la descripción
    // Patrón: "Cuota #X - Mes Año" o similar
   if(preg_match('/Cuota\s+#?(\d+)/i', $detalle->descripcion, $matches)) {
        $numeroCuota = (int)$matches[1];
        
        $schedule= $schedules->firstWhere('numero_cuota', $numeroCuota);
        
       if($schedule) {
            // Verificar si el monto coincide (con tolerancia de 0.01)
           if (abs($detalle->subtotal - $schedule->monto) < 0.01 || 
                abs($detalle->subtotal - $schedule->saldo_pendiente) < 0.01) {
                
                $detalle->payment_schedule_id = $schedule->id;
                $detalle->save();
                
                echo "  ✅ Vinculado a Cuota #{$numeroCuota} (ID: {$schedule->id})" . PHP_EOL;
                $actualizados++;
            } else {
                echo "  ⚠️  Monto no coincide: Detalle=${$detalle->subtotal}, Cuota=${$schedule->monto}" . PHP_EOL;
                $noEncontrados++;
            }
        } else {
            echo "  ❌ No se encontró Cuota #{$numeroCuota}" . PHP_EOL;
            $noEncontrados++;
        }
    } else {
        echo "  ℹ️  Descripción no coincide con patrón de cuota" . PHP_EOL;
        $noEncontrados++;
    }
    
    echo PHP_EOL;
}

echo PHP_EOL . "=== Resumen ===" . PHP_EOL;
echo "Detalles procesados: {$detallesSinVincular->count()}" . PHP_EOL;
echo "Actualizados: {$actualizados}" . PHP_EOL;
echo "No encontrados: {$noEncontrados}" . PHP_EOL;

if($actualizados > 0) {
    echo PHP_EOL . "=== Actualizando montos pagados ===" . PHP_EOL;
    
    // Recalcular todos los payment schedules
    foreach ($schedules as $schedule) {
        $totalPagado = $schedule->syncPaidAmountFromPayments();
        echo "Cuota #{$schedule->numero_cuota}: Monto Pagado actualizado a $" . number_format($totalPagado, 2) . PHP_EOL;
    }
    
    // Actualizar solvencia de la matrícula
    echo PHP_EOL . "=== Actualizando solvencia ===" . PHP_EOL;
    $esSolvente= $matricula->updateSolvencia();
    echo "Estado de solvencia: " . ($esSolvente ? '✅ SOLVENTE' : '⚠️ CON DEUDAS') . PHP_EOL;
}

echo PHP_EOL . "¡Proceso completado!" . PHP_EOL;