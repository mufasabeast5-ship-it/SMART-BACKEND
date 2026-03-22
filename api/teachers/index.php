<?php
// backend/api/teachers/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../helpers/response.php';

applyCors();
configureSession();
session_start();

if (empty($_SESSION['user_id'])) {
    errorResponse('Not authenticated', 401);
}

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── LIST ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $rows = $pdo->query(
        'SELECT id, name, email, subject FROM teachers ORDER BY name'
    )->fetchAll();

    $rows = array_map(fn($r) => [
        'id' => (string) $r['id'],
        'name' => $r['name'],
        'email' => $r['email'],
        'subject' => $r['subject'],
    ], $rows);

    jsonResponse($rows);
}

// ── CREATE ───────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    $name = trim($body['name'] ?? '');
    $email = trim($body['email'] ?? '');
    $subject = trim($body['subject'] ?? '');

    if (!$name || !$email)
        errorResponse('Name and email are required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        errorResponse('Invalid email');

    $ck = $pdo->prepare('SELECT id FROM teachers WHERE email = ?');
    $ck->execute([$email]);
    if ($ck->fetch())
        errorResponse('Email already exists', 409);

    $stmt = $pdo->prepare(
        'INSERT INTO teachers (name, email, subject) VALUES (?, ?, ?)'
    );
    $stmt->execute([$name, $email, $subject]);
    $id = (int) $pdo->lastInsertId();

    jsonResponse([
        'id' => (string) $id,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
    ], 201);
}

// ── UPDATE ───────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id)
        errorResponse('Missing id');

    $body = getBody();
    $name = trim($body['name'] ?? '');
    $email = trim($body['email'] ?? '');
    $subject = trim($body['subject'] ?? '');

    if (!$name || !$email)
        errorResponse('Name and email are required');

    $ck = $pdo->prepare('SELECT id FROM teachers WHERE email = ? AND id != ?');
    $ck->execute([$email, $id]);
    if ($ck->fetch())
        errorResponse('Email already exists', 409);

    $stmt = $pdo->prepare(
        'UPDATE teachers SET name = ?, email = ?, subject = ? WHERE id = ?'
    );
    $stmt->execute([$name, $email, $subject, $id]);

    jsonResponse([
        'id' => (string) $id,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
    ]);
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id)
        errorResponse('Missing id');

    $pdo->prepare('DELETE FROM teachers WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

errorResponse('Method not allowed', 405);
