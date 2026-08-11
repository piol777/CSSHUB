<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$meStmt = $pdo->prepare("SELECT course_id, year_level, section_label FROM students WHERE user_id = ?");
$meStmt->execute([$_SESSION['user_id']]);
$me = $meStmt->fetch();

if (!$me) {
    echo json_encode(['success' => true, 'events' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, title, event_type, event_date, event_time
    FROM upcoming_events
    WHERE event_date >= CURDATE()
      AND (target_course_id IS NULL OR target_course_id = ?)
      AND (target_year_level IS NULL OR target_year_level = ?)
      AND (target_section_label IS NULL OR target_section_label = ?)
    ORDER BY event_date ASC, event_time ASC
    LIMIT 5
");
$stmt->execute([$me['course_id'], $me['year_level'], $me['section_label']]);
$events = $stmt->fetchAll();

echo json_encode(['success' => true, 'events' => $events]);