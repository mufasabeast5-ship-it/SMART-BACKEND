<?php
// backend/api/students/index.php

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
        'SELECT id, name, email, class_id AS classId FROM students ORDER BY name'
    )->fetchAll();

    $rows = array_map(function ($r) {
        return [
            'id' => (string) $r['id'],
            'name' => $r['name'],
            'email' => $r['email'],
            'classId' => $r['classId'] !== null ? (string) $r['classId'] : '',
        ];
    }, $rows);

    jsonResponse($rows);
}

// ── CREATE ───────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    $name = trim($body['name'] ?? '');
    $email = trim($body['email'] ?? '');
    $classId = $body['classId'] ?? null;

    if (!$name || !$email)
        errorResponse('Name and email are required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        errorResponse('Invalid email');

    $ck = $pdo->prepare('SELECT id FROM students WHERE email = ?');
    $ck->execute([$email]);
    if ($ck->fetch())
        errorResponse('Email already exists', 409);

    $classIdVal = ($classId !== '' && $classId !== null) ? (int) $classId : null;

    $stmt = $pdo->prepare(
        'INSERT INTO students (name, email, class_id) VALUES (?, ?, ?)'
    );
    $stmt->execute([$name, $email, $classIdVal]);
    $id = (int) $pdo->lastInsertId();

    jsonResponse([
        'id' => (string) $id,
        'name' => $name,
        'email' => $email,
        'classId' => $classId ? (string) $classIdVal : '',
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
    $classId = $body['classId'] ?? null;

    if (!$name || !$email)
        errorResponse('Name and email are required');

    $ck = $pdo->prepare('SELECT id FROM students WHERE email = ? AND id != ?');
    $ck->execute([$email, $id]);
    if ($ck->fetch())
        errorResponse('Email already exists', 409);

    $classIdVal = ($classId !== '' && $classId !== null) ? (int) $classId : null;

    $stmt = $pdo->prepare(
        'UPDATE students SET name = ?, email = ?, class_id = ? WHERE id = ?'
    );
    $stmt->execute([$name, $email, $classIdVal, $id]);

    jsonResponse([
        'id' => (string) $id,
        'name' => $name,
        'email' => $email,
        'classId' => $classIdVal ? (string) $classIdVal : '',
    ]);
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id)
        errorResponse('Missing id');

    $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

errorResponse('Method not allowed', 405);
