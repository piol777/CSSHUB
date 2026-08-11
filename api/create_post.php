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
$content = trim($_POST['content'] ?? '');
$target_course_id = !empty($_POST['target_course_id']) ? (int)$_POST['target_course_id'] : null;
$target_year_level = !empty($_POST['target_year_level']) ? (int)$_POST['target_year_level'] : null;
$target_section_label = !empty($_POST['target_section_label']) ? trim($_POST['target_section_label']) : null;

if ($title === '' || $content === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title and content are required.']);
    exit;
}

if (strlen($title) > 200) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title is too long.']);
    exit;
}

// ===== Image upload validation (max 2 images) =====
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB per image
$uploadedPaths = [];

if (!empty($_FILES['images']['name'][0])) {
    $fileCount = count($_FILES['images']['name']);

    if ($fileCount > 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You can upload a maximum of 2 images.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../assets/images/uploads/posts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

        $fileType = $_FILES['images']['type'][$i];
        $fileSize = $_FILES['images']['size'][$i];
        $tmpName = $_FILES['images']['tmp_name'][$i];

        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, and WEBP images are allowed.']);
            exit;
        }

        if ($fileSize > $maxFileSize) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Each image must be under 5MB.']);
            exit;
        }

        $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
        $safeName = 'post_' . $professor_id . '_' . time() . '_' . $i . '.' . strtolower($ext);
        $destination = $uploadDir . $safeName;

        if (move_uploaded_file($tmpName, $destination)) {
            $uploadedPaths[] = 'assets/images/uploads/posts/' . $safeName;
        }
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO announcements (professor_id, title, content, target_course_id, target_year_level, target_section_label)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$professor_id, $title, $content, $target_course_id, $target_year_level, $target_section_label]);
    $announcement_id = $pdo->lastInsertId();

    // Save uploaded image paths
    if (!empty($uploadedPaths)) {
        $imgStmt = $pdo->prepare("INSERT INTO announcement_images (announcement_id, image_path, display_order) VALUES (?, ?, ?)");
        foreach ($uploadedPaths as $order => $path) {
            $imgStmt->execute([$announcement_id, $path, $order]);
        }
    }

    // Notify all matching students
    $sql = "
        SELECT s.user_id
        FROM students s
        WHERE (? IS NULL OR s.course_id = ?)
          AND (? IS NULL OR s.year_level = ?)
          AND (? IS NULL OR s.section_label = ?)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $target_course_id, $target_course_id,
        $target_year_level, $target_year_level,
        $target_section_label, $target_section_label
    ]);
    $matchingStudents = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$professor_id]);
    $prof = $stmt->fetch();
    $message = 'Prof. ' . $prof['first_name'] . ' ' . $prof['last_name'] . ' posted a new announcement';

    $notifStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, actor_id, announcement_id, message)
        VALUES (?, 'new_announcement', ?, ?, ?)
    ");
    foreach ($matchingStudents as $student) {
        $notifStmt->execute([$student['user_id'], $professor_id, $announcement_id, $message]);
    }

    echo json_encode(['success' => true, 'message' => 'Announcement posted successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create post.']);
}