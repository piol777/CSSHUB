<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('student');

$currentPage = 'profile';

$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, u.email, u.profile_picture,
           s.student_id_number, s.year_level, s.section_label, c.code AS course_code, c.name AS course_name
    FROM users u
    JOIN students s ON s.user_id = u.id
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$me = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/student_nav.php'; ?>

    <div class="profile-page-container">
        <div class="profile-card">
            <div class="profile-avatar-wrap">
                <div class="avatar-circle profile-avatar-large" id="profileAvatarPreview"
                    <?php if (!empty($me['profile_picture'])): ?>
                        style="background-image: url('../<?= sanitize($me['profile_picture']) ?>')"
                    <?php endif; ?>
                ></div>
            </div>
            <div class="profile-name"><?= sanitize($me['first_name'] . ' ' . $me['last_name']) ?></div>
            <div class="profile-role-badge">Student</div>

            <div class="profile-info-list">
                <div class="profile-info-row">
                    <span class="profile-info-label">Student ID</span>
                    <span class="profile-info-value"><?= sanitize($me['student_id_number']) ?></span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label">Course</span>
                    <span class="profile-info-value"><?= sanitize(($me['course_name'] ?? 'N/A') . ' (' . ($me['course_code'] ?? '-') . ')') ?></span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label">Year & Section</span>
                    <span class="profile-info-value"><?= sanitize($me['year_level'] . 'Y - ' . $me['section_label']) ?></span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-info-label">Email</span>
                    <span class="profile-info-value"><?= sanitize($me['email']) ?></span>
                </div>
            </div>

            <button class="profile-edit-btn" id="editProfileBtn">Edit Profile</button>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal-overlay" id="editProfileModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="modal-close-btn" id="closeEditProfileModal">&times;</button>
            </div>
            <div class="profile-edit-form">
                <div class="profile-avatar-wrap">
                    <div class="avatar-circle profile-avatar-large" id="editAvatarPreview"
                        <?php if (!empty($me['profile_picture'])): ?>
                            style="background-image: url('../<?= sanitize($me['profile_picture']) ?>')"
                        <?php endif; ?>
                    ></div>
                </div>
                <label class="profile-upload-label" for="profilePictureInput">Choose Photo</label>
                <input type="file" id="profilePictureInput" accept="image/*" hidden>
                <div class="modal-hint" id="profileUploadHint">JPG, PNG, GIF, or WEBP. Max 5MB.</div>
                <button class="profile-edit-btn" id="saveProfilePictureBtn" disabled>Save Photo</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/message_widget.js"></script>
    <script src="../assets/js/profile.js"></script>
    <script src="../assets/js/profile_card.js"></script>
</body>
</html>