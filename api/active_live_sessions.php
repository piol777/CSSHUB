<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

if ($_SESSION['role'] === 'student') {
    // Students only see live sessions they are actually eligible to join.
    // NULL on a session's course_id/year_level/section_label means "everyone" for that field.
    $meStmt = $pdo->prepare("SELECT course_id, year_level, section_label FROM students WHERE user_id = ?");
    $meStmt->execute([$_SESSION['user_id']]);
    $me = $meStmt->fetch();

    if (!$me) {
        echo json_encode(['success' => true, 'sessions' => []]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT ls.id, ls.room_id, ls.section_label, ls.year_level, ls.professor_id,
               u.first_name, u.last_name, c.code AS course_code
        FROM live_sessions ls
        JOIN users u ON ls.professor_id = u.id
        LEFT JOIN courses c ON ls.course_id = c.id
        WHERE ls.status = 'live'
          AND (ls.course_id IS NULL OR ls.course_id = ?)
          AND (ls.year_level IS NULL OR ls.year_level = ?)
          AND (ls.section_label IS NULL OR ls.section_label = ?)
        ORDER BY ls.started_at DESC
    ");
    $stmt->execute([$me['course_id'], $me['year_level'], $me['section_label']]);
} else {
    // Professors (and admins) see every live session, since they may need to manage/monitor all rooms.
    $stmt = $pdo->query("
        SELECT ls.id, ls.room_id, ls.section_label, ls.year_level, ls.professor_id,
               u.first_name, u.last_name, c.code AS course_code
        FROM live_sessions ls
        JOIN users u ON ls.professor_id = u.id
        LEFT JOIN courses c ON ls.course_id = c.id
        WHERE ls.status = 'live'
        ORDER BY ls.started_at DESC
    ");
}

$sessions = $stmt->fetchAll();

echo json_encode(['success' => true, 'sessions' => $sessions]);