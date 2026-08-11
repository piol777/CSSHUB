<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$professor_id = $_SESSION['user_id'];
$course_id = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
$year_level = !empty($_POST['year_level']) ? (int)$_POST['year_level'] : null;
$section_label = !empty($_POST['section_label']) ? trim($_POST['section_label']) : null;

// End any previous active session by this professor first
$stmt = $pdo->prepare("UPDATE live_sessions SET status = 'ended', ended_at = NOW() WHERE professor_id = ? AND status = 'live'");
$stmt->execute([$professor_id]);

$room_id = 'room_' . $professor_id . '_' . time();

$stmt = $pdo->prepare("
    INSERT INTO live_sessions (professor_id, room_id, course_id, year_level, section_label)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$professor_id, $room_id, $course_id, $year_level, $section_label]);

// Notify only the students who match this room's targeting (NULL = everyone in that field)
$profStmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$profStmt->execute([$professor_id]);
$prof = $profStmt->fetch();
$profFullName = $prof['first_name'] . ' ' . $prof['last_name'];

$targetSql = "SELECT user_id FROM students WHERE 1=1";
$targetParams = [];
if ($course_id) { $targetSql .= " AND course_id = ?"; $targetParams[] = $course_id; }
if ($year_level) { $targetSql .= " AND year_level = ?"; $targetParams[] = $year_level; }
if ($section_label) { $targetSql .= " AND section_label = ?"; $targetParams[] = $section_label; }

$targetStmt = $pdo->prepare($targetSql);
$targetStmt->execute($targetParams);
$targetStudents = $targetStmt->fetchAll();

$notifStmt = $pdo->prepare("
    INSERT INTO notifications (user_id, type, actor_id, message)
    VALUES (?, 'live_started', ?, ?)
");
foreach ($targetStudents as $student) {
    $notifStmt->execute([
        $student['user_id'],
        $professor_id,
        $profFullName . ' started a live class'
    ]);
}

echo json_encode(['success' => true, 'room_id' => $room_id]);