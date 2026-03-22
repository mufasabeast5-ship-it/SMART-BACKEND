<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

echo "Enrollment Requests:\n";
$stmt = $pdo->query('SELECT er.*, c.name as className, c.teacher_id FROM enrollment_requests er JOIN classes c ON er.class_id = c.id');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nClasses:\n";
$stmt = $pdo->query('SELECT id, name, section, teacher_id FROM classes');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nTeachers:\n";
$stmt = $pdo->query('SELECT id, name, email FROM teachers');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nSessions/Users info if possible (can't do because no session here, but let's see users):\n";
$stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'teacher'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
