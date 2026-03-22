<?php
// backend/api/enrollments/index.php

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
$role = $_SESSION['user_role'];
$uid = $_SESSION['user_id'];

// ── LIST ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($role === 'student') {
        // Students see their own requests
        $stmt = $pdo->prepare(
            'SELECT er.id, er.class_id AS classId, er.status, er.created_at AS createdAt,
                    c.name AS className, c.section AS classSection
             FROM enrollment_requests er
             JOIN classes c ON er.class_id = c.id
             WHERE er.user_id = ?
             ORDER BY er.created_at DESC'
        );
        $stmt->execute([$uid]);
    } elseif ($role === 'teacher') {
        // Teachers see requests for their classes
        $stmt = $pdo->prepare(
            'SELECT er.id, er.user_id AS userId, er.class_id AS classId, er.status, er.created_at AS createdAt,
                    c.name AS className, c.section AS classSection, u.name AS userName, u.email AS userEmail
             FROM enrollment_requests er
             JOIN classes c ON er.class_id = c.id
             JOIN users u ON er.user_id = u.id
             WHERE c.teacher_id = (SELECT id FROM teachers WHERE email = ?)
             ORDER BY er.created_at DESC'
        );
        $stmt->execute([$_SESSION['user_email']]);
    } else {
        // Admins see all
        $stmt = $pdo->query(
            'SELECT er.id, er.user_id AS userId, er.class_id AS classId, er.status, er.created_at AS createdAt,
                    c.name AS className, c.section AS classSection, u.name AS userName, u.email AS userEmail
             FROM enrollment_requests er
             JOIN classes c ON er.class_id = c.id
             JOIN users u ON er.user_id = u.id
             ORDER BY er.created_at DESC'
        );
    }

    $rows = $stmt->fetchAll();
    jsonResponse($rows);
}

// ── CREATE (Student Request) ─────────────────────────────────────────────────
if ($method === 'POST') {
    if ($role !== 'student')
        errorResponse('Only students can request enrollment', 403);

    $body = getBody();
    $classId = (int) ($body['classId'] ?? 0);

    if (!$classId)
        errorResponse('classId is required');

    $stmt = $pdo->prepare('INSERT INTO enrollment_requests (user_id, class_id) VALUES (?, ?)');
    try {
        $stmt->execute([$uid, $classId]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000)
            errorResponse('Request already exists', 409);
        throw $e;
    }

    jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()], 201);
}

// ── UPDATE (Approve/Reject) ──────────────────────────────────────────────────
if ($method === 'PUT') {
    if ($role === 'student')
        errorResponse('Unauthorized', 403);

    $id = (int) ($_GET['id'] ?? 0);
    $body = getBody();
    $status = $body['status'] ?? '';

    if (!$id || !in_array($status, ['approved', 'rejected'])) {
        errorResponse('Invalid id or status');
    }

    $pdo->beginTransaction();

    // 1. Update request status
    $upd = $pdo->prepare('UPDATE enrollment_requests SET status = ? WHERE id = ?');
    $upd->execute([$status, $id]);

    // 2. If approved, add student to the students table
    if ($status === 'approved') {
        $req = $pdo->prepare('SELECT user_id, class_id FROM enrollment_requests WHERE id = ?');
        $req->execute([$id]);
        $r = $req->fetch();

        if ($r) {
            $userSt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
            $userSt->execute([$r['user_id']]);
            $u = $userSt->fetch();

            if ($u) {
                // Check if student already exists
                $stCheck = $pdo->prepare('SELECT id FROM students WHERE email = ?');
                $stCheck->execute([$u['email']]);
                $existing = $stCheck->fetch();

                if ($existing) {
                    $pdo->prepare('UPDATE students SET class_id = ? WHERE id = ?')
                        ->execute([$r['class_id'], $existing['id']]);
                } else {
                    $pdo->prepare('INSERT INTO students (name, email, class_id) VALUES (?, ?, ?)')
                        ->execute([$u['name'], $u['email'], $r['class_id']]);
                }
            }
        }
    }

    $pdo->commit();
    jsonResponse(['success' => true]);
}

errorResponse('Method not allowed', 405);
