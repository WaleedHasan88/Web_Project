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

// Define valid grades and their numeric ranges
$grade_ranges = [
    'A+' => ['min' => 97, 'max' => 100],
    'A' => ['min' => 93, 'max' => 96],
    'A-' => ['min' => 90, 'max' => 92],
    'B+' => ['min' => 87, 'max' => 89],
    'B' => ['min' => 83, 'max' => 86],
    'B-' => ['min' => 80, 'max' => 82],
    'C+' => ['min' => 77, 'max' => 79],
    'C' => ['min' => 73, 'max' => 76],
    'C-' => ['min' => 70, 'max' => 72],
    'D+' => ['min' => 67, 'max' => 69],
    'D' => ['min' => 63, 'max' => 66],
    'D-' => ['min' => 60, 'max' => 62],
    'F' => ['min' => 0, 'max' => 59]
];

$valid_grades = array_keys($grade_ranges);

// Handle grade updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grade'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $grade = strtoupper($_POST['grade']);
    $numeric_grade = $_POST['numeric_grade'];
    
    // Validate grade
    if (in_array($grade, $valid_grades)) {
        $update_stmt = $db->prepare("UPDATE enrollments SET grade = ?, numeric_grade = ?, updated_by = ? WHERE id = ?");
        $teacher_id = 'T' . $_SESSION['user_id']; // Format: T123 for teacher
        $update_stmt->bind_param("sdsi", $grade, $numeric_grade, $teacher_id, $enrollment_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Grade updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update grade.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid grade. Please enter a valid grade.";
    }
    
    header("Location: student_management.php" . ($_GET['student_id'] ? "?student_id=" . $_GET['student_id'] : ""));
    exit();
}

