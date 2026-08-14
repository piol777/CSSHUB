<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['role'];

if ($role === 'professor') {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.content
        FROM announcements a
        WHERE a.professor_id = ? AND a.is_pinned = 1
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt2 = $pdo->prepare("SELECT course_id, year_level, section_label FROM students WHERE user_id = ?");
    $stmt2->execute([$_SESSION['user_id']]);
    $me = $stmt2->fetch();

    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.content, u.first_name, u.last_name
        FROM announcements a
        JOIN users u ON a.professor_id = u.id
        WHERE a.is_pinned = 1
          AND (a.target_course_id IS NULL OR a.target_course_id = ?)
          AND (a.target_year_level IS NULL OR a.target_year_level = ?)
          AND (a.target_section_label IS NULL OR a.target_section_label = ?)
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$me['course_id'] ?? 0, $me['year_level'] ?? 0, $me['section_label'] ?? '']);
}

echo json_encode(['success' => true, 'posts' => $stmt->fetchAll()]);