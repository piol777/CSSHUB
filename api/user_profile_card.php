<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$targetUserId = (int)($_GET['user_id'] ?? 0);
if (!$targetUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, role, first_name, last_name, email, profile_picture FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$profile = [
    'id' => $user['id'],
    'role' => $user['role'],
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['email'],
    'profile_picture' => $user['profile_picture'],
];

if ($user['role'] === 'student') {
    $stmt = $pdo->prepare("
        SELECT s.student_id_number, s.year_level, s.section_label, c.code AS course_code, c.name AS course_name
        FROM students s LEFT JOIN courses c ON s.course_id = c.id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$targetUserId]);
    $extra = $stmt->fetch();
    $profile['student_id_number'] = $extra['student_id_number'] ?? null;
    $profile['course'] = $extra ? (($extra['course_name'] ?? 'N/A') . ' (' . ($extra['course_code'] ?? '-') . ')') : null;
    $profile['year_section'] = $extra ? ($extra['year_level'] . 'Y - ' . $extra['section_label']) : null;
} elseif ($user['role'] === 'professor') {
    $stmt = $pdo->prepare("SELECT department FROM professors WHERE user_id = ?");
    $stmt->execute([$targetUserId]);
    $extra = $stmt->fetch();
    $profile['department'] = $extra['department'] ?? null;
}

// Messaging only works between a student and a professor (current schema), and never with yourself
$viewerRole = $_SESSION['role'];
$viewerId = $_SESSION['user_id'];
$profile['can_message'] = $viewerId !== $targetUserId
    && (($viewerRole === 'student' && $user['role'] === 'professor')
        || ($viewerRole === 'professor' && $user['role'] === 'student'));

echo json_encode(['success' => true, 'profile' => $profile]);