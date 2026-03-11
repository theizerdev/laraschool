<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PagoDetalle;
use App\Models\PaymentSchedule;
use App\Models\Matricula;

echo "=== Corrigiendo TODOS los Pagos Sin Vincular ===" . PHP_EOL . PHP_EOL;

// Obtener todos los detalles de pago SIN payment_schedule_id pero CON matrícula
$detallesSinVincular = PagoDetalle::whereNull('payment_schedule_id')
    ->whereHas('pago', function($q) {
        $q->whereNotNull('matricula_id')
          ->where('estado', 'aprobado');
    })
    ->with(['pago.matricula.paymentSchedules'])
    ->get();

echo "Total detalles sin vincular encontrados: {$detallesSinVincular->count()}" . PHP_EOL . PHP_EOL;

if($detallesSinVincular->isEmpty()) {
    echo "✅ ¡No hay pagos sin vincular! Todos los pagos están correctamente asignados." . PHP_EOL;
    exit(0);
}

$agrupadosPorMatricula = $detallesSinVincular->groupBy('pago.matricula_id');

$totalActualizados = 0;
$totalNoEncontrados = 0;
$matriculasProcesadas = 0;

foreach ($agrupadosPorMatricula as $matriculaId => $detallesDeMatricula) {
    $matricula = Matricula::find($matriculaId);
    
  if (!$matricula) {
        echo "❌ Matrícula #{$matriculaId} no encontrada, saltando..." . PHP_EOL;
        continue;
    }
    
    $matriculasProcesadas++;
    
    echo PHP_EOL . "--- Procesando Matrícula #{$matriculaId} ---" . PHP_EOL;
    echo "Estudiante: {$matricula->student->nombres} {$matricula->student->apellidos}" . PHP_EOL;
    echo "Detalles a procesar: {$detallesDeMatricula->count()}" . PHP_EOL;
    
    // Obtener todos los payment schedules de esta matrícula
    $schedules = PaymentSchedule::where('matricula_id', $matriculaId)
        ->orderBy('numero_cuota')
        ->get()
        ->keyBy('numero_cuota');
    
    $actualizadosEnMatricula = 0;
    $noEncontradosEnMatricula = 0;
    
    foreach ($detallesDeMatricula as $detalle) {
        $descripcion = $detalle->descripcion ?? '';
        $monto = $detalle->subtotal ?? 0;
        
        // Intentar extraer número de cuota de la descripción
     if(preg_match('/Cuota\s+#?(\d+)/i', $descripcion, $matches)) {
            $numeroCuota = (int)$matches[1];
            
          if(isset($schedules[$numeroCuota])) {
                $schedule= $schedules[$numeroCuota];
                
                // Verificar si el monto coincide (con tolerancia de 0.01)
             if(abs($monto - $schedule->monto) < 0.01 || 
                   abs($monto- $schedule->saldo_pendiente) < 0.01) {
                        
                        // Asignar payment_schedule_id
                       $detalle['payment_schedule_id'] = $schedule->id;
                        $detalle->save();
                        
                        echo "  ✅ Detalle #{$detalle->id}: Vinculado a Cuota #{$numeroCuota}" . PHP_EOL;
                        $actualizadosEnMatricula++;
                        $totalActualizados++;
                    } else {
                        echo "  ⚠️  Detalle #{$detalle->id}: Monto no coincide ($" . number_format($monto, 2) . " vs $" . number_format($schedule->monto, 2) . ")" . PHP_EOL;
                        $noEncontradosEnMatricula++;
                        $totalNoEncontrados++;
                    }
            } else {
                echo "  ❌ Detalle #{$detalle->id}: No existe Cuota #{$numeroCuota}" . PHP_EOL;
                $noEncontradosEnMatricula++;
                $totalNoEncontrados++;
            }
        } else {
            echo "  ℹ️  Detalle #{$detalle->id}: Descripción '{$descripcion}' no es cuota" . PHP_EOL;
            // No contar como error - puede ser servicio administrativo u otros conceptos
        }
    }
    
    // Si se actualizó al menos uno, recalcular montos pagados y solvencia
  if($actualizadosEnMatricula > 0) {
        echo PHP_EOL . "  📊 Actualizando montos pagados y solvencia..." . PHP_EOL;
        
        // Recalcular todos los payment schedules
        foreach ($schedules as $schedule) {
            $totalPagado = $schedule->syncPaidAmountFromPayments();
        }
        
        // Actualizar solvencia de la matrícula
       $esSolvente = $matricula->updateSolvencia();
        echo "  💰 Estado de solvencia: " . ($esSolvente ? '✅ SOLVENTE' : '⚠️ CON DEUDAS') . PHP_EOL;
    }
    
    echo PHP_EOL . "  Resumen Matrícula #{$matriculaId}: {$actualizadosEnMatricula} actualizados, {$noEncontradosEnMatricula} no encontrados" . PHP_EOL;
}

echo PHP_EOL . "=========================================" . PHP_EOL;
echo "=== RESUMEN GLOBAL ===" . PHP_EOL;
echo "=========================================" . PHP_EOL;
echo "Matrículas procesadas: {$matriculasProcesadas}" . PHP_EOL;
echo "Total detalles actualizados: {$totalActualizados}" . PHP_EOL;
echo "Total no encontrados: {$totalNoEncontrados}" . PHP_EOL;
echo "=========================================" . PHP_EOL;

if($totalActualizados > 0) {
    echo PHP_EOL . "✅ ¡Se corrigieron {$totalActualizados} detalles de pago!" . PHP_EOL;
    echo "⚠️  Recuerda limpiar la caché: php artisan cache:clear" . PHP_EOL;
} else {
    echo PHP_EOL . "ℹ️  No se requirieron correcciones masivas." . PHP_EOL;
}

echo PHP_EOL . "¡Proceso completado!" . PHP_EOL;