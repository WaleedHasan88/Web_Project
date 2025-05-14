<?php
require_once __DIR__ . '/config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/', '', true, true);
}

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time()-3600, '/', '', true, true);
    
    // Clear remember token from database if user was logged in
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        try {
            $pdo = get_db_connection();
            $table = $_SESSION['role'] === 'admin' ? 'admins' : 'students';
            $stmt = $pdo->prepare("UPDATE $table SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Error clearing remember token: " . $e->getMessage());
        }
    }
}

// Log the logout
if (isset($_SESSION['username'])) {
    error_log("User logged out - Username: " . $_SESSION['username']);
}

// Destroy the session
session_destroy();

// Redirect to login page
redirect_to('portal.php');
?> 