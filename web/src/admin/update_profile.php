<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get form data
$admin_id = $_SESSION['user_id'];
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$current_password = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
$new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

// Connect to database
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]);
    exit();
}

// Check if username already exists (excluding current user)
$stmt = $db->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
$stmt->bind_param("si", $username, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Username already exists'
    ]);
    exit();
}
$stmt->close();

// Check if email already exists (excluding current user)
$stmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Email already exists'
    ]);
    exit();
}
$stmt->close();

// If changing password, verify current password and validate new password
if (!empty($new_password)) {
    if (empty($current_password)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Current password is required to change password'
        ]);
        exit();
    }

    if ($new_password !== $confirm_password) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'New passwords do not match'
        ]);
        exit();
    }

    // Verify current password
    $stmt = $db->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($current_password, $admin['password'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Current password is incorrect'
        ]);
        exit();
    }

    // Update with new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE admins SET username = ?, email = ?, password = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $hashed_password, $admin_id);
} else {
    // Update without changing password
    $stmt = $db->prepare("UPDATE admins SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $email, $admin_id);
}

if ($stmt->execute()) {
    // Update session username if it was changed
    $_SESSION['username'] = $username;
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Profile updated successfully',
        'data' => [
            'username' => $username,
            'email' => $email
        ]
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error updating profile: ' . $stmt->error
    ]);
}

$stmt->close();
$db->close();
?> 