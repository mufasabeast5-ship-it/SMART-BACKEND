<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

echo "Updating users table role enum...\n";
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'admin'");
    echo "Enum updated successfully.\n";
} catch (Exception $e) {
    echo "Error updating enum: " . $e->getMessage() . "\n";
}

echo "Fixing users with empty roles...\n";
// Assuming users with empty roles are students (or at least we should recover them)
$stmt = $pdo->query("SELECT email FROM users WHERE role = '' OR role IS NULL");
$emptyUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (count($emptyUsers) > 0) {
    foreach ($emptyUsers as $email) {
        $pdo->prepare("UPDATE users SET role = 'student' WHERE email = ?")->execute([$email]);
        echo "Set role to 'student' for user: $email\n";
    }
} else {
    echo "No users found with empty roles.\n";
}

echo "Checking if anyone else needs a fix...\n";
// Sometimes users are intended to be students but fall back to 'admin' default if insertion failed? 
// No, the empty string check is more likely for invalid ENUM values.

echo "Final check of user roles:\n";
$stmt = $pdo->query('SELECT email, role FROM users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
