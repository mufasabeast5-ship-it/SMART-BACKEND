<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

echo "Teachers table:\n";
$stmt = $pdo->query('SELECT id, name, email FROM teachers');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nUsers table (teachers only):\n";
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'teacher'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
