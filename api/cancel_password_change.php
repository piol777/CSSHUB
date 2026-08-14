<?php
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['pending_password_hash'])) {
    unset($_SESSION['pending_password_hash']);
}

redirect('/cdsgahub/professor/change_password.php');