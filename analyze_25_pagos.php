<?php
$pdoV = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdoT = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas_sync_temp', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$stmt = $pdoT->query("
    SELECT p.id, p.fecha, p.serie, p.numero, p.total, p.metodo_pago, p.referencia, p.created_at,
           s.nombres, s.apellidos, s.documento_identidad
    FROM pagos p
    LEFT JOIN matriculas m ON p.matricula_id = m.id
    LEFT JOIN students s ON m.estudiante_id = s.id
    WHERE p.id >= 1872
    ORDER BY p.id
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "ANALYSIS OF ALL 25 PAYMENTS (Temp 1872 to 1896):\n";
echo str_repeat("=", 110) . "\n";

foreach ($rows as $p) {
    echo sprintf("\n[TEMP #%d] %s | %s-%s | \$%s | Ref: '%s' | %s %s (%s)\n",
        $p['id'], $p['fecha'], $p['serie'], $p['numero'], $p['total'], $p['referencia'],
        $p['nombres'], $p['apellidos'], $p['documento_identidad']
    );

    // 1. Search in vargas by student document
    $searchStmt = $pdoV->prepare("
        SELECT p.id, p.fecha, p.serie, p.numero, p.total, p.metodo_pago, p.referencia, p.created_at
        FROM pagos p
        JOIN matriculas m ON p.matricula_id = m.id
        JOIN students s ON m.estudiante_id = s.id
        WHERE s.documento_identidad = ?
        ORDER BY p.fecha DESC, p.id DESC
    ");
    $searchStmt->execute([$p['documento_identidad']]);
    $vPayments = $searchStmt->fetchAll(PDO::FETCH_ASSOC);

    $exactMatch = null;
    $refMatch = null;
    $recentMatch = null;

    foreach ($vPayments as $vp) {
        if ($vp['referencia'] !== '' && $vp['referencia'] === $p['referencia']) {
            $refMatch = $vp;
        }
        if ($vp['fecha'] === $p['fecha'] && abs($vp['total'] - $p['total']) < 0.01) {
            $exactMatch = $vp;
        }
        if ($vp['fecha'] >= '2026-10-01') {
            $recentMatch[] = $vp;
        }
    }

    if ($refMatch) {
        echo sprintf("   MATCH BY REFERENCE in Vargas! ID: %d | %s | %s-%s | \$%s | Ref: %s\n",
            $refMatch['id'], $refMatch['fecha'], $refMatch['serie'], $refMatch['numero'], $refMatch['total'], $refMatch['referencia']
        );
    } elseif ($exactMatch) {
        echo sprintf("   MATCH BY DATE & TOTAL in Vargas! ID: %d | %s | %s-%s | \$%s | Ref: %s\n",
            $exactMatch['id'], $exactMatch['fecha'], $exactMatch['serie'], $exactMatch['numero'], $exactMatch['total'], $exactMatch['referencia']
        );
    } else {
        echo "   *** COMPLETELY MISSING IN VARGAS ***\n";
        if ($recentMatch) {
            echo "   (Student has other Oct 2026 payments in Vargas):\n";
            foreach ($recentMatch as $rm) {
                echo sprintf("      - Vargas #%d: %s | %s-%s | \$%s | Ref: %s\n",
                    $rm['id'], $rm['fecha'], $rm['serie'], $rm['numero'], $rm['total'], $rm['referencia']
                );
            }
        } else {
            echo "   (Student has NO payments in Oct 2026 in Vargas)\n";
        }
    }
}
