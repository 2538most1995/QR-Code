<?php
// config/auth.php
require_once __DIR__ . '/database.php';

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: admin/login.php");
        exit;
    }
}

function getLoggedInUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['admin_id'] ?? 1,
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'fullname' => $_SESSION['admin_fullname'] ?? 'ผู้ดูแลระบบ',
        'role' => $_SESSION['admin_role'] ?? 'ผู้ดูแลระบบ',
        'avatar' => $_SESSION['admin_avatar'] ?? 'assets/images/default-avatar.png'
    ];
}
