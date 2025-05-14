<?php
// Session configuration - must be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();
require_once __DIR__ . '/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "All fields are required";
        redirect_to('portal.php');
    }

    try {
        $db = get_db_connection();

        // First check if it's an admin
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $role = 'admin';

        // If not an admin, check if it's a teacher
        if (!$user) {
            $stmt = $db->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $role = 'teacher';
        }

        // If not a teacher, check if it's a student
        if (!$user) {
            $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $role = 'student';
        }

        if ($user) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $role;
                $_SESSION['username'] = $username;

                // Set remember me cookie if requested
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (86400 * 30); // 30 days
                    
                    setcookie('remember_token', $token, $expiry, "/", "", true, true);
                    
                    // Store token in database
                    $table = $role === 'admin' ? 'admins' : ($role === 'teacher' ? 'teachers' : 'students');
                    $stmt = $db->prepare("UPDATE " . $table . " SET remember_token = ?, token_expiry = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $token, date('Y-m-d H:i:s', $expiry), $user['id']);
                    $stmt->execute();
                }

                // Log successful login
                error_log("Successful login - User: $username, Role: $role");

                // Redirect based on role
                if ($role === 'admin') {
                    redirect_to('../admin/dashboard.php');
                } elseif ($role === 'teacher') {
                    redirect_to('../teacher/teacher_dashboard.php');
                } else {
                    redirect_to('../student/dashboard.php');
                }
            } else {
                // Log failed login attempt with more details
                error_log("Failed login attempt - User: $username, Role: $role, Password hash: " . $user['password']);
                $_SESSION['error'] = "Invalid credentials";
                redirect_to('portal.php');
            }
        } else {
            error_log("User not found - Username: $username");
            $_SESSION['error'] = "User not found";
            redirect_to('portal.php');
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred. Please try again later.";
        redirect_to('portal.php');
    }
} else {
    // If not a POST request, redirect to login page
    redirect_to('portal.php');
}
?> 