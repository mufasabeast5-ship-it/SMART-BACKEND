<?php
// backend/api/auth/signup.php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/response.php';

applyCors();
requireMethod('POST');

configureSession();
session_start();

$body = getBody();
$name = trim($body['name'] ?? '');
$email = trim($body['email'] ?? '');
$pass = $body['password'] ?? '';
$role = $body['role'] ?? 'admin';

if (!in_array($role, ['admin', 'teacher', 'student'])) {
    $role = 'admin';
}

if (!$name || !$email || !$pass) {
    errorResponse('Name, email, and password are required');
}
if (strlen($pass) < 6) {
    errorResponse('Password must be at least 6 characters');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    errorResponse('Invalid email address');
}

$pdo = getDB();

$check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$email]);
if ($check->fetch()) {
    errorResponse('Email already registered', 409);
}

$hash = password_hash($pass, PASSWORD_BCRYPT);
$ins = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
$ins->execute([$name, $email, $hash, $role]);
$id = (int) $pdo->lastInsertId();

if ($role === 'teacher') {
    $insT = $pdo->prepare('INSERT INTO teachers (name, email) VALUES (?, ?)');
    $insT->execute([$name, $email]);
}

$_SESSION['user_id'] = $id;
$_SESSION['user_name'] = $name;
$_SESSION['user_email'] = $email;
$_SESSION['user_role'] = $role;

jsonResponse([
    'success' => true,
    'user' => [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'role' => $role,
    ],
], 201);
