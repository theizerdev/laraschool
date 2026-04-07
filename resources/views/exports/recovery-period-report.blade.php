<table>
    <thead>
        <tr>
            <th colspan="5" style="text-align: center; font-weight: bold; font-size: 16px;">Reporte del Período de Recuperación</th>
        </tr>
        <tr>
            <th colspan="5" style="text-align: center; font-weight: bold;">{{ $period->name }}</th>
        </tr>
        <tr>
            <th colspan="2">Período Lectivo:</th>
            <td colspan="3">{{ $period->schoolPeriod->name }}</td>
        </tr>
        <tr>
            <th colspan="2">Fechas del Período:</th>
            <td colspan="3">{{ optional($period->start_date)->format('d/m/Y') }} - {{ optional($period->end_date)->format('d/m/Y') }}</td>
        </tr>
        <tr></tr>
        <tr>
            <th style="font-weight: bold;">Estudiante</th>
            <th style="font-weight: bold;">Código</th>
            <th style="font-weight: bold;">Materia</th>
            <th style="font-weight: bold;">Nota Final</th>
            <th style="font-weight: bold;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $enrollment)
            <tr>
                <td>{{ $enrollment->student->full_name }}</td>
                <td>{{ $enrollment->student->codigo }}</td>
                <td>{{ $enrollment->subject->name }}</td>
                <td>{{ $enrollment->final_grade ?? 'N/A' }}</td>
                <td>{{ $enrollment->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
