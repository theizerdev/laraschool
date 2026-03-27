<?php

require_once 'vendor/autoload.php';

use App\Models\Matricula;
use App\Models\Pago;
use Carbon\Carbon;

// Obtener todas las matrículas activas
$matriculas = Matricula::with(['student', 'cronogramaPagos', 'pagos'])
    ->where('estado', 'activo')
    ->get();

echo "=== VERIFICACIÓN DE MOROSIDAD ===\n";
echo "Total de matrículas activas: " . $matriculas->count() . "\n\n";

$fechaCorte = Carbon::now();
echo "Fecha de corte para morosidad: " . $fechaCorte->format('d/m/Y') . "\n\n";

$morosos = [];
$noMorosos = [];

foreach ($matriculas as $matricula) {
    echo "Matrícula ID: " . $matricula->id . "\n";
    echo "Estudiante: " . ($matricula->student->nombres ?? 'N/A') . " " . ($matricula->student->apellidos ?? 'N/A') . "\n";
    
    // Obtener cuotas vencidas
    $cuotasVencidas = $matricula->cronogramaPagos
        ->where('fecha_vencimiento', '<=', $fechaCorte)
        ->where('estado', 'pendiente');
    
    echo "Cuotas totales: " . $matricula->cronogramaPagos->count() . "\n";
    echo "Cuotas vencidas (fecha <= " . $fechaCorte->format('d/m/Y') . "): " . $cuotasVencidas->count() . "\n";
    
    $montoVencido = $cuotasVencidas->sum('monto');
    echo "Monto vencido: $" . number_format($montoVencido, 2) . "\n";
    
    // Obtener pagos aprobados
    $totalPagado = Pago::where('matricula_id', $matricula->id)
        ->where('estado', 'aprobado')
        ->sum('total');
    
    echo "Total pagado: $" . number_format($totalPagado, 2) . "\n";
    echo "Costo total de matrícula: $" . number_format($matricula->costo ?? 0, 2) . "\n";
    
    $saldoPendiente = ($matricula->costo ?? 0) - $totalPagado;
    echo "Saldo pendiente total: $" . number_format($saldoPendiente, 2) . "\n";
    
    // Verificar si es moroso
    $esMoroso = $montoVencido > 0;
    echo "¿Es moroso? " . ($esMoroso ? 'SÍ' : 'NO') . "\n";
    
    if ($esMoroso) {
        $morosos[] = $matricula;
        echo "✅ Agregado a morosos\n";
    } else {
        $noMorosos[] = $matricula;
        echo "❌ No es moroso\n";
    }
    
    echo "---\n\n";
}

echo "=== RESUMEN ===\n";
echo "Total de morosos: " . count($morosos) . "\n";
echo "Total de no morosos: " . count($noMorosos) . "\n";
echo "Porcentaje de morosidad: " . ($matriculas->count() > 0 ? (count($morosos) / $matriculas->count() * 100) : 0) . "%\n";

// Mostrar detalles de algunos morosos
if (count($morosos) > 0) {
    echo "\n=== EJEMPLOS DE MOROSOS ===\n";
    $ejemplos = array_slice($morosos, 0, 3);
    foreach ($ejemplos as $matricula) {
        echo "Matrícula ID: " . $matricula->id . "\n";
        echo "Estudiante: " . ($matricula->student->nombres ?? 'N/A') . " " . ($matricula->student->apellidos ?? 'N/A') . "\n";
        
        $cuotasVencidas = $matricula->cronogramaPagos
            ->where('fecha_vencimiento', '<=', Carbon::now())
            ->where('estado', 'pendiente');
        
        echo "Cuotas vencidas: " . $cuotasVencidas->count() . "\n";
        echo "Monto vencido: $" . number_format($cuotasVencidas->sum('monto'), 2) . "\n";
        echo "---\n";
    }
}