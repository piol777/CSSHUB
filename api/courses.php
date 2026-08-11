<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY name ASC")->fetchAll();
echo json_encode(['success' => true, 'courses' => $courses]);