<?php
// backend/api/classes/index.php

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
    $role = $_SESSION['user_role'] ?? 'admin';
    $email = $_SESSION['user_email'] ?? '';

    if ($role === 'teacher') {
        $stmt = $pdo->prepare(
            'SELECT c.id, c.name, c.section, c.teacher_id AS teacherId 
             FROM classes c
             JOIN teachers t ON c.teacher_id = t.id
             WHERE t.email = ?
             ORDER BY c.name, c.section'
        );
        $stmt->execute([$email]);
        $rows = $stmt->fetchAll();
    } else {
        $rows = $pdo->query(
            'SELECT id, name, section, teacher_id AS teacherId FROM classes ORDER BY name, section'
        )->fetchAll();
    }

    $rows = array_map(fn($r) => [
        'id' => (string) $r['id'],
        'name' => $r['name'],
        'section' => $r['section'],
        'teacherId' => $r['teacherId'] !== null ? (string) $r['teacherId'] : '',
    ], $rows);

    jsonResponse($rows);
}

// ── CREATE ───────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    $name = trim($body['name'] ?? '');
    $section = trim($body['section'] ?? '');
    $teacherId = $body['teacherId'] ?? null;
    $role = $_SESSION['user_role'] ?? 'admin';
    $email = $_SESSION['user_email'] ?? '';

    if ($role === 'teacher') {
        $st = $pdo->prepare('SELECT id FROM teachers WHERE email = ?');
        $st->execute([$email]);
        $t = $st->fetch();
        if ($t) {
            $teacherId = $t['id'];
        }
    }

    if (!$name || !$section)
        errorResponse('Name and section are required');

    $teacherIdVal = ($teacherId !== '' && $teacherId !== null) ? (int) $teacherId : null;

    $stmt = $pdo->prepare(
        'INSERT INTO classes (name, section, teacher_id) VALUES (?, ?, ?)'
    );
    $stmt->execute([$name, $section, $teacherIdVal]);
    $id = (int) $pdo->lastInsertId();

    jsonResponse([
        'id' => (string) $id,
        'name' => $name,
        'section' => $section,
        'teacherId' => $teacherIdVal ? (string) $teacherIdVal : '',
    ], 201);
}

// ── UPDATE ───────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id)
        errorResponse('Missing id');

    $body = getBody();
    $name = trim($body['name'] ?? '');
    $section = trim($body['section'] ?? '');
    $teacherId = $body['teacherId'] ?? null;

    if (!$name || !$section)
        errorResponse('Name and section are required');

    $teacherIdVal = ($teacherId !== '' && $teacherId !== null) ? (int) $teacherId : null;

    $stmt = $pdo->prepare(
        'UPDATE classes SET name = ?, section = ?, teacher_id = ? WHERE id = ?'
    );
    $stmt->execute([$name, $section, $teacherIdVal, $id]);

    jsonResponse([
        'id' => (string) $id,
        'name' => $name,
        'section' => $section,
        'teacherId' => $teacherIdVal ? (string) $teacherIdVal : '',
    ]);
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id)
        errorResponse('Missing id');

    $pdo->prepare('DELETE FROM classes WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

errorResponse('Method not allowed', 405);
