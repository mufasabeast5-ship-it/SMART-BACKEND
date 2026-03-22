<?php
// backend/config/cors.php
// Apply CORS headers so Vite dev-server (localhost:8080) can call our API
require_once __DIR__ . '/../helpers/env.php';
loadEnv(__DIR__ . '/../.env');

function applyCors(): void
{
    $envOrigins = getenv('ALLOWED_ORIGINS');
    $defaultOrigins = ['http://localhost:8080', 'http://localhost:3000', 'http://127.0.0.1:8080'];
    $allowed = $envOrigins ? array_map('trim', explode(',', $envOrigins)) : $defaultOrigins;
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Check if origin is explicitly allowed or matches a pattern (localhost or .vercel.app)
    $isAllowed = in_array($origin, $allowed, true);
    if (!$isAllowed && $origin) {
        $host = parse_url($origin, PHP_URL_HOST);
        if ($host && (str_ends_with($host, '.vercel.app') || $host === 'localhost' || $host === '127.0.0.1')) {
            $isAllowed = true;
        }
    }

    if ($isAllowed && $origin) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: " . ($allowed[0] ?? $defaultOrigins[0]));
    }

    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
