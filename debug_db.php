<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

echo "Teachers table contents:\n";
$stmt = $pdo->query('SELECT id, name, email FROM teachers');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nUsers with teacher role:\n";
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'teacher'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
