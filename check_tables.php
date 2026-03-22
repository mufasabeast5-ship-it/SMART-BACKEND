<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

echo "Tables in database:\n";
$stmt = $pdo->query('SHOW TABLES');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

echo "\nChecking if enrollment_requests exists specifically:\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'enrollment_requests'");
print_r($stmt->fetchAll());
