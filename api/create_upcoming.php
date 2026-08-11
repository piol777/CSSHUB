<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$professor_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$event_type = $_POST['event_type'] ?? 'event';
$event_date = $_POST['event_date'] ?? '';
$event_time = trim($_POST['event_time'] ?? '');
$target_course_id = !empty($_POST['target_course_id']) ? (int)$_POST['target_course_id'] : null;
$target_year_level = !empty($_POST['target_year_level']) ? (int)$_POST['target_year_level'] : null;
$target_section_label = !empty($_POST['target_section_label']) ? trim($_POST['target_section_label']) : null;

$allowedTypes = ['class', 'live', 'exam', 'event'];
if (!in_array($event_type, $allowedTypes, true)) {
    $event_type = 'event';
}

if ($title === '' || $event_date === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title and date are required.']);
    exit;
}

if (strlen($title) > 200) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title is too long.']);
    exit;
}

$dateObj = DateTime::createFromFormat('Y-m-d', $event_date);
if (!$dateObj) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO upcoming_events (professor_id, title, event_type, event_date, event_time, target_course_id, target_year_level, target_section_label)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $professor_id,
        $title,
        $event_type,
        $event_date,
        $event_time !== '' ? $event_time : null,
        $target_course_id,
        $target_year_level,
        $target_section_label
    ]);

    echo json_encode(['success' => true, 'message' => 'Upcoming event posted.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save upcoming event.']);
}