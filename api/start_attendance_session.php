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
$class_id = (int)($_POST['class_id'] ?? 0);
$session_date = trim($_POST['session_date'] ?? date('Y-m-d'));

// Verify ownership + get the class's course/year/section
$stmt = $pdo->prepare("SELECT * FROM class_sections WHERE id = ? AND professor_id = ?");
$stmt->execute([$class_id, $professor_id]);
$class = $stmt->fetch();

if (!$class) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Class not found.']);
    exit;
}

// Get or create today's session (one session per class per date)
$stmt = $pdo->prepare("SELECT id FROM attendance_sessions WHERE class_section_id = ? AND session_date = ?");
$stmt->execute([$class_id, $session_date]);
$session_id = $stmt->fetchColumn();

if (!$session_id) {
    $stmt = $pdo->prepare("INSERT INTO attendance_sessions (class_section_id, session_date) VALUES (?, ?)");
    $stmt->execute([$class_id, $session_date]);
    $session_id = $pdo->lastInsertId();
}

// Get enrolled students (same course/year/section as this class)
$stmt = $pdo->prepare("
    SELECT s.user_id, u.first_name, u.last_name
    FROM students s
    JOIN users u ON u.id = s.user_id
    WHERE s.course_id = ? AND s.year_level = ? AND s.section_label = ?
    ORDER BY u.last_name ASC
");
$stmt->execute([$class['course_id'], $class['year_level'], $class['section_label']]);
$students = $stmt->fetchAll();

// Seed default 'present' records for students who don't have one yet for this session
$seedStmt = $pdo->prepare("INSERT IGNORE INTO attendance_records (session_id, student_id, status) VALUES (?, ?, 'present')");
foreach ($students as $s) {
    $seedStmt->execute([$session_id, $s['user_id']]);
}

// Fetch final statuses
$statusStmt = $pdo->prepare("SELECT student_id, status FROM attendance_records WHERE session_id = ?");
$statusStmt->execute([$session_id]);
$statusMap = [];
foreach ($statusStmt->fetchAll() as $row) {
    $statusMap[$row['student_id']] = $row['status'];
}

$result = array_map(function ($s) use ($statusMap) {
    return [
        'student_id' => (int)$s['user_id'],
        'name' => $s['first_name'] . ' ' . $s['last_name'],
        'status' => $statusMap[$s['user_id']] ?? 'present',
    ];
}, $students);

echo json_encode(['success' => true, 'session_id' => (int)$session_id, 'session_date' => $session_date, 'students' => $result]);