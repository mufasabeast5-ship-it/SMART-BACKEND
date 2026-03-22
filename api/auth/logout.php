<?php
// backend/api/auth/logout.php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/response.php';

applyCors();
requireMethod('POST');

configureSession();
session_start();
session_destroy();

jsonResponse(['success' => true]);
