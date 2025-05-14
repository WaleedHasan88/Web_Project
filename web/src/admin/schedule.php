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

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add Schedule
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $course_id = intval($_POST['course_id']);
        $day = trim($_POST['day']);
        $start_time = trim($_POST['start_time']);
        $end_time = trim($_POST['end_time']);
        $room = trim($_POST['room']);

        // Check for schedule conflicts
        $stmt = $db->prepare("
            SELECT id FROM schedules 
            WHERE day = ? AND room = ? AND 
            ((start_time <= ? AND end_time > ?) OR 
             (start_time < ? AND end_time >= ?) OR 
             (start_time >= ? AND end_time <= ?))
        ");
        $stmt->bind_param("ssssssss", $day, $room, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt = $db->prepare("INSERT INTO schedules (course_id, day, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $course_id, $day, $start_time, $end_time, $room);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Schedule added successfully.";
            } else {
                $_SESSION['error_message'] = "Error adding schedule: " . $stmt->error;
            }
        } else {
            $_SESSION['error_message'] = "Schedule conflict: The room is already booked for this time slot.";
        }
    }
    // Handle Update Schedule
    else if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = intval($_POST['id']);
        $course_id = intval($_POST['course_id']);
        $day = trim($_POST['day']);
        $start_time = trim($_POST['start_time']);
        $end_time = trim($_POST['end_time']);
        $room = trim($_POST['room']);

        // Check for schedule conflicts (excluding current schedule)
        $stmt = $db->prepare("
            SELECT id FROM schedules 
            WHERE day = ? AND room = ? AND id != ? AND
            ((start_time <= ? AND end_time > ?) OR 
             (start_time < ? AND end_time >= ?) OR 
             (start_time >= ? AND end_time <= ?))
        ");
        $stmt->bind_param("ssissssss", $day, $room, $id, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt = $db->prepare("UPDATE schedules SET course_id = ?, day = ?, start_time = ?, end_time = ?, room = ? WHERE id = ?");
            $stmt->bind_param("issssi", $course_id, $day, $start_time, $end_time, $room, $id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Schedule updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating schedule: " . $stmt->error;
            }
        } else {
            $_SESSION['error_message'] = "Schedule conflict: The room is already booked for this time slot.";
        }
    }
    // Handle Delete Schedule
    else if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $schedule_id = intval($_POST['schedule_id']);
        
        $stmt = $db->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Schedule deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting schedule: " . $stmt->error;
        }
    }
}
// Handle Get Schedule (AJAX request)
else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $schedule_id = intval($_GET['id']);
    $stmt = $db->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $schedule = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'schedule' => $schedule]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Schedule not found']);
        exit();
    }
}

// Get all courses with their schedules
$result = $db->query("
    SELECT c.*, s.id as schedule_id, s.day, s.start_time, s.end_time, s.room 
    FROM courses c 
    LEFT JOIN schedules s ON c.id = s.course_id 
    ORDER BY c.course_code, s.day, s.start_time
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule - EPU</title>
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
                    <h1 class="h2">Course Schedule</h1>
                    <button class="btn btn-primary" onclick="showAddScheduleForm()">
                        <i class="bi bi-plus-circle"></i> Add New Schedule
                    </button>
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

                <!-- Add Schedule Form -->
                <div id="addScheduleForm" class="card mb-4" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title">Add New Schedule</h5>
                    </div>
                    <div class="card-body">
                        <form action="schedule.php" method="POST" class="schedule-form">
                            <input type="hidden" name="action" value="add">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="course_id" class="form-label">Course:</label>
                                    <select class="form-select" id="course_id" name="course_id" required>
                                        <option value="">Select a course</option>
                                        <?php
                                        $courses = $db->query("SELECT id, course_code, course_name FROM courses ORDER BY course_code");
                                        while ($course = $courses->fetch_assoc()) {
                                            echo "<option value='{$course['id']}'>{$course['course_code']} - {$course['course_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="day" class="form-label">Day:</label>
                                    <select class="form-select" id="day" name="day" required>
                                        <option value="">Select a day</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                        <option value="Sunday">Sunday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_time" class="form-label">Start Time:</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_time" class="form-label">End Time:</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="room" class="form-label">Room:</label>
                                    <input type="text" class="form-control" id="room" name="room" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="hideAddScheduleForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Schedule</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Schedule Form -->
                <div id="editScheduleForm" class="card mb-4" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title">Edit Schedule</h5>
                    </div>
                    <div class="card-body">
                        <form action="schedule.php" method="POST" class="schedule-form">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" id="edit_id">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_course_id" class="form-label">Course:</label>
                                    <select class="form-select" id="edit_course_id" name="course_id" required>
                                        <option value="">Select a course</option>
                                        <?php
                                        $courses = $db->query("SELECT id, course_code, course_name FROM courses ORDER BY course_code");
                                        while ($course = $courses->fetch_assoc()) {
                                            echo "<option value='{$course['id']}'>{$course['course_code']} - {$course['course_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_day" class="form-label">Day:</label>
                                    <select class="form-select" id="edit_day" name="day" required>
                                        <option value="">Select a day</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                        <option value="Sunday">Sunday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_start_time" class="form-label">Start Time:</label>
                                    <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_end_time" class="form-label">End Time:</label>
                                    <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="edit_room" class="form-label">Room:</label>
                                    <input type="text" class="form-control" id="edit_room" name="room" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="hideEditScheduleForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Schedule</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Schedule List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Current Schedule</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Room</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $current_course = null;
                                    while ($row = $result->fetch_assoc()) {
                                        if ($row['schedule_id']) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['course_code']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['day']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['start_time']) . " - " . htmlspecialchars($row['end_time']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['room']) . "</td>";
                                            echo "<td>";
                                            echo "<button class='btn btn-sm btn-primary me-2' onclick='editSchedule(" . $row['schedule_id'] . ")'><i class='bi bi-pencil'></i> Edit</button>";
                                            echo "<form action='schedule.php' method='POST' style='display: inline;'>";
                                            echo "<input type='hidden' name='action' value='delete'>";
                                            echo "<input type='hidden' name='schedule_id' value='" . $row['schedule_id'] . "'>";
                                            echo "<button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this schedule?\")'><i class='bi bi-trash'></i> Delete</button>";
                                            echo "</form>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAddScheduleForm() {
            document.getElementById('addScheduleForm').style.display = 'block';
            document.getElementById('editScheduleForm').style.display = 'none';
        }

        function hideAddScheduleForm() {
            document.getElementById('addScheduleForm').style.display = 'none';
        }

        function hideEditScheduleForm() {
            document.getElementById('editScheduleForm').style.display = 'none';
        }

        function editSchedule(scheduleId) {
            fetch('schedule.php?action=get&id=' + scheduleId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const schedule = data.schedule;
                        document.getElementById('edit_id').value = schedule.id;
                        document.getElementById('edit_course_id').value = schedule.course_id;
                        document.getElementById('edit_day').value = schedule.day;
                        document.getElementById('edit_start_time').value = schedule.start_time;
                        document.getElementById('edit_end_time').value = schedule.end_time;
                        document.getElementById('edit_room').value = schedule.room;
                        
                        document.getElementById('addScheduleForm').style.display = 'none';
                        document.getElementById('editScheduleForm').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching schedule data.');
                });
        }
    </script>
</body>
</html> 