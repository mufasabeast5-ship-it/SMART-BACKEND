<?php
// backend/api/lessons/index.php

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
$role = $_SESSION['user_role'] ?? 'admin';
$email = $_SESSION['user_email'] ?? '';

// ── LIST ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $classId = (int) ($_GET['classId'] ?? 0);

    // If a classId is provided, just list lessons for that class
    if ($classId) {
        $stmt = $pdo->prepare('SELECT id, title, description, lesson_date AS date, class_id AS classId FROM lessons WHERE class_id = ? ORDER BY lesson_date DESC');
        $stmt->execute([$classId]);
        $rows = $stmt->fetchAll();
    } else {
        // Otherwise list based on role
        if ($role === 'teacher') {
            $stmt = $pdo->prepare(
                'SELECT l.id, l.title, l.description, l.lesson_date AS date, l.class_id AS classId 
                 FROM lessons l
                 JOIN classes c ON l.class_id = c.id
                 JOIN teachers t ON c.teacher_id = t.id
                 WHERE t.email = ?
                 ORDER BY l.lesson_date DESC'
            );
            $stmt->execute([$email]);
            $rows = $stmt->fetchAll();
        } else {
            $rows = $pdo->query('SELECT id, title, description, lesson_date AS date, class_id AS classId FROM lessons ORDER BY lesson_date DESC')->fetchAll();
        }
    }

    $rows = array_map(fn($r) => [
        'id' => (string) $r['id'],
        'title' => $r['title'],
        'description' => $r['description'] ?? '',
        'date' => $r['date'],
        'classId' => (string) $r['classId'],
    ], $rows);

    jsonResponse($rows);
}

// ── CREATE ───────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    $title = trim($body['title'] ?? '');
    $description = trim($body['description'] ?? '');
    $date = $body['date'] ?? date('Y-m-d');
    $classId = (int) ($body['classId'] ?? 0);

    if (!$title || !$classId) {
        errorResponse('Title and classId are required');
    }

    // Permission check for teachers
    if ($role === 'teacher') {
        $st = $pdo->prepare('SELECT c.id FROM classes c JOIN teachers t ON c.teacher_id = t.id WHERE c.id = ? AND t.email = ?');
        $st->execute([$classId, $email]);
        if (!$st->fetch()) {
            errorResponse('You can only create lessons for your own classes', 403);
        }
    }

    $stmt = $pdo->prepare('INSERT INTO lessons (class_id, title, description, lesson_date) VALUES (?, ?, ?, ?)');
    $stmt->execute([$classId, $title, $description, $date]);
    $id = (int) $pdo->lastInsertId();

    jsonResponse([
        'id' => (string) $id,
        'title' => $title,
        'description' => $description,
        'date' => $date,
        'classId' => (string) $classId,
    ], 201);
}

// ── UPDATE ───────────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id)
        errorResponse('Missing lesson ID');

    $body = getBody();
    $title = trim($body['title'] ?? '');
    $description = trim($body['description'] ?? '');
    $date = $body['date'] ?? '';

    if (!$title || !$date) {
        errorResponse('Title and date are required');
    }

    // Permission check
    if ($role === 'teacher') {
        $st = $pdo->prepare('SELECT l.id FROM lessons l JOIN classes c ON l.class_id = c.id JOIN teachers t ON c.teacher_id = t.id WHERE l.id = ? AND t.email = ?');
        $st->execute([$id, $email]);
        if (!$st->fetch())
            errorResponse('Unauthorized', 403);
    }

    $stmt = $pdo->prepare('UPDATE lessons SET title = ?, description = ?, lesson_date = ? WHERE id = ?');
    $stmt->execute([$title, $description, $date, $id]);

    jsonResponse(['success' => true]);
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id)
        errorResponse('Missing lesson ID');

    // Permission check
    if ($role === 'teacher') {
        $st = $pdo->prepare('SELECT l.id FROM lessons l JOIN classes c ON l.class_id = c.id JOIN teachers t ON c.teacher_id = t.id WHERE l.id = ? AND t.email = ?');
        $st->execute([$id, $email]);
        if (!$st->fetch())
            errorResponse('Unauthorized', 403);
    }

    $pdo->prepare('DELETE FROM lessons WHERE id = ?')->execute([$id]);
    jsonResponse(['success' => true]);
}

errorResponse('Method not allowed', 405);
