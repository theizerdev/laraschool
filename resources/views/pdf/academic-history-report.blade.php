<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Académico</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; line-height: 1.4; color: #333; }
        .container { width: 100%; margin: 0 auto; }
        .header, .footer { text-align: center; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 5px 0; font-size: 14px; font-weight: normal; }
        .header p { margin: 0; }
        .content { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .page-footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #777; }
        .logo { max-width: 100px; max-height: 80px; margin-bottom: 10px; }
        .info-table td { border: none; padding: 2px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            {{-- Aquí se podría agregar un logo si existe en el sistema --}}
            <h1>U.E.P. Colegio Vargas</h1>
            <h2>Historial Académico</h2>
            <p>Generado el: {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <div class="content">
            @if($student)
                <h3>Datos del Estudiante</h3>
                <table class="info-table">
                    <tr>
                        <td><strong>Nombre:</strong></td>
                        <td>{{ $student->nombres }} {{ $student->apellidos }}</td>
                    </tr>
                    <tr>
                        <td><strong>Cédula/Código:</strong></td>
                        <td>{{ $student->codigo }}</td>
                    </tr>
                </table>
            @endif

            <h3>Registros Académicos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Período</th>
                        <th>Materia</th>
                        <th>Calificación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->student->nombres }} {{ $record->student->apellidos }}</td>
                            <td>{{ $record->schoolPeriod->nombre }}</td>
                            <td>{{ $record->subject->nombre }}</td>
                            <td class="text-center">
                                @if($record->final_grade !== null)
                                    {{ number_format($record->final_grade, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusText = 'Matriculado';
                                    if ($record->status === 'completed') {
                                        $statusText = 'Completado';
                                    } elseif ($record->status === 'failed') {
                                        $statusText = 'Reprobado';
                                    } elseif ($record->status === 'withdrawn') {
                                        $statusText = 'Retirado';
                                    } elseif ($record->in_recovery) {
                                        $statusText = 'En recuperación';
                                    }
                                @endphp
                                {{ $statusText }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No se encontraron registros con los filtros aplicados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="page-footer">
            <p>U.E.P. Colegio Vargas - Sistema de Gestión Académica</p>
        </div>
    </div>
</body>
</html>
