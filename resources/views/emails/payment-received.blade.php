<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirmación de Pago</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .payment-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .amount { font-size: 18px; font-weight: bold; color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✅ Pago Recibido</h2>
            <p>U.E JOSE MARIA VARGAS</p>
        </div>
        
        <div class="content">
            <p>Estimado/a <strong>{{ $estudiante->nombres }} {{ $estudiante->apellidos }}</strong>,</p>
            
            <p>Hemos recibido su pago exitosamente. A continuación los detalles:</p>
            
            <div class="payment-details">
                <h3>Detalles del Pago</h3>
                <p><strong>Número de Recibo:</strong> {{ $pago->numero_completo }}</p>
                <p><strong>Fecha:</strong> {{ $pago->fecha->format('d/m/Y') }}</p>
                <p><strong>Método de Pago:</strong> {{ ucfirst(str_replace('_', ' ', $pago->metodo_pago)) }}</p>
                @if($pago->referencia)
                    <p><strong>Referencia:</strong> {{ $pago->referencia }}</p>
                @endif
                
                <h4>Conceptos Pagados:</h4>
                @foreach($pago->detalles as $detalle)
                    <p>• {{ $detalle->descripcion }}: <span class="amount">${{ number_format($detalle->precio_unitario * $detalle->cantidad, 2) }}</span></p>
                @endforeach
                
                <hr>
                <p><strong>Total Pagado:</strong> <span class="amount">${{ number_format($pago->total, 2) }}</span></p>
            </div>
            
            <p>Gracias por su pago puntual. Este comprobante sirve como confirmación de su transacción.</p>
        </div>
        
        <div class="footer">
            <p>U.E JOSE MARIA VARGAS<br>
            Este es un mensaje automático, por favor no responder a este correo.</p>
        </div>
    </div>
</body>
</html>