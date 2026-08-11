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

$stmt = $pdo->prepare("
    SELECT
        cs.id, cs.subject_name, cs.section_label, cs.year_level, cs.semester_label, cs.color_hex,
        c.code AS course_code,

        (SELECT COUNT(*) FROM students s
            WHERE s.course_id = cs.course_id AND s.year_level = cs.year_level AND s.section_label = cs.section_label
        ) AS students_count,

        (SELECT COUNT(DISTINCT a.id) FROM assignments a
            WHERE a.class_section_id = cs.id
            AND EXISTS (
                SELECT 1 FROM students s2
                WHERE s2.course_id = cs.course_id AND s2.year_level = cs.year_level AND s2.section_label = cs.section_label
                AND NOT EXISTS (
                    SELECT 1 FROM assignment_submissions sub
                    WHERE sub.assignment_id = a.id AND sub.student_id = s2.user_id AND sub.status = 'graded'
                )
            )
        ) AS assignments_pending,

        (SELECT ROUND(AVG(sub.grade), 0) FROM assignment_submissions sub
            JOIN assignments a2 ON a2.id = sub.assignment_id
            WHERE a2.class_section_id = cs.id AND sub.status = 'graded'
        ) AS grade_average,

        (SELECT ROUND(AVG(CASE WHEN ar.status = 'present' THEN 100 ELSE 0 END), 0)
            FROM attendance_records ar
            JOIN attendance_sessions ats ON ats.id = ar.session_id
            WHERE ats.class_section_id = cs.id
        ) AS attendance_pct

    FROM class_sections cs
    JOIN courses c ON c.id = cs.course_id
    WHERE cs.professor_id = ?
    ORDER BY cs.created_at DESC
");
$stmt->execute([$professor_id]);
$classes = $stmt->fetchAll();

echo json_encode(['success' => true, 'classes' => $classes]);