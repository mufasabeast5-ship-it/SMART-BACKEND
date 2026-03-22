<?php
// backend/api/auth/login.php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/response.php';

applyCors();
requireMethod('POST');

configureSession();
session_start();

$body = getBody();
$email = trim($body['email'] ?? '');
$pass = $body['password'] ?? '';

if (!$email || !$pass) {
    errorResponse('Email and password are required');
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password'])) {
        errorResponse('Invalid credentials', 401);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $email, // email from $user['email']
            'role' => $user['role'],
        ],
    ]);
} catch (Exception $e) {
    errorResponse('Database error: ' . $e->getMessage(), 500);
}
