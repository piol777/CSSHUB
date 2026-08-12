<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('professor');

$currentPage = 'profile';

$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, u.email, u.profile_picture, p.department
    FROM users u
    JOIN professors p ON p.user_id = u.id
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

    <?php include __DIR__ . '/../includes/professor_nav.php'; ?>

    <div class="profile-page-container">
        <div class="profile-card">
            <div class="profile-avatar-wrap">
                <div class="avatar-circle profile-avatar-large" id="profileAvatarPreview"
                    <?php if (!empty($me['profile_picture'])): ?>
                        style="background-image: url('../<?= sanitize($me['profile_picture']) ?>')"
                    <?php endif; ?>
                ></div>
            </div>
            <div class="profile-name">Prof. <?= sanitize($me['first_name'] . ' ' . $me['last_name']) ?></div>
            <div class="profile-role-badge">Professor</div>

            <div class="profile-info-list">
                <div class="profile-info-row">
                    <span class="profile-info-label">Department</span>
                    <span class="profile-info-value"><?= sanitize($me['department'] ?? 'N/A') ?></span>
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
    <script src="../assets/js/create_post.js"></script>
    <script src="../assets/js/message_widget.js"></script>
    <script src="../assets/js/profile.js"></script>
    <script src="../assets/js/profile_card.js"></script>
</body>
</html>