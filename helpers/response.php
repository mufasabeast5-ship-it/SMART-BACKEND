<?php
// backend/helpers/response.php

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function errorResponse(string $message, int $status = 400): void
{
    jsonResponse(['error' => $message], $status);
}

function getBody(): array
{
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function requireMethod(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        errorResponse('Method not allowed', 405);
    }
}
