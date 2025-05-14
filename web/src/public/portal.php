<?php
// Session configuration - must be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();

// Handle logout
if (isset($_GET['logout'])) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: portal.php");
    exit();
}

require_once '../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: ../student/dashboard.php");
    } elseif ($_SESSION['role'] === 'teacher') {
      header("Location: ../teacher/teacher_dashboard.php");
    }
    exit();
}

// Get error message if exists
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']); // Clear the error message after displaying
?>

<section class="portal-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card portal-card" data-aos="fade-up">
          <div class="card-body">
            <h2 class="text-center">Portal</h2>
            <?php if ($error): ?>
              <div class="error-message text-center"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="../includes/login.php" method="POST" id="loginForm">
              <div class="mb-3" data-aos="fade-up" data-aos-delay="100">
                <label for="username" class="form-label">ID</label>
                <input type="text" class="form-control" id="username" name="username" required 
                       pattern="[A-Za-z0-9]+" title="Only letters and numbers are allowed" />
              </div>
              <div class="mb-3" data-aos="fade-up" data-aos-delay="200">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required 
                       minlength="6" title="Password must be at least 6 characters long" />
              </div>
              <div class="mb-3 form-check" data-aos="fade-up" data-aos-delay="300">
                <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me" />
                <label class="form-check-label" for="rememberMe">Remember me</label>
              </div>
              <button type="submit" class="btn btn-primary w-100" data-aos="fade-up" data-aos-delay="400">
                Login
              </button>
            </form>
            <div class="text-center mt-3" data-aos="fade-up" data-aos-delay="600">
              <a href="forgot_password.php">Forgot Password?</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


<script>
  // Form validation
  document.getElementById('loginForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
      e.preventDefault();
      alert('Please fill in all fields');
      return;
    }
  });
</script>
