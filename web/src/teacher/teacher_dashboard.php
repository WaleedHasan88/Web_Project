<?php
// Session configuration - must be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();
require_once '../includes/config.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../public/portal.php");
    exit();
}

$db = get_db_connection();
$teacher_id = $_SESSION['user_id'];

// Get teacher information
$stmt = $db->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Get total courses count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM courses WHERE instructor = ?");
$stmt->bind_param("s", $teacher['full_name']);
$stmt->execute();
$total_courses = $stmt->get_result()->fetch_assoc()['count'];

// Get total students count
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT e.student_id) as count 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    WHERE c.instructor = ? AND e.status = 'approved'
");
$stmt->bind_param("s", $teacher['full_name']);
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['count'];

// Get teacher and courses in a single query
$stmt = $db->prepare("
    SELECT t.*, c.*, COUNT(DISTINCT e.student_id) as enrolled_students
    FROM teachers t
    LEFT JOIN courses c ON c.instructor = t.full_name
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE t.id = ?
    GROUP BY c.id
    ORDER BY c.course_code
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses = $stmt->get_result();

// Get selected course details if course_id is provided
$selected_course = null;
$enrolled_students = null;
if (isset($_GET['course_id'])) {
    $stmt = $db->prepare("
        SELECT c.* 
        FROM courses c 
        WHERE c.id = ? AND c.instructor = (SELECT full_name FROM teachers WHERE id = ?)
    ");
    $stmt->bind_param("ii", $_GET['course_id'], $teacher_id);
    $stmt->execute();
    $selected_course = $stmt->get_result()->fetch_assoc();

    if ($selected_course) {
        // Get enrolled students for the selected course
        $stmt = $db->prepare("
            SELECT s.*, CONCAT(s.first_name, ' ', s.last_name) AS full_name
            FROM students s
            JOIN enrollments e ON s.id = e.student_id
            WHERE e.course_id = ?
            ORDER BY s.first_name, s.last_name
        ");
        $stmt->bind_param("i", $_GET['course_id']);
        $stmt->execute();
        $enrolled_students = $stmt->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - EPU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/teacher.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Welcome, <?php echo htmlspecialchars($teacher['first_name']); ?>!</h1>
                </div>

                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card dashboard-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title text-white">Total Courses</h5>
                                <p class="card-text display-6"><?php echo $total_courses; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card dashboard-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title text-white">Total Students</h5>
                                <p class="card-text display-6"><?php echo $total_students; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">My Courses</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($courses->num_rows > 0): ?>
                                    <div class="list-group">
                                        <?php while ($course = $courses->fetch_assoc()): ?>
                                            <a href="javascript:void(0)" 
                                               onclick="toggleCourseDetails(<?= $course['id'] ?>)"
                                               class="list-group-item list-group-item-action <?= (isset($_GET['course_id']) && $_GET['course_id'] == $course['id']) ? 'active' : '' ?>">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($course['course_code']) ?></h6>
                                                    <small><?= $course['enrolled_students'] ?> students</small>
                                                </div>
                                                <small><?= htmlspecialchars($course['course_name']) ?></small>
                                            </a>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>You haven't been assigned to any courses yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($selected_course): ?>
                <div class="row mt-4 course-details" id="courseDetails">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Course Details</h5>
                                <button class="btn btn-sm btn-outline-secondary" onclick="toggleCourseDetails()">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <h5><?= htmlspecialchars($selected_course['course_code']) ?></h5>
                                <h6 class="text-muted mb-3"><?= htmlspecialchars($selected_course['course_name']) ?></h6>
                                <p><strong>Instructor:</strong> <?= htmlspecialchars($selected_course['instructor']) ?></p>
                                <p><strong>Credits:</strong> <?= htmlspecialchars($selected_course['credits']) ?></p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Enrolled Students</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($enrolled_students && $enrolled_students->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Student ID</th>
                                                    <th>Full Name</th>
                                                    <th>Email</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($student = $enrolled_students->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                                                        <td><?= htmlspecialchars($student['email']) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-danger" 
                                                                    onclick="removeStudent(<?= $student['id'] ?>)">
                                                                <i class="bi bi-person-x me-1"></i> Remove
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No students are currently enrolled in this course.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
    function removeStudent(studentId) {
        if (confirm('Are you sure you want to remove this student from the course?')) {
            // Here you would typically make an AJAX call to remove the student
            // For now, we'll just show an alert
            alert('Student removal functionality will be implemented here');
        }
    }

    function toggleCourseDetails(courseId = null) {
        const courseDetails = document.getElementById('courseDetails');
        if (courseId) {
            // Show course details for the selected course
            window.location.href = `?course_id=${courseId}`;
        } else {
            // Hide course details
            courseDetails.classList.add('hidden');
            // Remove course_id from URL
            window.history.pushState({}, '', window.location.pathname);
        }
    }
    </script>
</body>
</html> 