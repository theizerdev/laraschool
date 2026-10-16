<?php
$pdoV = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdoT = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas_sync_temp', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "==================== 1. STUDENTS IN TEMP ====================\n";
$stmt = $pdoT->query("SELECT id, nombres, apellidos, documento_identidad, created_at FROM students WHERE id >= 235");
$studentsT = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($studentsT as $st) {
    $exists = $pdoV->prepare("SELECT id, nombres, apellidos, documento_identidad FROM students WHERE documento_identidad = ?");
    $exists->execute([$st['documento_identidad']]);
    $vRow = $exists->fetch(PDO::FETCH_ASSOC);
    if ($vRow) {
        echo "MATCHED: Temp ID {$st['id']} -> Vargas ID {$vRow['id']} | {$st['nombres']} {$st['apellidos']} ({$st['documento_identidad']})\n";
    } else {
        echo "MISSING IN VARGAS: Temp ID {$st['id']} | {$st['nombres']} {$st['apellidos']} ({$st['documento_identidad']}) created: {$st['created_at']}\n";
    }
}

echo "\n==================== 2. CAJAS IN TEMP ====================\n";
$stmt = $pdoT->query("SELECT id, fecha, numero_corte, estado, monto_inicial, total_ingresos, monto_final, created_at FROM cajas WHERE fecha >= '2026-10-01' ORDER BY fecha, numero_corte");
$cajasT = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cajasT as $c) {
    $exists = $pdoV->prepare("SELECT id, fecha, numero_corte, total_ingresos FROM cajas WHERE fecha = ? AND numero_corte = ?");
    $exists->execute([$c['fecha'], $c['numero_corte']]);
    $vRow = $exists->fetch(PDO::FETCH_ASSOC);
    if ($vRow) {
        echo "MATCHED: Temp Caja {$c['id']} -> Vargas Caja {$vRow['id']} | Date: {$c['fecha']} Corte: {$c['numero_corte']} | Temp Inc: {$c['total_ingresos']} vs Var Inc: {$vRow['total_ingresos']}\n";
    } else {
        echo "MISSING IN VARGAS: Temp Caja {$c['id']} | Date: {$c['fecha']} Corte: {$c['numero_corte']} | Inc: {$c['total_ingresos']} Final: {$c['monto_final']} Created: {$c['created_at']}\n";
    }
}

echo "\n==================== 3. MATRICULAS IN TEMP (Oct 2026) ====================\n";
$stmt = $pdoT->query("
    SELECT m.id, m.estudiante_id, s.nombres, s.apellidos, s.documento_identidad, m.programa_id, m.periodo_id, m.costo, m.fecha_matricula, m.created_at 
    FROM matriculas m
    JOIN students s ON m.estudiante_id = s.id
    WHERE m.fecha_matricula >= '2026-10-01'
    ORDER BY m.fecha_matricula, m.id
");
$matT = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($matT as $m) {
    // Check if matricula exists in vargas for this student (by documento_identidad)
    $stmtV = $pdoV->prepare("
        SELECT m.id, m.estudiante_id, m.fecha_matricula, m.created_at
        FROM matriculas m
        JOIN students s ON m.estudiante_id = s.id
        WHERE s.documento_identidad = ? AND m.programa_id = ? AND m.periodo_id = ?
    ");
    $stmtV->execute([$m['documento_identidad'], $m['programa_id'], $m['periodo_id']]);
    $vRow = $stmtV->fetch(PDO::FETCH_ASSOC);
    if ($vRow) {
        echo "MATCHED: Temp Mat {$m['id']} -> Vargas Mat {$vRow['id']} | Doc: {$m['documento_identidad']} | Temp Date: {$m['fecha_matricula']} vs Var Date: {$vRow['fecha_matricula']}\n";
    } else {
        echo "MISSING IN VARGAS: Temp Mat {$m['id']} | Student: {$m['nombres']} {$m['apellidos']} ({$m['documento_identidad']}) | Date: {$m['fecha_matricula']} | Costo: {$m['costo']}\n";
    }
}

echo "\n==================== 4. PAGOS IN TEMP (Oct 2026) ====================\n";
$stmt = $pdoT->query("
    SELECT p.id, p.caja_id, p.serie, p.numero, p.fecha, p.total, p.total_bolivares, p.metodo_pago, p.referencia, p.created_at,
           s.documento_identidad, s.nombres, s.apellidos
    FROM pagos p
    LEFT JOIN matriculas m ON p.matricula_id = m.id
    LEFT JOIN students s ON m.estudiante_id = s.id
    WHERE p.fecha >= '2026-10-01'
    ORDER BY p.fecha, p.id
");
$pagosT = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pagosT as $p) {
    // Look up in vargas by serie + numero
    $stmtV = $pdoV->prepare("SELECT id, caja_id, serie, numero, fecha, total, referencia FROM pagos WHERE serie = ? AND numero = ?");
    $stmtV->execute([$p['serie'], $p['numero']]);
    $vRow = $stmtV->fetch(PDO::FETCH_ASSOC);
    if ($vRow) {
        echo "MATCHED: Temp Pago {$p['id']} -> Var Pago {$vRow['id']} | #{$p['serie']}-{$p['numero']} | Date: {$p['fecha']} vs Var: {$vRow['fecha']} | Tot: {$p['total']} vs {$vRow['total']}\n";
    } else {
        echo "MISSING IN VARGAS: Temp Pago {$p['id']} | #{$p['serie']}-{$p['numero']} | Date: {$p['fecha']} | Tot: {$p['total']} | Ref: {$p['referencia']} | Student: {$p['nombres']} {$p['apellidos']} ({$p['documento_identidad']})\n";
    }
}
