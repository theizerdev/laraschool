<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Morosidad</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .info { margin-bottom: 15px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 20px; }
        .totals table { width: 50%; margin-left: auto; }
    </style>
</head>
<body>
    <div class="header">
        <h2>REPORTE DE MOROSIDAD</h2>
        <p>U.E VARGAS II</p>
    </div>

    <div class="info">
        <div class="info-row">
            <span><strong>Fecha de generación:</strong> {{ $fecha_generacion }}</span>
            <span><strong>Rango de fechas:</strong> {{ $rango_fechas }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Documento</th>
                <th>Programa</th>
                <th>Nivel</th>
                <th class="text-right">Costo Total</th>
                <th class="text-right">Total Pagado</th>
                <th class="text-right">Saldo Pendiente</th>
                <th class="text-right">% Pagado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($morosos as $moroso)
                <tr>
                    <td>{{ $moroso['matricula']->student->nombres ?? '' }} {{ $moroso['matricula']->student->apellidos ?? '' }}</td>
                    <td>{{ $moroso['matricula']->student->documento_identidad ?? 'N/A' }}</td>
                    <td>{{ $moroso['matricula']->programa->nombre ?? 'N/A' }}</td>
                    <td>{{ $moroso['matricula']->programa->nivelEducativo->nombre ?? 'N/A' }}</td>
                    <td class="text-right">${{ number_format($moroso['matricula']->costo ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format($moroso['total_pagado'], 2) }}</td>
                    <td class="text-right">${{ number_format($moroso['saldo_pendiente'], 2) }}</td>
                    <td class="text-right">{{ number_format($moroso['porcentaje_pagado'], 2) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td><strong>Total Estudiantes:</strong></td>
                <td class="text-right">{{ $totales['total_estudiantes'] }}</td>
            </tr>
            <tr>
                <td><strong>Total Morosos:</strong></td>
                <td class="text-right">{{ $totales['total_morosos'] }}</td>
            </tr>
            <tr>
                <td><strong>Porcentaje Morosidad:</strong></td>
                <td class="text-right">{{ number_format($totales['porcentaje_morosidad'], 2) }}%</td>
            </tr>
        </table>
    </div>
</body>
</html>