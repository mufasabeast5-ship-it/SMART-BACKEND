<?php

$host = 'centerbeam.proxy.rlwy.net';
$port = '39594';
$user = 'root';
$pass = 'fhWBemXhgSAquuRXZlYCMVxCtTafoIrA';
$db   = 'railway';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    
    echo "Tables in $db:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found.\n";
    } else {
        foreach ($tables as $table) {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table` ");
            $rowCount = $countStmt->fetchColumn();
            echo "- $table ($rowCount rows)\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
