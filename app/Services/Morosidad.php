public function generarMensajeMorosidad($estudiante, $empresa)
{
    $cuotas = $estudiante->matricula->cuotas()->where('estado', '!=', 'Pagado')->get();
    
    if ($cuotas->isEmpty()) {
        return null;
    }

    $mensaje = "🔔 **NOTIFICACIÓN DE MOROSIDAD**\n";
    $mensaje .= "Institución: {$empresa->nombre}\n";
    $mensaje .= "Estudiante: {$estudiante->nombre_completo}\n";
    $mensaje .= "Documento: {$estudiante->documento}\n\n";

    $totalDeuda = 0;
    $detalleCuotas = [];

    foreach ($cuotas as $cuota) {
        $montoPendiente = $cuota->monto - $cuota->pagado;
        if ($montoPendiente > 0) {
            $totalDeuda += $montoPendiente;
            $detalleCuotas[] = "- Cuota {$cuota->numero}: Bs. {$montoPendiente} ({$cuota->estado})";
        }
    }

    if (!empty($detalleCuotas)) {
        $mensaje .= "Deuda pendiente: **Bs. {$totalDeuda}**\n";
        $mensaje .= "Detalles:\n";
        $mensaje .= implode("\n", $detalleCuotas);
    }

    $mensaje .= "\n\nPara realizar el pago, puede usar cualquiera de los siguientes métodos:\n";
    $mensaje .= "- Efectivo\n";
    $mensaje .= "- Transferencia bancaria\n";
    $mensaje .= "- Tarjeta de crédito/débito\n";
    $mensaje .= "- Pago móvil\n\n";
    $mensaje .= "Gracias por su atención.\n";
    $mensaje .= "Atentamente,\n";
    $mensaje .= "{$empresa->nombre}";

    return $mensaje;
}