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

// Get student's grades
$student_id = $_SESSION['user_id'];
$stmt = $db->prepare("
    SELECT c.course_code, c.course_name, c.credits, e.grade, e.semester, e.year
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ? AND e.grade IS NOT NULL
    ORDER BY e.year DESC, 
    CASE e.semester 
        WHEN 'Fall' THEN 1 
        WHEN 'Summer' THEN 2 
        WHEN 'Spring' THEN 3 
    END DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades = $stmt->get_result();

// Calculate GPA
$total_credits = 0;
$total_grade_points = 0;

$grade_points = [
    'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
    'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
    'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
    'D+' => 1.3, 'D' => 1.0, 'D-' => 0.7,
    'F' => 0.0
];

while ($grade = $grades->fetch_assoc()) {
    if (isset($grade_points[$grade['grade']])) {
        $total_credits += $grade['credits'];
        $total_grade_points += ($grade_points[$grade['grade']] * $grade['credits']);
    }
}

$gpa = $total_credits > 0 ? round($total_grade_points / $total_credits, 2) : 0.00;
$grades->data_seek(0); // Reset result pointer
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - EPU</title>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
                    <h1 class="h2">My Grades</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print Transcript
                            </button>
                        </div>
                    </div>
                </div>

                <!-- GPA Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title">Current GPA</h5>
                                <p class="display-4 mb-0 gpa-value"><?php echo number_format($gpa, 2); ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-0 credits-info">Total Credits: <?php echo $total_credits; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grades Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Academic Record</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover grades-table">
                                <thead>
                                    <tr>
                                        <th>Semester</th>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Credits</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($grades->num_rows > 0): ?>
                                        <?php while ($grade = $grades->fetch_assoc()): ?>
                                            <tr>
                                                <td class="semester"><?php echo htmlspecialchars($grade['semester'] . ' ' . $grade['year']); ?></td>
                                                <td class="course-code"><?php echo htmlspecialchars($grade['course_code']); ?></td>
                                                <td class="course-name"><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                                <td class="credits"><?php echo htmlspecialchars($grade['credits']); ?></td>
                                                <?php
                                                    $grade_value = htmlspecialchars($grade['grade']);
                                                    $grade_class = gradeToClass($grade['grade']);
                                                ?>
                                                <td class="grade <?php echo $grade_class; ?>"><?php echo $grade_value; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No grades available yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Grade Legend (visible only when printing) -->
                <div class="grade-legend">
                    <h4>Grade Scale</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Grade</th>
                                        <th>Range</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="grade grade-Aplus">A+</td>
                                        <td>97-100</td>
                                        <td>Excellent</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-A">A</td>
                                        <td>93-96</td>
                                        <td>Excellent</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-Aminus">A-</td>
                                        <td>90-92</td>
                                        <td>Excellent</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-Bplus">B+</td>
                                        <td>87-89</td>
                                        <td>Very Good</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-B">B</td>
                                        <td>83-86</td>
                                        <td>Very Good</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-Bminus">B-</td>
                                        <td>80-82</td>
                                        <td>Very Good</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Grade</th>
                                        <th>Range</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="grade grade-Cplus">C+</td>
                                        <td>77-79</td>
                                        <td>Good</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-C">C</td>
                                        <td>73-76</td>
                                        <td>Good</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-Cminus">C-</td>
                                        <td>70-72</td>
                                        <td>Good</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-Dplus">D+</td>
                                        <td>67-69</td>
                                        <td>Pass</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-D">D</td>
                                        <td>63-66</td>
                                        <td>Pass</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-Dminus">D-</td>
                                        <td>60-62</td>
                                        <td>Pass</td>
                                    </tr>
                                    <tr>
                                        <td class="grade grade-F">F</td>
                                        <td>0-59</td>
                                        <td>Fail</td>
                                    </tr>
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

<?php
function gradeToClass($grade) {
    switch ($grade) {
        case 'A+': return 'grade-Aplus';
        case 'A-': return 'grade-Aminus';
        case 'B+': return 'grade-Bplus';
        case 'B-': return 'grade-Bminus';
        case 'C+': return 'grade-Cplus';
        case 'C-': return 'grade-Cminus';
        case 'D+': return 'grade-Dplus';
        case 'D-': return 'grade-Dminus';
        default: return 'grade-' . $grade;
    }
}
?> 