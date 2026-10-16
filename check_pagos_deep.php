<?php
$pdoV = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdoT = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas_sync_temp', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "COMPARING TEMP PAGOS (Oct 2 - Oct 15) WITH VARGAS:\n";
echo str_repeat("=", 100) . "\n";

$stmt = $pdoT->query("
    SELECT p.id, p.fecha, p.serie, p.numero, p.total, p.metodo_pago, p.referencia, p.created_at,
           s.nombres, s.apellidos, s.documento_identidad
    FROM pagos p
    LEFT JOIN matriculas m ON p.matricula_id = m.id
    LEFT JOIN students s ON m.estudiante_id = s.id
    WHERE p.id >= 1870
    ORDER BY p.id
");

while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("\nTEMP PAGO #%d | %s-%s | %s | \$%s | Ref: %s | Est: %s %s (%s)\n",
        $p['id'], $p['serie'], $p['numero'], $p['fecha'], $p['total'], $p['referencia'],
        $p['nombres'], $p['apellidos'], $p['documento_identidad']
    );

    // Find in Vargas by Serie + Numero:
    $stmtV1 = $pdoV->prepare("
        SELECT p.id, p.fecha, p.serie, p.numero, p.total, p.metodo_pago, p.referencia, p.created_at,
               s.nombres, s.apellidos, s.documento_identidad
        FROM pagos p
        LEFT JOIN matriculas m ON p.matricula_id = m.id
        LEFT JOIN students s ON m.estudiante_id = s.id
        WHERE p.serie = ? AND p.numero = ?
    ");
    $stmtV1->execute([$p['serie'], $p['numero']]);
    $v1 = $stmtV1->fetch(PDO::FETCH_ASSOC);
    if ($v1) {
        echo sprintf("  -> VARGAS SAME RECEIPT # (%s-%s): ID %d | %s | \$%s | Ref: %s | Est: %s %s (%s)\n",
            $v1['serie'], $v1['numero'], $v1['id'], $v1['fecha'], $v1['total'], $v1['referencia'],
            $v1['nombres'], $v1['apellidos'], $v1['documento_identidad']
        );
    } else {
        echo "  -> NO RECEIPT NUMBER MATCH IN VARGAS\n";
    }

    // Find in Vargas by Student + Amount + Date/Ref:
    $stmtV2 = $pdoV->prepare("
        SELECT p.id, p.fecha, p.serie, p.numero, p.total, p.referencia
        FROM pagos p
        LEFT JOIN matriculas m ON p.matricula_id = m.id
        LEFT JOIN students s ON m.estudiante_id = s.id
        WHERE s.documento_identidad = ?
    ");
    $stmtV2->execute([$p['documento_identidad']]);
    $v2 = $stmtV2->fetchAll(PDO::FETCH_ASSOC);
    if ($v2) {
        echo "  -> VARGAS payments for this student:\n";
        foreach ($v2 as $vp) {
            echo sprintf("     - Vargas ID %d: %s-%s | %s | \$%s | Ref: %s\n",
                $vp['id'], $vp['serie'], $vp['numero'], $vp['fecha'], $vp['total'], $vp['referencia']
            );
        }
    } else {
        echo "  -> NO PAYMENTS FOR THIS STUDENT IN VARGAS AT ALL!\n";
    }
}
