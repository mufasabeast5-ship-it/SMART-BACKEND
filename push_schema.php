<?php

$host = 'centerbeam.proxy.rlwy.net';
$port = '39594';
$user = 'root';
$pass = 'fhWBemXhgSAquuRXZlYCMVxCtTafoIrA';
$db   = 'railway';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    echo "Connecting to $host:$port...\n";
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected successfully.\n";

    $sqlFile = __DIR__ . '/database/schema_railway.sql';
    if (!file_exists($sqlFile)) {
        die("SQL file not found at $sqlFile\n");
    }

    $sql = file_get_contents($sqlFile);
    
    // Execute multiple queries
    // PDO::exec doesn't handle multiple queries separated by semicolons well in some drivers,
    // so we'll try it and if it fails, we'll split them.
    
    echo "Pushing schema...\n";
    $pdo->exec($sql);
    echo "Schema pushed successfully.\n";

} catch (PDOException $e) {
    if ($e->getCode() == 2002) {
        echo "Error: Could not connect to the database. Check if the database is running and accessible.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
    exit(1);
}