// Get teacher information
$teacher_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Get all students enrolled in teacher's courses
$stmt = $db->prepare("
    SELECT DISTINCT
        s.*,
        GROUP_CONCAT(DISTINCT c.course_code) as enrolled_courses,
        COUNT(DISTINCT c.id) as total_courses
    FROM students s
    JOIN enrollments e ON s.id = e.student_id
    JOIN courses c ON e.course_id = c.id
    WHERE c.instructor = ?
    GROUP BY s.id
    ORDER BY s.last_name, s.first_name
");
$stmt->bind_param("s", $teacher['full_name']);
$stmt->execute();
$students = $stmt->get_result();

// Get selected student details if student_id is provided
$selected_student = null;
$enrolled_courses = [];
$grade_history = null;
$gpa = 0;

if (isset($_GET['student_id'])) {
    // Get student information
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $_GET['student_id']);
    $stmt->execute();
    $selected_student = $stmt->get_result()->fetch_assoc();

    if ($selected_student) {
        // Get student's enrolled courses with enrollment IDs
        $stmt = $db->prepare("
            SELECT 
                e.id as enrollment_id,
                c.id as course_id,
                c.course_code,
                c.course_name,
                e.grade,
                e.numeric_grade,
                e.status
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.student_id = ? AND c.instructor = ?
            ORDER BY c.course_code
        ");
        $stmt->bind_param("is", $_GET['student_id'], $teacher['full_name']);
        $stmt->execute();
        $enrolled_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get student's grade history
        $stmt = $db->prepare("
            SELECT 
                c.course_code,
                c.course_name,
                e.grade,
                e.status,
                e.semester,
                e.year,
                e.created_at as enrollment_date
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.student_id = ? AND c.instructor = ?
            ORDER BY e.year DESC, 
                CASE e.semester 
                    WHEN 'Fall' THEN 2 
                    WHEN 'Spring' THEN 1 
                END DESC,
                c.course_code ASC
        ");
        $stmt->bind_param("is", $_GET['student_id'], $teacher['full_name']);
        $stmt->execute();
        $grade_history = $stmt->get_result();

        // Calculate GPA
        $total_points = 0;
        $total_courses = 0;
        $grade_points = [
            'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
            'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
            'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
            'D+' => 1.3, 'D' => 1.0, 'D-' => 0.7,
            'F' => 0.0
        ];

        while ($course = $grade_history->fetch_assoc()) {
            if ($course['grade'] && $course['status'] === 'approved') {
                $total_points += $grade_points[$course['grade']] ?? 0;
                $total_courses++;
            }
        }
        $gpa = $total_courses > 0 ? round($total_points / $total_courses, 2) : 0;

        // Reset pointer to beginning
        $grade_history->data_seek(0);
    }
}

// Add this after the session_start() and before the HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_students'])) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_grades_' . date('Y-m-d') . '.csv"');
    
    // Create file pointer for output
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add CSV headers
    fputcsv($output, ['Student ID', 'First Name', 'Last Name', 'Email', 'Course Code', 'Course Name', 'Grade', 'Status']);
    
    // Get all students with their grades
    $stmt = $db->prepare("
        SELECT 
            s.student_id,
            s.first_name,
            s.last_name,
            s.email,
            c.course_code,
            c.course_name,
            e.numeric_grade,
            e.status,
            e.id as enrollment_id
        FROM students s
        JOIN enrollments e ON s.id = e.student_id
        JOIN courses c ON e.course_id = c.id
        WHERE c.instructor = ?
        ORDER BY s.last_name, s.first_name, c.course_code
    ");
    $stmt->bind_param("s", $teacher['full_name']);
    $stmt->execute();
    $results = $stmt->get_result();
    
    // Write student data
    while ($row = $results->fetch_assoc()) {
        fputcsv($output, [
            $row['student_id'],
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['course_code'],
            $row['course_name'],
            $row['numeric_grade'] ?? '',
            $row['status']
        ]);
    }
    
    fclose($output);
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['student_file'])) {
    $file = $_FILES['student_file'];
    $allowed_types = ['text/csv', 'application/vnd.ms-excel'];
    
    if (in_array($file['type'], $allowed_types)) {
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
            // Skip the header row
            fgetcsv($handle);
            
            $success_count = 0;
            $error_count = 0;
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 8) { // Ensure we have all required fields
                    $student_id = $data[0];
                    $course_code = $data[4];
                    $numeric_grade = $data[6];
                    
                    // Convert numeric grade to letter grade
                    $letter_grade = '';
                    if ($numeric_grade !== '') {
                        $numeric_grade = floatval($numeric_grade);
                        if ($numeric_grade >= 97) $letter_grade = 'A+';
                        elseif ($numeric_grade >= 93) $letter_grade = 'A';
                        elseif ($numeric_grade >= 90) $letter_grade = 'A-';
                        elseif ($numeric_grade >= 87) $letter_grade = 'B+';
                        elseif ($numeric_grade >= 83) $letter_grade = 'B';
                        elseif ($numeric_grade >= 80) $letter_grade = 'B-';
                        elseif ($numeric_grade >= 77) $letter_grade = 'C+';
                        elseif ($numeric_grade >= 73) $letter_grade = 'C';
                        elseif ($numeric_grade >= 70) $letter_grade = 'C-';
                        elseif ($numeric_grade >= 67) $letter_grade = 'D+';
                        elseif ($numeric_grade >= 63) $letter_grade = 'D';
                        elseif ($numeric_grade >= 60) $letter_grade = 'D-';
                        else $letter_grade = 'F';
                    }
                    
                    // Get student ID from student_id
                    $stmt = $db->prepare("SELECT id FROM students WHERE student_id = ?");
                    $stmt->bind_param("s", $student_id);
                    $stmt->execute();
                    $student_result = $stmt->get_result();
                    $student = $student_result->fetch_assoc();
                    
                    if ($student) {
                        // Get course ID from course_code
                        $stmt = $db->prepare("SELECT id FROM courses WHERE course_code = ? AND instructor = ?");
                        $stmt->bind_param("ss", $course_code, $teacher['full_name']);
                        $stmt->execute();
                        $course_result = $stmt->get_result();
                        $course = $course_result->fetch_assoc();
                        
                        if ($course) {
                            // Get enrollment ID
                            $stmt = $db->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
                            $stmt->bind_param("ii", $student['id'], $course['id']);
                            $stmt->execute();
                            $enrollment_result = $stmt->get_result();
                            $enrollment = $enrollment_result->fetch_assoc();
                            
                            if ($enrollment) {
                                // Update grades
                                $stmt = $db->prepare("UPDATE enrollments SET numeric_grade = ?, grade = ?, updated_by = ? WHERE id = ?");
                                $teacher_id = 'T' . $_SESSION['user_id'];
                                $stmt->bind_param("dssi", $numeric_grade, $letter_grade, $teacher_id, $enrollment['id']);
                                if ($stmt->execute()) {
                                    $success_count++;
                                } else {
                                    $error_count++;
                                }
                            } else {
                                $error_count++;
                            }
                        } else {
                            $error_count++;
                        }
                    } else {
                        $error_count++;
                    }
                }
            }
            fclose($handle);
            
            $_SESSION['success_message'] = "Import completed: $success_count grades updated successfully, $error_count errors.";
        } else {
            $_SESSION['error_message'] = "Error reading the file.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid file type. Please upload a CSV file.";
    }
    
    header("Location: student_management.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - EPU</title>
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
                    <h1 class="h2">Student Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="input-group search-box me-2">
                            <input type="text" class="form-control" id="studentSearch" placeholder="Search students...">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                        </div>
                        <div class="btn-group me-2">
                            <form method="POST" class="d-inline">
                                <button type="submit" name="export_students" class="btn btn-success">
                                    <i class="bi bi-download me-1"></i> Export
                                </button>
                            </form>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-upload me-1"></i> Import
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total Students</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $students->num_rows; ?></h2>
                                    </div>
                                    <div class="icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Active Courses</h6>
                                        <h2 class="mt-2 mb-0"><?php 
                                            $active_courses = 0;
                                            foreach ($enrolled_courses as $course) {
                                                if ($course['status'] === 'active' || $course['status'] === 'approved') {
                                                    $active_courses++;
                                                }
                                            }
                                            echo $active_courses;
                                        ?></h2>
                                    </div>
                                    <div class="icon">
                                        <i class="bi bi-book-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students List -->
                <div class="row">
                    <?php if ($students->num_rows > 0): ?>
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4 student-card-container">
                                <div class="card student-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($student['student_id']); ?></p>
                                            </div>
                                            <span class="badge bg-primary"><?php echo $student['total_courses']; ?> Courses</span>
                                        </div>
                                        <p class="card-text">
                                            <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($student['email']); ?>
                                        </p>
                                        <div class="mb-3">
                                            <small class="text-muted">Enrolled Courses:</small>
                                            <div class="mt-1">
                                                <?php 
                                                $courses = explode(',', $student['enrolled_courses']);
                                                foreach ($courses as $course) {
                                                    echo '<span class="badge bg-light text-dark me-1 mb-1">' . htmlspecialchars($course) . '</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <button onclick="showStudentDetails(<?php echo $student['id']; ?>)" 
                                                class="btn btn-primary w-100">
                                            <i class="bi bi-eye me-1"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No students found.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($selected_student): ?>
                <!-- Student Details -->
                <div class="row mt-4 student-details" id="studentDetails">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Student Details</h3>
                            <button class="btn btn-outline-secondary" onclick="hideStudentDetails()">
                                <i class="bi bi-x-lg me-1"></i> Close
                            </button>
                        </div>

                        <!-- Student Information and Courses -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0 text-white">
                                        <?php echo htmlspecialchars($selected_student['first_name'] . ' ' . $selected_student['last_name']); ?>
                                        <small class="ms-2">(<?php echo htmlspecialchars($selected_student['student_id']); ?>)</small>
                                    </h5>
                                    <span class="badge bg-light text-dark">GPA: <?php echo $gpa; ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-1"><i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($selected_student['email']); ?></p>
                                    </div>
                               
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Course</th>
                                                <th>Grade</th>
                                                <th>Status</th>
                                                <th>Semester</th>
                                                <th>Year</th>
                                                <th>Enrollment Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($grade_history && $grade_history->num_rows > 0): ?>
                                                <?php while ($course = $grade_history->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($course['course_code']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($course['course_name']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            // Find the current enrollment for this course
                                                            $current_enrollment = null;
                                                            foreach ($enrolled_courses as $enrollment) {
                                                                if ($enrollment['course_code'] === $course['course_code']) {
                                                                    $current_enrollment = $enrollment;
                                                                    break;
                                                                }
                                                            }
                                                            
                                                            if ($current_enrollment): ?>
                                                                <form method="POST" class="d-flex align-items-center">
                                                                    <input type="hidden" name="enrollment_id" value="<?php echo $current_enrollment['enrollment_id']; ?>">
                                                                    <div class="input-group">
                                                                        <input type="number" 
                                                                               class="form-control form-control-sm grade-input numeric-grade" 
                                                                               data-enrollment-id="<?php echo $current_enrollment['enrollment_id']; ?>"
                                                                               value="<?php echo $current_enrollment['numeric_grade'] ?? ''; ?>"
                                                                               min="0" 
                                                                               max="100" 
                                                                               step="0.1"
                                                                               placeholder="0-100">
                                                                        <span class="input-group-text grade-badge letter-grade" id="letter_<?php echo $current_enrollment['enrollment_id']; ?>">
                                                                            <?php echo htmlspecialchars($current_enrollment['grade'] ?? ''); ?>
                                                                        </span>
                                                                        <input type="hidden" name="grade" id="grade_<?php echo $current_enrollment['enrollment_id']; ?>" value="<?php echo htmlspecialchars($current_enrollment['grade'] ?? ''); ?>">
                                                                        <input type="hidden" name="numeric_grade" id="numeric_grade_<?php echo $current_enrollment['enrollment_id']; ?>" value="<?php echo $current_enrollment['numeric_grade'] ?? ''; ?>">
                                                                        <button type="submit" name="update_grade" class="btn btn-sm btn-primary">
                                                                            <i class="bi bi-check-lg"></i>
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            <?php else: ?>
                                                                <?php if ($course['grade']): ?>
                                                                    <span class="badge bg-<?php 
                                                                        echo $course['grade'][0] === 'A' ? 'success' : 
                                                                            ($course['grade'][0] === 'B' ? 'info' : 
                                                                            ($course['grade'][0] === 'C' ? 'primary' : 
                                                                            ($course['grade'][0] === 'D' ? 'warning' : 'danger'))); 
                                                                    ?>">
                                                                        <?php echo htmlspecialchars($course['grade']); ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">Not Graded</span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $course['status'] === 'approved' ? 'success' : 
                                                                    ($course['status'] === 'pending' ? 'warning' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst(htmlspecialchars($course['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($course['semester']); ?></td>
                                                        <td><?php echo htmlspecialchars($course['year']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No course history found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="student_file" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="student_file" name="student_file" accept=".csv" required>
                            <div class="form-text">
                                File should contain: Student ID, First Name, Last Name, Email, Course Code, Course Name, Grade (0-100), Status
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Download the template file to see the correct format.
                            <a href="templates/student_import_template.csv" class="alert-link" download>Download Template</a>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Note: Only grades will be updated. Student information will not be modified.
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Import Grades</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
    function showStudentDetails(studentId) {
        window.location.href = `?student_id=${studentId}`;
    }

    function hideStudentDetails() {
        // Remove student_id from the URL and reload
        const url = new URL(window.location.href);
        url.searchParams.delete('student_id');
        window.location.href = url.pathname + url.search;
    }

    // Grade ranges for conversion
    const gradeRanges = <?php echo json_encode($grade_ranges); ?>;

    // Function to convert numeric grade to letter grade
    function numericToLetter(numericGrade) {
        for (const [letter, range] of Object.entries(gradeRanges)) {
            if (numericGrade >= range.min && numericGrade <= range.max) {
                return letter;
            }
        }
        return 'F';
    }

    // Add event listeners for numeric grade inputs
    document.querySelectorAll('.numeric-grade').forEach(input => {
        input.addEventListener('input', function() {
            const enrollmentId = this.dataset.enrollmentId;
            const numericGrade = parseFloat(this.value);
            const letterGradeSpan = document.getElementById('letter_' + enrollmentId);
            const gradeInput = document.getElementById('grade_' + enrollmentId);
            const numericGradeInput = document.getElementById('numeric_grade_' + enrollmentId);
            
            if (!isNaN(numericGrade) && numericGrade >= 0 && numericGrade <= 100) {
                const letterGrade = numericToLetter(numericGrade);
                letterGradeSpan.textContent = letterGrade;
                gradeInput.value = letterGrade;
                numericGradeInput.value = numericGrade;
            } else {
                letterGradeSpan.textContent = '';
                gradeInput.value = '';
                numericGradeInput.value = '';
            }
        });
    });

    // Student search functionality
    document.getElementById('studentSearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const studentCards = document.querySelectorAll('.student-card-container');
        
        studentCards.forEach(card => {
            const studentName = card.querySelector('.card-title').textContent.toLowerCase();
            const studentId = card.querySelector('.text-muted').textContent.toLowerCase();
            const studentEmail = card.querySelector('.card-text').textContent.toLowerCase();
            
            if (studentName.includes(searchTerm) || 
                studentId.includes(searchTerm) || 
                studentEmail.includes(searchTerm)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });

    function deleteStudent(studentId) {
        if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
            window.location.href = `delete_student.php?student_id=${studentId}`;
        }
    }
    </script>
</body>
</html> 