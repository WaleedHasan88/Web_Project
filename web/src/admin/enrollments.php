<?php
// Session configuration - must be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/portal.php");
    exit();
}

$db = get_db_connection();

// Handle enrollment status updates and deletions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enrollment_id']) && isset($_POST['action'])) {
        $enrollment_id = intval($_POST['enrollment_id']);
        $action = $_POST['action'];
        
        if ($action === 'approve' || $action === 'reject') {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            
            $stmt = $db->prepare("UPDATE enrollments SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $enrollment_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Enrollment request " . $status . " successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating enrollment status: " . $stmt->error;
            }
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM enrollments WHERE id = ?");
            $stmt->bind_param("i", $enrollment_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Enrollment deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Error deleting enrollment: " . $stmt->error;
            }
        }
    } elseif (isset($_POST['add_enrollment'])) {
        $student_id = intval($_POST['student_id']);
        $course_id = intval($_POST['course_id']);
        $semester = $_POST['semester'];
        $year = intval($_POST['year']);
        $status = 'approved'; // Direct enrollment is automatically approved
        
        // Check for duplicate enrollment
        $check_stmt = $db->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ? AND semester = ? AND year = ?");
        $check_stmt->bind_param("iiss", $student_id, $course_id, $semester, $year);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "This student is already enrolled in this course for the selected semester and year.";
        } else {
            $stmt = $db->prepare("INSERT INTO enrollments (student_id, course_id, semester, year, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisis", $student_id, $course_id, $semester, $year, $status);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Student enrolled successfully.";
            } else {
                $_SESSION['error_message'] = "Error enrolling student: " . $stmt->error;
            }
        }
    }
    
    header("Location: enrollments.php");
    exit();
}

// Get current semester and year
$current_month = date('n');
$current_year = date('Y');
$semester = ($current_month >= 1 && $current_month <= 5) ? 'Spring' : 
           (($current_month >= 6 && $current_month <= 8) ? 'Summer' : 'Fall');

// Get all students for the add enrollment form
$students = $db->query("SELECT id, first_name, last_name, student_id FROM students ORDER BY first_name, last_name");

// Get all courses for the add enrollment form
$courses = $db->query("SELECT id, course_code, course_name FROM courses ORDER BY course_code");

// Get enrollment requests
$stmt = $db->prepare("
    SELECT e.id, e.student_id, e.course_id, e.semester, e.year, e.status,
           s.first_name, s.last_name, s.student_id as student_number,
           c.course_code, c.course_name, c.instructor
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    WHERE e.semester = ? AND e.year = ?
    ORDER BY e.status = 'pending' DESC, e.created_at DESC
");
$stmt->bind_param("si", $semester, $current_year);
$stmt->execute();
$enrollments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Enrollments - EPU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Enrollments</h1>
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

                <!-- Add New Enrollment Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Add New Enrollment</h5>
                    </div>
                    <div class="card-body">
                        <form action="enrollments.php" method="POST" class="row g-3">
                            <div class="col-md-4">
                                <label for="student_id" class="form-label">Student</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php while ($student = $students->fetch_assoc()): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="course_id" class="form-label">Course</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php while ($course = $courses->fetch_assoc()): ?>
                                        <option value="<?php echo $course['id']; ?>">
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="Spring">Spring</option>
                                    <option value="Summer">Summer</option>
                                    <option value="Fall">Fall</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="year" class="form-label">Year</label>
                                <input type="number" class="form-control" id="year" name="year" value="<?php echo $current_year; ?>" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_enrollment" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add Enrollment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Enrollment List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Enrollment Requests - <?php echo $semester . ' ' . $current_year; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Instructor</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($enrollments->num_rows > 0): ?>
                                        <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($enrollment['student_number']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($enrollment['course_code']); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($enrollment['course_name']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($enrollment['instructor']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'pending' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger'
                                                    ][$enrollment['status']];
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($enrollment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($enrollment['status'] === 'pending'): ?>
                                                        <form action="enrollments.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['id']; ?>">
                                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                                                <i class="bi bi-check-circle"></i> Approve
                                                            </button>
                                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-x-circle"></i> Reject
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form action="enrollments.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['id']; ?>">
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this enrollment?')">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No enrollment requests found.</td>
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