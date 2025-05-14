<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error display for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Session configuration - must be set before any session_start()
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    ini_set('session.cookie_lifetime', 0); // Until browser closes
    
    session_start();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'epu_portal');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty

// Site configuration
define('SITE_NAME', 'Erbil Polytechnic University');
define('SITE_URL', 'http://localhost/web/src/public');

// Utility functions
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validate_email($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_student() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function is_teacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

function redirect_to($path) {
    header("Location: " . SITE_URL . "/" . $path);
    exit();
}

function get_db_connection() {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        error_log("Database connection failed: " . $db->connect_error);
        die("Connection failed: Please try again later.");
    }
    
    $db->set_charset("utf8mb4");
    return $db;
}
?> 