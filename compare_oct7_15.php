<?php
$pdoV = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdoT = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas_sync_temp', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "VARGAS PAYMENTS ON 2026-10-07:\n";
$stmt = $pdoV->query("
    SELECT p.id, p.caja_id, p.serie, p.numero, p.fecha, p.total, p.referencia, p.user_id, s.nombres, s.apellidos, s.documento_identidad
    FROM pagos p
    JOIN matriculas m ON p.matricula_id = m.id
    JOIN students s ON m.estudiante_id = s.id
    WHERE p.fecha = '2026-10-07'
");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  ID %d | Caja: %d | %s-%s | \$%s | Ref: %s | User: %d | %s %s (%s)\n",
        $r['id'], $r['caja_id'], $r['serie'], $r['numero'], $r['total'], $r['referencia'], $r['user_id'], $r['nombres'], $r['apellidos'], $r['documento_identidad']
    );
}

echo "\nTEMP PAYMENTS ON 2026-10-07:\n";
$stmt = $pdoT->query("
    SELECT p.id, p.caja_id, p.serie, p.numero, p.fecha, p.total, p.referencia, p.user_id, s.nombres, s.apellidos, s.documento_identidad
    FROM pagos p
    JOIN matriculas m ON p.matricula_id = m.id
    JOIN students s ON m.estudiante_id = s.id
    WHERE p.fecha = '2026-10-07'
");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  ID %d | Caja: %d | %s-%s | \$%s | Ref: %s | User: %d | %s %s (%s)\n",
        $r['id'], $r['caja_id'], $r['serie'], $r['numero'], $r['total'], $r['referencia'], $r['user_id'], $r['nombres'], $r['apellidos'], $r['documento_identidad']
    );
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "VARGAS PAYMENTS ON 2026-10-15:\n";
$stmt = $pdoV->query("
    SELECT p.id, p.caja_id, p.serie, p.numero, p.fecha, p.total, p.referencia, p.user_id, s.nombres, s.apellidos, s.documento_identidad
    FROM pagos p
    JOIN matriculas m ON p.matricula_id = m.id
    JOIN students s ON m.estudiante_id = s.id
    WHERE p.fecha = '2026-10-15'
");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  ID %d | Caja: %d | %s-%s | \$%s | Ref: %s | User: %d | %s %s (%s)\n",
        $r['id'], $r['caja_id'], $r['serie'], $r['numero'], $r['total'], $r['referencia'], $r['user_id'], $r['nombres'], $r['apellidos'], $r['documento_identidad']
    );
}

echo "\nTEMP PAYMENTS ON 2026-10-15:\n";
$stmt = $pdoT->query("
    SELECT p.id, p.caja_id, p.serie, p.numero, p.fecha, p.total, p.referencia, p.user_id, s.nombres, s.apellidos, s.documento_identidad
    FROM pagos p
    JOIN matriculas m ON p.matricula_id = m.id
    JOIN students s ON m.estudiante_id = s.id
    WHERE p.fecha = '2026-10-15'
");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  ID %d | Caja: %d | %s-%s | \$%s | Ref: %s | User: %d | %s %s (%s)\n",
        $r['id'], $r['caja_id'], $r['serie'], $r['numero'], $r['total'], $r['referencia'], $r['user_id'], $r['nombres'], $r['apellidos'], $r['documento_identidad']
    );
}
