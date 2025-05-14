<?php
// Session configuration - must be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();
require_once '../includes/config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../public/portal.php");
    exit();
}

$db = get_db_connection();

// Handle course enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']);
    $student_id = $_SESSION['user_id'];
    
    // Get current semester and year
    $current_month = date('n');
    $current_year = date('Y');
    $semester = ($current_month >= 1 && $current_month <= 5) ? 'Spring' : 
               (($current_month >= 6 && $current_month <= 8) ? 'Summer' : 'Fall');

    // Check if already enrolled or pending
    $stmt = $db->prepare("SELECT id, status FROM enrollments WHERE student_id = ? AND course_id = ? AND semester = ? AND year = ?");
    $stmt->bind_param("iisi", $student_id, $course_id, $semester, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $enrollment = $result->fetch_assoc();
        if ($enrollment['status'] === 'pending') {
            $_SESSION['error_message'] = "Your enrollment request is pending approval.";
        } else {
            $_SESSION['error_message'] = "You are already enrolled in this course.";
        }
    } else {
        // Start transaction
        $db->begin_transaction();
        
        try {
            // Create enrollment request with pending status
            $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id, semester, year, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iisi", $student_id, $course_id, $semester, $current_year);
            $stmt->execute();
            
            $_SESSION['success_message'] = "Enrollment request submitted. Waiting for admin approval.";
            
            // Commit transaction
            $db->commit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            $_SESSION['error_message'] = "Error submitting enrollment request: " . $e->getMessage();
        }
    }
    
    // Redirect to refresh the page
    header("Location: courses.php");
    exit();
}

// Get current semester and year
$current_month = date('n');
$current_year = date('Y');
$semester = ($current_month >= 1 && $current_month <= 5) ? 'Spring' : 
           (($current_month >= 6 && $current_month <= 8) ? 'Summer' : 'Fall');

// Get student's enrolled courses
$student_id = $_SESSION['user_id'];
$stmt = $db->prepare("
    SELECT c.*, s.day, s.start_time, s.end_time, s.room, e.grade, e.status
    FROM courses c 
    JOIN enrollments e ON c.id = e.course_id 
    LEFT JOIN schedules s ON c.id = s.course_id
    WHERE e.student_id = ? AND e.semester = ? AND e.year = ?
    ORDER BY c.course_code
");
$stmt->bind_param("isi", $student_id, $semester, $current_year);
$stmt->execute();
$enrolled_courses = $stmt->get_result();

// Get available courses (not enrolled or pending)
$stmt = $db->prepare("
    SELECT c.*, s.day, s.start_time, s.end_time, s.room
    FROM courses c
    LEFT JOIN schedules s ON c.id = s.course_id
    WHERE c.id NOT IN (
        SELECT course_id 
        FROM enrollments 
        WHERE student_id = ? AND semester = ? AND year = ? AND status != 'rejected'
    )
    ORDER BY c.course_code
");
$stmt->bind_param("isi", $student_id, $semester, $current_year);
$stmt->execute();
$available_courses = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - EPU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/student.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Courses</h1>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Enrolled Courses -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Enrolled Courses - <?php echo $semester . ' ' . $current_year; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover course-table">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Instructor</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($enrolled_courses->num_rows > 0): ?>
                                        <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
                                            <tr>
                                                <td class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></td>
                                                <td class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                <td class="instructor"><?php echo htmlspecialchars($course['instructor']); ?></td>
                                                <td class="schedule">
                                                    <?php
                                                    if ($course['day'] && $course['start_time'] && $course['end_time']) {
                                                        echo '<span class="day">' . htmlspecialchars($course['day']) . '</span> ';
                                                        echo '<span class="time">' . date('h:i A', strtotime($course['start_time'])) . ' - ' . 
                                                             date('h:i A', strtotime($course['end_time'])) . '</span>';
                                                        if ($course['room']) {
                                                            echo ' <span class="room">(' . htmlspecialchars($course['room']) . ')</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">TBA</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'pending' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger'
                                                    ][$course['status']];
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($course['status']); ?>
                                                    </span>
                                                </td>
                                               
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No courses enrolled for the current semester.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Available Courses -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Available Courses - <?php echo $semester . ' ' . $current_year; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover course-table">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Instructor</th>
                                        <th>Schedule</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($available_courses->num_rows > 0): ?>
                                        <?php while ($course = $available_courses->fetch_assoc()): ?>
                                            <tr>
                                                <td class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></td>
                                                <td class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                <td class="instructor"><?php echo htmlspecialchars($course['instructor']); ?></td>
                                                <td class="schedule">
                                                    <?php
                                                    if ($course['day'] && $course['start_time'] && $course['end_time']) {
                                                        echo '<span class="day">' . htmlspecialchars($course['day']) . '</span> ';
                                                        echo '<span class="time">' . date('h:i A', strtotime($course['start_time'])) . ' - ' . 
                                                             date('h:i A', strtotime($course['end_time'])) . '</span>';
                                                        if ($course['room']) {
                                                            echo ' <span class="room">(' . htmlspecialchars($course['room']) . ')</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">TBA</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="actions">
                                                    <form action="courses.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary enroll-btn">
                                                            <i class="bi bi-plus-circle me-1"></i> Enroll
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No available courses for the current semester.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 