<?php
session_start();

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function redirect(string $path): void {
    header("Location: $path");
    exit;
}

function guard_any_role(array $allowedRoles): void {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        redirect('/cdsgahub/auth/login.php');
    }

    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        redirect('/cdsgahub/' . $_SESSION['role'] . '/dashboard.php');
    }
}

function generate_random_password(int $length = 10): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function get_daily_verse(): array {
    $verses = [
        ['text' => "I can do all things through Christ which strengtheneth me.", 'reference' => "Philippians 4:13"],
        ['text' => "The LORD is my shepherd; I shall not want.", 'reference' => "Psalm 23:1"],
        ['text' => "This is the day which the LORD hath made; we will rejoice and be glad in it.", 'reference' => "Psalm 118:24"],
        ['text' => "Trust in the LORD with all thine heart; and lean not unto thine own understanding.", 'reference' => "Proverbs 3:5"],
        ['text' => "In all thy ways acknowledge him, and he shall direct thy paths.", 'reference' => "Proverbs 3:6"],
        ['text' => "Let all your things be done with charity.", 'reference' => "1 Corinthians 16:14"],
        ['text' => "Be strong and of a good courage; fear not, nor be afraid.", 'reference' => "Deuteronomy 31:6"],
        ['text' => "With God all things are possible.", 'reference' => "Matthew 19:26"],
        ['text' => "The LORD is my light and my salvation; whom shall I fear?", 'reference' => "Psalm 27:1"],
        ['text' => "Commit thy works unto the LORD, and thy thoughts shall be established.", 'reference' => "Proverbs 16:3"],
        ['text' => "Be not overcome of evil, but overcome evil with good.", 'reference' => "Romans 12:21"],
        ['text' => "Rejoice evermore.", 'reference' => "1 Thessalonians 5:16"],
        ['text' => "Pray without ceasing.", 'reference' => "1 Thessalonians 5:17"],
        ['text' => "In every thing give thanks: for this is the will of God in Christ Jesus concerning you.", 'reference' => "1 Thessalonians 5:18"],
        ['text' => "For God hath not given us the spirit of fear; but of power, and of love, and of a sound mind.", 'reference' => "2 Timothy 1:7"],
        ['text' => "The fear of the LORD is the beginning of wisdom.", 'reference' => "Proverbs 9:10"],
        ['text' => "A merry heart doeth good like a medicine.", 'reference' => "Proverbs 17:22"],
        ['text' => "Cast thy burden upon the LORD, and he shall sustain thee.", 'reference' => "Psalm 55:22"],
        ['text' => "The LORD is good, a strong hold in the day of trouble; and he knoweth them that trust in him.", 'reference' => "Nahum 1:7"],
        ['text' => "Let your light so shine before men, that they may see your good works, and glorify your Father which is in heaven.", 'reference' => "Matthew 5:16"],
    ];

    $index = (int) date('z') % count($verses);
    return $verses[$index];
}

function get_attachment_meta(string $path): array {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    $labels = [
        'pdf'  => ['label' => 'PDF', 'color' => '#e5484d'],
        'doc'  => ['label' => 'DOC', 'color' => '#2952ff'],
        'docx' => ['label' => 'DOC', 'color' => '#2952ff'],
        'ppt'  => ['label' => 'PPT', 'color' => '#e8730f'],
        'pptx' => ['label' => 'PPT', 'color' => '#e8730f'],
        'xls'  => ['label' => 'XLS', 'color' => '#2f9e44'],
        'xlsx' => ['label' => 'XLS', 'color' => '#2f9e44'],
        'txt'  => ['label' => 'TXT', 'color' => '#6b6885'],
        'csv'  => ['label' => 'CSV', 'color' => '#2f9e44'],
        'zip'  => ['label' => 'ZIP', 'color' => '#6b6885'],
        'rar'  => ['label' => 'RAR', 'color' => '#6b6885'],
        'rtf'  => ['label' => 'RTF', 'color' => '#6b6885'],
        'odt'  => ['label' => 'ODT', 'color' => '#2952ff'],
    ];

    $meta = $labels[$ext] ?? ['label' => 'FILE', 'color' => '#6b6885'];

    $fullPath = __DIR__ . '/../' . $path;
    $sizeBytes = is_file($fullPath) ? filesize($fullPath) : 0;
    $sizeLabel = $sizeBytes >= 1024 * 1024
        ? round($sizeBytes / (1024 * 1024), 1) . ' MB'
        : max(1, round($sizeBytes / 1024)) . ' KB';

    return [
        'ext'   => $ext,
        'label' => $meta['label'],
        'color' => $meta['color'],
        'size'  => $sizeLabel,
        'filename' => basename($path),
    ];
}

function generate_next_student_id(PDO $pdo): string {
    $year = date('Y');

    $stmt = $pdo->prepare("
        SELECT student_id_number
        FROM students
        WHERE student_id_number LIKE ?
        ORDER BY student_id_number DESC
        LIMIT 1
    ");
    $stmt->execute([$year . '____']); // exactly YEAR + 4 digits

    $last = $stmt->fetchColumn();

    $next = 1;
    if ($last && preg_match('/^' . $year . '(\d{4})$/', $last, $m)) {
        $next = ((int)$m[1]) + 1;
    }

    return $year . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

function is_student_restricted($pdo, $studentId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_warnings WHERE student_id = ?");
    $stmt->execute([$studentId]);
    return (int)$stmt->fetchColumn() >= 3;
}