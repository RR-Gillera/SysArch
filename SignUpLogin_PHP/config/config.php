<?php
// config/config.php

session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ccs_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('SITE_NAME', 'College of Computer Studies');
define('BASE_URL', '/SignUpLogin_PHP/public/');

// Session timeout (30 minutes)
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_lifetime', 1800);

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['IdNumber']) && !empty($_SESSION['IdNumber']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['Role']) && $_SESSION['Role'] === 'Admin';
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

// Helper function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Helper function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
