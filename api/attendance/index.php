<?php
// backend/api/attendance/index.php

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
        'SELECT id, student_id AS studentId, class_id AS classId, date, status
         FROM attendance
         ORDER BY date DESC, id DESC'
    )->fetchAll();

    $rows = array_map(fn($r) => [
        'id' => (string) $r['id'],
        'studentId' => (string) $r['studentId'],
        'classId' => (string) $r['classId'],
        'date' => $r['date'],
        'status' => $r['status'],
    ], $rows);

    jsonResponse($rows);
}

// ── SUBMIT BATCH ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = getBody();
    $records = $body['records'] ?? [];

    if (!is_array($records) || count($records) === 0) {
        errorResponse('records array is required');
    }

    $inserted = [];
    $stmt = $pdo->prepare(
        'INSERT INTO attendance (student_id, class_id, date, status)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE status = VALUES(status), id = LAST_INSERT_ID(id)'
    );

    foreach ($records as $rec) {
        $studentId = (int) ($rec['studentId'] ?? 0);
        $classId = (int) ($rec['classId'] ?? 0);
        $date = $rec['date'] ?? '';
        $status = $rec['status'] ?? '';

        if (!$studentId || !$classId || !$date || !in_array($status, ['present', 'absent', 'late'])) {
            continue;
        }

        $stmt->execute([$studentId, $classId, $date, $status]);
        $id = (int) $pdo->lastInsertId();

        $inserted[] = [
            'id' => (string) $id,
            'studentId' => (string) $studentId,
            'classId' => (string) $classId,
            'date' => $date,
            'status' => $status,
        ];
    }

    jsonResponse(['inserted' => $inserted, 'count' => count($inserted)], 201);
}

errorResponse('Method not allowed', 405);
