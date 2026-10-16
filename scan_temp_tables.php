<?php
$pdoT = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas_sync_temp', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$tables = $pdoT->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo "SCANNING ALL TABLES IN TEMP FOR RECORDS ON OR AFTER 2026-10-02:\n";
echo str_repeat("=", 80) . "\n";

foreach ($tables as $t) {
    // check which date columns exist
    $cols = $pdoT->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_COLUMN);
    $dateCols = array_intersect($cols, ['created_at', 'updated_at', 'fecha', 'fecha_matricula', 'fecha_pago', 'date']);
    
    if (empty($dateCols)) {
        continue;
    }
    
    $whereParts = [];
    foreach ($dateCols as $dc) {
        $whereParts[] = "`$dc` >= '2026-10-02 00:00:00'";
    }
    $where = implode(' OR ', $whereParts);
    
    try {
        $count = (int) $pdoT->query("SELECT COUNT(*) FROM `$t` WHERE $where")->fetchColumn();
        if ($count > 0) {
            echo sprintf("%-30s: %d records\n", $t, $count);
            // Show distinct dates
            foreach ($dateCols as $dc) {
                $dates = $pdoT->query("SELECT DISTINCT DATE(`$dc`) as d FROM `$t` WHERE `$dc` >= '2026-10-02 00:00:00' ORDER BY d")->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($dates)) {
                    echo sprintf("   [%s]: %s\n", $dc, implode(', ', $dates));
                }
            }
        }
    } catch (Exception $e) {
        // ignore
    }
}
