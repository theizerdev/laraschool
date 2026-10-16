<?php
/**
 * Genera el script SQL delta para aplicar directamente en producción.
 */

$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$sql = "-- ====================================================================\n";
$sql .= "-- SCRIPT DE SINCRONIZACIÓN ADMINISTRATIVA PARA PRODUCCIÓN\n";
$sql .= "-- Base de datos: vargas\n";
$sql .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- ====================================================================\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$sql .= "START TRANSACTION;\n\n";

// 1. Estudiante
$sql .= "-- 1. NUEVO ESTUDIANTE\n";
$student = $pdo->query("SELECT * FROM students WHERE id = 253")->fetch();
$cols = array_keys($student);
$colList = implode(', ', array_map(fn($c) => "`$c`", $cols));
$valList = implode(', ', array_map(function($v) use ($pdo) {
    return is_null($v) ? 'NULL' : $pdo->quote($v);
}, array_values($student)));
$sql .= "INSERT INTO `students` ($colList) VALUES ($valList);\n\n";

// 2. Cajas
$sql .= "-- 2. CAJAS SINCRONIZADAS (05/10, 07/10 corte 2, 15/10 corte 2)\n";
$cajas = $pdo->query("SELECT * FROM cajas WHERE id IN (158, 159, 160) ORDER BY id")->fetchAll();
foreach ($cajas as $c) {
    $cols = array_keys($c);
    $colList = implode(', ', array_map(fn($col) => "`$col`", $cols));
    $valList = implode(', ', array_map(fn($v) => is_null($v) ? 'NULL' : $pdo->quote($v), array_values($c)));
    $sql .= "INSERT INTO `cajas` ($colList) VALUES ($valList);\n";
}
$sql .= "\n";

// 3. Matrículas
$sql .= "-- 3. MATRÍCULAS SINCRONIZADAS DEL 05/10\n";
$mats = $pdo->query("SELECT * FROM matriculas WHERE id IN (355, 356, 357, 358) ORDER BY id")->fetchAll();
foreach ($mats as $m) {
    $cols = array_keys($m);
    $colList = implode(', ', array_map(fn($col) => "`$col`", $cols));
    $valList = implode(', ', array_map(fn($v) => is_null($v) ? 'NULL' : $pdo->quote($v), array_values($m)));
    $sql .= "INSERT INTO `matriculas` ($colList) VALUES ($valList);\n";
}
$sql .= "\n";

// 4. Payment Schedules
$sql .= "-- 4. CUOTAS DE LAS 4 MATRÍCULAS NUEVAS (52 cuotas)\n";
$schedules = $pdo->query("SELECT * FROM payment_schedules WHERE matricula_id IN (355, 356, 357, 358) ORDER BY id")->fetchAll();
foreach ($schedules as $s) {
    $cols = array_keys($s);
    $colList = implode(', ', array_map(fn($col) => "`$col`", $cols));
    $valList = implode(', ', array_map(fn($v) => is_null($v) ? 'NULL' : $pdo->quote($v), array_values($s)));
    $sql .= "INSERT INTO `payment_schedules` ($colList) VALUES ($valList);\n";
}
$sql .= "\n";

// 5. Pagos
$sql .= "-- 5. PAGOS SINCRONIZADOS (24 pagos)\n";
$pagos = $pdo->query("SELECT * FROM pagos WHERE id BETWEEN 1975 AND 1998 ORDER BY id")->fetchAll();
foreach ($pagos as $p) {
    $cols = array_keys($p);
    $colList = implode(', ', array_map(fn($col) => "`$col`", $cols));
    $valList = implode(', ', array_map(fn($v) => is_null($v) ? 'NULL' : $pdo->quote($v), array_values($p)));
    $sql .= "INSERT INTO `pagos` ($colList) VALUES ($valList);\n";
}
$sql .= "\n";

// 6. Pago Detalles
$sql .= "-- 6. DETALLES DE LOS PAGOS (38 detalles)\n";
$detalles = $pdo->query("SELECT * FROM pago_detalles WHERE pago_id BETWEEN 1975 AND 1998 ORDER BY id")->fetchAll();
foreach ($detalles as $d) {
    $cols = array_keys($d);
    $colList = implode(', ', array_map(fn($col) => "`$col`", $cols));
    $valList = implode(', ', array_map(fn($v) => is_null($v) ? 'NULL' : $pdo->quote($v), array_values($d)));
    $sql .= "INSERT INTO `pago_detalles` ($colList) VALUES ($valList);\n";
}
$sql .= "\n";

// 7. Actualización de cuotas previas afectadas
$sql .= "-- 7. ACTUALIZACIÓN DE CUOTAS AFECTADAS POR LOS PAGOS\n";
$schedUpdates = [
    1976 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    2456 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    2068 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    2191 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    921  => ['monto_pagado' => 85.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    922  => ['monto_pagado' => 85.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    923  => ['monto_pagado' => 85.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    1117 => ['monto_pagado' => 70.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    1118 => ['monto_pagado' => 70.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    1130 => ['monto_pagado' => 70.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    1131 => ['monto_pagado' => 70.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-05'],
    2279 => ['monto_pagado' => 50.00, 'estado' => 'parcial', 'fecha_pago' => null],
    3277 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-07'],
    1905 => ['monto_pagado' => 90.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-07'],
    2982 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-15'],
    2995 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-15'],
    2996 => ['monto_pagado' => 10.00, 'estado' => 'parcial', 'fecha_pago' => null],
    3568 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-15'],
    3569 => ['monto_pagado' => 30.00, 'estado' => 'parcial', 'fecha_pago' => null],
    2139 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-15'],
    2961 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-15'],
    3041 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-15'],
    3278 => ['monto_pagado' => 10.00, 'estado' => 'parcial', 'fecha_pago' => null],
    1918 => ['monto_pagado' => 95.00, 'estado' => 'pagado', 'fecha_pago' => '2026-10-15'],
];

foreach ($schedUpdates as $id => $u) {
    $fp = is_null($u['fecha_pago']) ? 'NULL' : $pdo->quote($u['fecha_pago']);
    $sql .= "UPDATE `payment_schedules` SET `monto_pagado` = {$u['monto_pagado']}, `estado` = '{$u['estado']}', `fecha_pago` = $fp, `updated_at` = NOW() WHERE `id` = $id;\n";
}
$sql .= "\n";

// 8. Series
$sql .= "-- 8. ACTUALIZACIÓN DE SERIES (CORRELATIVO ACTUAL)\n";
$sql .= "UPDATE `series` SET `correlativo_actual` = 1459, `updated_at` = NOW() WHERE `serie` = 'R001';\n";
$sql .= "UPDATE `series` SET `correlativo_actual` = 227, `updated_at` = NOW() WHERE `serie` = 'CE001';\n\n";

$sql .= "COMMIT;\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/sync_production_delta.sql', $sql);
echo "Archivo sync_production_delta.sql generado exitosamente (" . strlen($sql) . " bytes).\n";
