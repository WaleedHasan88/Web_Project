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

// Get student information
$student_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Get student's enrolled courses
$stmt = $db->prepare("
    SELECT c.id, c.course_code, c.course_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ? AND e.status = 'approved'
    ORDER BY c.course_code
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$courses = $stmt->get_result();

// Get selected course or default to first course
$selected_course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
if (!$selected_course_id && $courses->num_rows > 0) {
    $first_course = $courses->fetch_assoc();
    $selected_course_id = $first_course['id'];
    $courses->data_seek(0); // Reset pointer to beginning
}

// Get lecture files for the selected course
if ($selected_course_id) {
    $stmt = $db->prepare("
        SELECT lf.*, t.first_name, t.last_name
        FROM lecture_files lf
        JOIN teachers t ON lf.uploaded_by = t.id
        WHERE lf.course_id = ?
        ORDER BY lf.uploaded_at DESC
    ");
    $stmt->bind_param("i", $selected_course_id);
    $stmt->execute();
    $lecture_files = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Lectures - EPU</title>
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
                    <h1 class="h2">Course Lectures</h1>
                </div>

                <!-- Course Selection -->
                <div class="card mb-4 course-select">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="course_id" class="form-label">Select Course</label>
                                <select class="form-select" id="course_id" name="course_id" onchange="this.form.submit()">
                                    <?php while ($course = $courses->fetch_assoc()): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo $selected_course_id == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lecture Files List -->
                <?php if (isset($lecture_files) && $lecture_files->num_rows > 0): ?>
                    <div class="card lecture-card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-folder me-2"></i>Available Lecture Files
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>File Name</th>
                                            <th>Uploaded By</th>
                                            <th>Upload Date</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($file = $lecture_files->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <i class="bi bi-file-earmark me-2"></i>
                                                    <?php echo htmlspecialchars($file['title']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($file['description']); ?></td>
                                                <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                                <td><?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($file['uploaded_at'])); ?></td>
                                                <td class="text-end action-buttons">
                                                    <a href="../uploads/lectures/<?php echo $file['file_path']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       download="<?php echo $file['file_name']; ?>">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No lecture files available for this course yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 