<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

function userBelongsToConversation(PDO $pdo, int $conversation_id, int $user_id): bool {
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND (student_id = ? OR professor_id = ?)");
    $stmt->execute([$conversation_id, $user_id, $user_id]);
    return (bool)$stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conversation_id = (int)($_GET['conversation_id'] ?? 0);

    if (!$conversation_id || !userBelongsToConversation($pdo, $conversation_id, $user_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.content, m.attachment_path, m.attachment_type, m.attachment_name, m.created_at,
               u.profile_picture AS sender_profile_picture
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll();

    // Mark messages from the other person as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?");
    $stmt->execute([$conversation_id, $user_id]);

    // Attach reaction summaries
    if (!empty($messages)) {
        $ids = array_column($messages, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rStmt = $pdo->prepare("SELECT message_id, user_id, reaction FROM message_reactions WHERE message_id IN ($placeholders)");
        $rStmt->execute($ids);
        $allReactions = $rStmt->fetchAll();

        $reactionsByMessage = [];
        foreach ($allReactions as $r) {
            $mid = $r['message_id'];
            if (!isset($reactionsByMessage[$mid])) {
                $reactionsByMessage[$mid] = ['counts' => [], 'my_reaction' => null];
            }
            $reactionsByMessage[$mid]['counts'][$r['reaction']] = ($reactionsByMessage[$mid]['counts'][$r['reaction']] ?? 0) + 1;
            if ((int)$r['user_id'] === $user_id) {
                $reactionsByMessage[$mid]['my_reaction'] = $r['reaction'];
            }
        }

        foreach ($messages as &$m) {
            $m['reactions'] = $reactionsByMessage[$m['id']] ?? ['counts' => new stdClass(), 'my_reaction' => null];
        }
        unset($m);
    }

    echo json_encode(['success' => true, 'messages' => $messages, 'current_user_id' => $user_id]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conversation_id = (int)($_POST['conversation_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if (!$conversation_id || !userBelongsToConversation($pdo, $conversation_id, $user_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    if (strlen($content) > 2000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is too long']);
        exit;
    }

    // ===== Attachment upload (image or file) =====
    $attachment_path = null;
    $attachment_type = null;
    $attachment_name = null;

    if (!empty($_FILES['attachment']['name'])) {
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedFileTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'application/zip'
        ];
        $maxFileSize = 10 * 1024 * 1024; // 10MB

        if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'File upload failed.']);
            exit;
        }

        if ($_FILES['attachment']['size'] > $maxFileSize) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'File must be under 10MB.']);
            exit;
        }

        $fileType = $_FILES['attachment']['type'];
        $isImage = in_array($fileType, $allowedImageTypes, true);
        $isFile = in_array($fileType, $allowedFileTypes, true);

        if (!$isImage && !$isFile) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'This file type is not allowed.']);
            exit;
        }

        $uploadDir = __DIR__ . '/../assets/uploads/messages/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $safeName = 'msg_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $destination = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
            $attachment_path = 'assets/uploads/messages/' . $safeName;
            $attachment_type = $isImage ? 'image' : 'file';
            $attachment_name = $_FILES['attachment']['name'];
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded file.']);
            exit;
        }
    }

    if ($content === '' && !$attachment_path) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, content, attachment_path, attachment_type, attachment_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $conversation_id,
        $user_id,
        $content !== '' ? $content : null,
        $attachment_path,
        $attachment_type,
        $attachment_name
    ]);

    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $pdo->lastInsertId(),
            'sender_id' => $user_id,
            'content' => $content !== '' ? $content : null,
            'attachment_path' => $attachment_path,
            'attachment_type' => $attachment_type,
            'attachment_name' => $attachment_name,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false]);