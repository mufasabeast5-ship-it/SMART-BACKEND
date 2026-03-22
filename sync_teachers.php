<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

echo "Syncing users with role 'teacher' to teachers table...\n";

$users = $pdo->query("SELECT name, email FROM users WHERE role = 'teacher'")->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    $check = $pdo->prepare("SELECT id FROM teachers WHERE email = ?");
    $check->execute([$u['email']]);
    if (!$check->fetch()) {
        $ins = $pdo->prepare("INSERT INTO teachers (name, email) VALUES (?, ?)");
        $ins->execute([$u['name'], $u['email']]);
        echo "Added teacher: {$u['name']} ({$u['email']})\n";
    } else {
        echo "Teacher already exists: {$u['name']}\n";
    }
}

echo "Sync complete.\n";
