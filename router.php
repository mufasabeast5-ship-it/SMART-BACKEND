<?php
// backend/router.php
// PHP built-in server router — maps clean URLs to the correct .php files

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// If the URI points to an existing file (e.g. a real .php), serve it
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // let PHP built-in server handle it directly
}

// Try adding /index.php or /index.html to directory URIs
$indexPhp = rtrim($file, '/') . '/index.php';
$indexHtml = rtrim($file, '/') . '/index.html';

if (file_exists($indexPhp)) {
    require $indexPhp;
    return true;
}

if (file_exists($indexHtml)) {
    header('Content-Type: text/html');
    readfile($indexHtml);
    return true;
}

// 404 fallback
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => 'Not found: ' . $uri]);
