<?php
// backend/api/auth/me.php
// Returns the currently-logged-in user (session check)
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/response.php';

applyCors();
requireMethod('GET');

configureSession();
session_start();

if (empty($_SESSION['user_id'])) {
    errorResponse('Not authenticated', 401);
}

jsonResponse([
    'user' => [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
    ],
]);
