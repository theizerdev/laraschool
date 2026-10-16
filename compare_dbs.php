<?php
$pdoV = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdoT = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas_sync_temp', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$tables = $pdoV->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo "TABLE SUMMARY:\n";
echo str_repeat('-', 70) . "\n";
echo sprintf("%-30s | %-10s | %-10s | %-10s\n", "Table", "vargas", "temp", "temp - var");
echo str_repeat('-', 70) . "\n";

$interesting = [];

foreach ($tables as $table) {
    $countV = (int) $pdoV->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    $hasTableT = $pdoT->query("SHOW TABLES LIKE '{$table}'")->fetchColumn();
    if (!$hasTableT) {
        echo sprintf("%-30s | %10d | %-10s | %-10s\n", $table, $countV, "MISSING", "N/A");
        continue;
    }
    $countT = (int) $pdoT->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    $diff = $countT - $countV;
    
    if ($countT > 0 || $countV > 0) {
        echo sprintf("%-30s | %10d | %10d | %+10d\n", $table, $countV, $countT, $diff);
    }
}
