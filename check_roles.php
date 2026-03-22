<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();
$stmt = $pdo->query('SELECT email, role FROM users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
