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

// Get current semester and year
$current_month = date('n');
$current_year = date('Y');
$semester = ($current_month >= 1 && $current_month <= 5) ? 'Spring' : 
           (($current_month >= 6 && $current_month <= 8) ? 'Summer' : 'Fall');

// Get student's schedule
$stmt = $db->prepare("
    SELECT 
        c.course_code,
        c.course_name,
        c.instructor,
        s.day,
        s.start_time,
        s.end_time,
        s.room,
        e.status,
        e.grade
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    JOIN schedules s ON c.id = s.course_id
    WHERE e.student_id = ? 
    AND e.semester = ? 
    AND e.year = ?
    AND e.status = 'approved'
    ORDER BY 
        FIELD(s.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
        s.start_time
");
$stmt->bind_param("isi", $student_id, $semester, $current_year);
$stmt->execute();
$schedule = $stmt->get_result();

// Group schedule by day
$schedule_by_day = [];
while ($class = $schedule->fetch_assoc()) {
    $day = $class['day'];
    if (!isset($schedule_by_day[$day])) {
        $schedule_by_day[$day] = [];
    }
    $schedule_by_day[$day][] = $class;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - EPU</title>
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
                    <h1 class="h2">My Schedule</h1>
                    <div class="text-muted">
                        <?php echo $semester . ' ' . $current_year; ?>
                    </div>
                </div>

                <!-- Schedule Grid -->
                <div class="row">
                    <?php
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($days as $day):
                        $classes = $schedule_by_day[$day] ?? [];
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0 text-white"><?php echo $day; ?></h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($classes)): ?>
                                        <?php foreach ($classes as $class): ?>
                                            <div class="mb-3 pb-3 border-bottom">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($class['course_code']); ?></h6>
                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($class['course_name']); ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock me-1"></i>
                                                        <?php echo htmlspecialchars($class['start_time'] . ' - ' . $class['end_time']); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-geo-alt me-1"></i>
                                                        Room <?php echo htmlspecialchars($class['room']); ?>
                                                    </small>
                                                </div>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-person me-1"></i>
                                                        <?php echo htmlspecialchars($class['instructor']); ?>
                                                    </small>
                                                    <?php if ($class['grade']): ?>
                                                        <small class="text-muted ms-2">
                                                            <i class="bi bi-star me-1"></i>
                                                            Grade: <?php echo htmlspecialchars($class['grade']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No classes scheduled</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html> 