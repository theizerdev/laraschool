<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detalle de Deuda - {{ $estudiante->nombres }} {{ $estudiante->apellidos }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #dc3545;
            padding-bottom: 15px;
        }
        .header h2 {
            color: #dc3545;
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            color: #666;
        }
        .student-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 10px;
        }
        .card {
            flex: 1;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-total {
            background-color: #e8f5e9;
            border: 2px solid #28a745;
        }
        .card-paid {
            background-color: #e3f2fd;
            border: 2px solid #17a2b8;
        }
        .card-pending {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
        }
        .card-title {
            font-size: 10px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .card-amount {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .card-bs {
            font-size: 9px;
            color: #888;
        }
        .card-total .card-amount { color: #28a745; }
        .card-paid .card-amount { color: #17a2b8; }
        .card-pending .card-amount { color: #ffc107; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            background-color: #dc3545;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #28a745; color: white; }
        .badge-warning { background-color: #ffc107; color: #333; }
        .badge-danger { background-color: #dc3545; color: white; }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #dc3545;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .contact-info {
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>📋 DETALLE DE DEUDA</h2>
        <p>U.E VARGAS II - Notificación de Pagos Pendientes</p>
    </div>

    <div class="student-info">
        <div class="info-row">
            <span><span class="info-label">Estudiante:</span> {{ $estudiante->nombres }} {{ $estudiante->apellidos }}</span>
            <span><span class="info-label">Documento:</span> {{ $estudiante->documento_identidad ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span><span class="info-label">Programa:</span> {{ $matricula->programa->nombre ?? 'N/A' }}</span>
            <span><span class="info-label">Nivel:</span> {{ $matricula->programa->nivelEducativo->nombre ?? 'N/A' }}</span>
        </div>
        @if(!$esMayorDeEdad && $estudiante->representante_nombres)
        <div class="info-row">
            <span><span class="info-label">Representante:</span> {{ $estudiante->representante_nombres }} {{ $estudiante->representante_apellidos }}</span>
        </div>
        @endif
    </div>

    <div class="summary-cards">
        <div class="card card-total">
            <div class="card-title">Costo Total (Rango)</div>
            <div class="card-amount">${{ number_format($costoTotal, 2, ',', '.') }}</div>
            <div class="card-bs">Bs. {{ number_format($costoTotal * ($tasaCambio ?? 565), 2, ',', '.') }}</div>
        </div>
        <div class="card card-paid">
            <div class="card-title">Total Pagado (Rango)</div>
            <div class="card-amount">${{ number_format($totalPagado, 2, ',', '.') }}</div>
            <div class="card-bs">Bs. {{ number_format($totalPagado * ($tasaCambio ?? 565), 2, ',', '.') }}</div>
        </div>
        <div class="card card-pending">
            <div class="card-title">Saldo Pendiente (Rango)</div>
            <div class="card-amount">${{ number_format($saldoPendiente, 2, ',', '.') }}</div>
            <div class="card-bs">Bs. {{ number_format($saldoPendiente * ($tasaCambio ?? 565), 2, ',', '.') }}</div>
        </div>
    </div>

    <h3 style="color: #dc3545; margin-bottom: 15px;">📅 Detalle de Cuotas Pendientes</h3>

    <table>
        <thead>
            <tr>
                <th>Fecha Vencimiento</th>
                <th>Concepto</th>
                <th class="text-right">Monto Total</th>
                <th class="text-right">Abonado</th>
                <th class="text-right">Restante</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuotas as $cuota)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}</td>
                    <td>{{ $cuota->descripcion ?? 'Cuota ' . ($cuota->numero_cuota ?? 'N/A') }}</td>
                    <td class="text-right">${{ number_format($cuota->monto, 2, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($cuota->monto_pagado ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold; color: #dc3545;">
                        ${{ number_format($cuota->monto - ($cuota->monto_pagado ?? 0), 2, ',', '.') }}
                    </td>
                    <td class="text-center">
                        @if(($cuota->monto_pagado ?? 0) >= $cuota->monto)
                            <span class="badge badge-success">Pagado</span>
                        @elseif(($cuota->monto_pagado ?? 0) > 0)
                            <span class="badge badge-warning">Parcial</span>
                        @else
                            <span class="badge badge-danger">Pendiente</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Importante:</strong> Este documento es una notificación oficial de pagos pendientes.</p>
        <p>Por favor, regularice su situación financiera a la brevedad posible para evitar inconvenientes.</p>
        <div class="contact-info">
            <p>📍 Para realizar su pago, puede acercarse a nuestras oficinas administrativas</p>
            <p>📞 O contáctenos para más información sobre métodos de pago disponibles</p>
        </div>
        <p style="margin-top: 15px;"><em>Generado el {{ now()->format('d/m/Y H:i:s') }}</em></p>
        <p><strong>U.E VARGAS II</strong></p>
    </div>
</body>
</html>
