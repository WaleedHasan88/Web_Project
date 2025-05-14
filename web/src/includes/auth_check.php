<?php
require_once __DIR__ . '/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: /web/src/public/portal.php");
    exit();
}

// Check if user has the required role (if specified)
if (isset($required_role)) {
    if ($required_role === 'admin' && !is_admin()) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: /web/src/public/index.php");
        exit();
    } elseif ($required_role === 'student' && !is_student()) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: /web/src/public/index.php");
        exit();
    }
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
?> 