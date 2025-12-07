<?php
session_start();
require_once __DIR__ . '/../sb_base.php';

// check if user is already log in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login/login.php');
    exit;
}

$userType = $_SESSION['user_type'] ?? 'user';

// link to specific role profile page
if ($userType === 'admin') {
    header('Location: /page/admin_profile.php');
} else {
    header('Location: /page/user_profile.php');
}
exit;
