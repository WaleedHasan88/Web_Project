<?php
// Session configuration - must be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();
require_once '../includes/config.php';
$db = get_db_connection();

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/portal.php");
    exit();
}

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

// Function to convert numeric grade to letter grade
function numericToLetter($numeric_grade) {
    global $grade_ranges;
    foreach ($grade_ranges as $letter => $range) {
        if ($numeric_grade >= $range['min'] && $numeric_grade <= $range['max']) {
            return $letter;
        }
    }
    return 'F';
}

// Function to convert letter grade to numeric range
function letterToNumeric($letter_grade) {
    global $grade_ranges;
    return $grade_ranges[$letter_grade] ?? null;
}

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enrollment_id']) && isset($_POST['grade'])) {
    $enrollment_id = (int)$_POST['enrollment_id'];
    $grade = strtoupper($_POST['grade']);
    $numeric_grade = (float)$_POST['numeric_grade'];
    
    // Validate grade
    if (in_array($grade, $valid_grades)) {
        $stmt = $db->prepare("UPDATE enrollments SET grade = ?, numeric_grade = ?, updated_by = ? WHERE id = ? AND status = 'approved'");
        $admin_id = 'A' . $_SESSION['user_id']; // Format: A123 for admin
        $stmt->bind_param("sdsi", $grade, $numeric_grade, $admin_id, $enrollment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Grade updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating grade.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid grade. Please enter a valid grade.";
    }
    
    // Preserve filters in redirect
    $redirect_url = "grades.php";
    if ($selected_course) $redirect_url .= "?course=" . $selected_course;
    if ($selected_teacher) $redirect_url .= ($selected_course ? "&" : "?") . "teacher=" . $selected_teacher;
    
    header("Location: " . $redirect_url);
    exit();
}

// Get current semester and year
$current_month = date('n');
$current_semester = ($current_month >= 1 && $current_month <= 6) ? 'Spring' : 'Fall';
$current_year = date('Y');

// Get all courses for filter
$courses_query = $db->query("SELECT id, course_code, course_name FROM courses ORDER BY course_code");
$courses = $courses_query->fetch_all(MYSQLI_ASSOC);

// Get all teachers for filter
$teachers_query = $db->query("SELECT id, full_name FROM teachers ORDER BY full_name");
$teachers = $teachers_query->fetch_all(MYSQLI_ASSOC);

// Get filter values
$selected_course = isset($_GET['course']) ? (int)$_GET['course'] : '';
$selected_teacher = isset($_GET['teacher']) ? (int)$_GET['teacher'] : '';

// Build the query with filters
$query = "
    SELECT 
        e.id as enrollment_id,
        e.grade,
        s.id as student_id,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.student_id as student_number,
        c.course_code,
        c.course_name,
        c.instructor,
        e.semester,
        e.year
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    WHERE e.status = 'approved'
";

$params = [];
$types = "";

if ($selected_course) {
    $query .= " AND c.id = ?";
    $params[] = $selected_course;
    $types .= "i";
}

if ($selected_teacher) {
    $query .= " AND c.instructor = (SELECT full_name FROM teachers WHERE id = ?)";
    $params[] = $selected_teacher;
    $types .= "i";
}

$query .= " ORDER BY e.year DESC, 
    CASE e.semester 
        WHEN 'Fall' THEN 2 
        WHEN 'Spring' THEN 1 
    END DESC,
    s.last_name ASC,
    s.first_name ASC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$enrollments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades - EPU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'Sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Grades</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="course" class="form-label">Course</label>
                                <select class="form-select" id="course" name="course">
                                    <option value="">All Courses</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo $selected_course == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="teacher" class="form-label">Teacher</label>
                                <select class="form-select" id="teacher" name="teacher">
                                    <option value="">All Teachers</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php echo $selected_teacher == $teacher['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                <a href="grades.php" class="btn btn-secondary">Clear Filters</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Teacher</th>
                                        <th>Semester</th>
                                        <th>Year</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($enrollments && $enrollments->num_rows > 0): ?>
                                        <?php while ($row = $enrollments->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['student_number']); ?></td>
                                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['course_code']); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($row['course_name']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['instructor']); ?></td>
                                                <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                                <td><?php echo htmlspecialchars($row['year']); ?></td>
                                                <td>
                                                    <form method="POST" class="grade-form">
                                                        <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                                        <input type="hidden" name="grade" id="grade_<?php echo $row['enrollment_id']; ?>" value="<?php echo htmlspecialchars($row['grade']); ?>">
                                                        <div class="input-group">
                                                            <input type="number" 
                                                                   class="form-control numeric-grade" 
                                                                   data-enrollment-id="<?php echo $row['enrollment_id']; ?>"
                                                                   value="<?php 
                                                                        if ($row['grade']) {
                                                                            // Get the stored numeric grade from the database if available
                                                                            $stmt = $db->prepare("SELECT numeric_grade FROM enrollments WHERE id = ?");
                                                                            $stmt->bind_param("i", $row['enrollment_id']);
                                                                            $stmt->execute();
                                                                            $result = $stmt->get_result();
                                                                            $grade_data = $result->fetch_assoc();
                                                                            echo $grade_data['numeric_grade'] ?? '';
                                                                        }
                                                                   ?>"
                                                                   min="0" 
                                                                   max="100" 
                                                                   step="0.1"
                                                                   required
                                                                   placeholder="Enter grade (0-100)">
                                                            <span class="input-group-text letter-grade" id="letter_<?php echo $row['enrollment_id']; ?>">
                                                                <?php echo htmlspecialchars($row['grade'] ?? ''); ?>
                                                            </span>
                                                            <input type="hidden" name="numeric_grade" id="numeric_grade_<?php echo $row['enrollment_id']; ?>" value="">
                                                            <button type="submit" class="btn btn-primary" id="submit_<?php echo $row['enrollment_id']; ?>" disabled>
                                                                <i class="bi bi-save"></i>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No approved enrollments found.</td>
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

    <!-- Grade History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Grade Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Letter Grade</th>
                                    <th>Numeric Range</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grade_ranges as $letter => $range): ?>
                                    <tr>
                                        <td><?php echo $letter; ?></td>
                                        <td><?php echo $range['min'] . ' - ' . $range['max']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
                const submitButton = document.getElementById('submit_' + enrollmentId);
                
                if (!isNaN(numericGrade) && numericGrade >= 0 && numericGrade <= 100) {
                    const letterGrade = numericToLetter(numericGrade);
                    letterGradeSpan.textContent = letterGrade;
                    gradeInput.value = letterGrade;
                    numericGradeInput.value = numericGrade;
                    submitButton.disabled = false;
                } else {
                    letterGradeSpan.textContent = '';
                    gradeInput.value = '';
                    numericGradeInput.value = '';
                    submitButton.disabled = true;
                }
            });

            // Trigger input event on page load if there's a value
            if (input.value) {
                input.dispatchEvent(new Event('input'));
            }
        });

        // Handle grade history modal
        document.querySelectorAll('.view-history').forEach(button => {
            button.addEventListener('click', function() {
                const student = this.dataset.student;
                const course = this.dataset.course;
                const grade = this.dataset.grade;
                
                // Get numeric range for the current grade
                const range = gradeRanges[grade] || { min: 'N/A', max: 'N/A' };
                
                document.getElementById('historyContent').innerHTML = `
                    <strong>Student:</strong> ${student}<br>
                    <strong>Course:</strong> ${course}<br>
                    <strong>Current Grade:</strong> ${grade}<br>
                    <strong>Numeric Range:</strong> ${range.min} - ${range.max}
                `;
            });
        });
    </script>
</body>
</html> 