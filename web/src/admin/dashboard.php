<?php
// Session configuration - must be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();
require_once '../includes/config.php';
$db = get_db_connection();

// Get total students count
$numberofsutdnet = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) AS count FROM students"));

// Get total courses count
$total_courses = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) AS count FROM courses"));

// Get total teachers count
$total_teachers = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) AS count FROM teachers"));

// Get recent activities
$recent_activities = $db->query("
    SELECT 
        e.created_at as date,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        c.course_code,
        c.course_name,
        e.status
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY e.created_at DESC
    LIMIT 5
");

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/portal.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EPU</title>
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
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Dashboard Cards -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <p class="card-text display-6"><?php echo $numberofsutdnet['count']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Active Courses</h5>
                                <p class="card-text display-6"><?php echo $total_courses['count']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Teachers</h5>
                                <p class="card-text display-6"><?php echo $total_teachers['count']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Student</th>
                                                <th>Course</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                                                <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo date('Y-m-d H:i', strtotime($activity['date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($activity['student_name']); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($activity['course_code']); ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($activity['course_name']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_class = [
                                                                'pending' => 'warning',
                                                                'approved' => 'success',
                                                                'rejected' => 'danger'
                                                            ][$activity['status']];
                                                            ?>
                                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                                <?php echo ucfirst($activity['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No recent activities found.</td>
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
        </div>
    </div>
</body>
</html> 