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

try {
    $db = get_db_connection();
    
    // Get all teachers for the instructor dropdown
    $teachers = $db->query("SELECT id, full_name FROM teachers ORDER BY full_name");
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $course_code = trim($_POST['course_code'] ?? '');
        $course_name = trim($_POST['course_name'] ?? '');
        $instructor = trim($_POST['instructor'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $credits = intval($_POST['credits'] ?? 0);

        switch($action) {
            case 'add':
                // Check if course code already exists
                $stmt = $db->prepare("SELECT id FROM courses WHERE course_code = ?");
                $stmt->bind_param("s", $course_code);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    $stmt = $db->prepare("INSERT INTO courses (course_code, course_name, instructor, department, credits) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssi", $course_code, $course_name, $instructor, $department, $credits);
                    
                    if ($stmt->execute()) {
                        $course_id = $stmt->insert_id;
                        
                        // Handle schedule creation
                        if (isset($_POST['schedule_day']) && is_array($_POST['schedule_day'])) {
                            $schedule_stmt = $db->prepare("INSERT INTO schedules (course_id, day, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)");
                            
                            foreach ($_POST['schedule_day'] as $index => $day) {
                                if (!empty($day) && !empty($_POST['schedule_start'][$index]) && !empty($_POST['schedule_end'][$index]) && !empty($_POST['schedule_room'][$index])) {
                                    $start_time = $_POST['schedule_start'][$index];
                                    $end_time = $_POST['schedule_end'][$index];
                                    $room = $_POST['schedule_room'][$index];
                                    
                                    $schedule_stmt->bind_param("issss", $course_id, $day, $start_time, $end_time, $room);
                                    $schedule_stmt->execute();
                                }
                            }
                        }
                        
                        $_SESSION['success_message'] = "Course added successfully.";
                    } else {
                        $_SESSION['error_message'] = "Error adding course: " . $stmt->error;
                    }
                } else {
                    $_SESSION['error_message'] = "Course code already exists.";
                }
                break;

            case 'update':
                $id = intval($_POST['id']);
                // Check if course code exists for other courses
                $stmt = $db->prepare("SELECT id FROM courses WHERE course_code = ? AND id != ?");
                $stmt->bind_param("si", $course_code, $id);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    $stmt = $db->prepare("UPDATE courses SET course_code = ?, course_name = ?, instructor = ?, department = ?, credits = ? WHERE id = ?");
                    $stmt->bind_param("ssssii", $course_code, $course_name, $instructor, $department, $credits, $id);
                    
                    if ($stmt->execute()) {
                        // Delete existing schedules
                        $stmt = $db->prepare("DELETE FROM schedules WHERE course_id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        
                        // Insert new schedules
                        if (isset($_POST['schedule_day']) && is_array($_POST['schedule_day'])) {
                            $schedule_stmt = $db->prepare("INSERT INTO schedules (course_id, day, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)");
                            
                            foreach ($_POST['schedule_day'] as $index => $day) {
                                if (!empty($day) && !empty($_POST['schedule_start'][$index]) && !empty($_POST['schedule_end'][$index]) && !empty($_POST['schedule_room'][$index])) {
                                    $start_time = $_POST['schedule_start'][$index];
                                    $end_time = $_POST['schedule_end'][$index];
                                    $room = $_POST['schedule_room'][$index];
                                    
                                    $schedule_stmt->bind_param("issss", $id, $day, $start_time, $end_time, $room);
                                    $schedule_stmt->execute();
                                }
                            }
                        }
                        
                        $_SESSION['success_message'] = "Course updated successfully.";
                    } else {
                        $_SESSION['error_message'] = "Error updating course: " . $stmt->error;
                    }
                } else {
                    $_SESSION['error_message'] = "Course code already exists for another course.";
                }
                break;

            case 'delete':
                $course_id = intval($_POST['course_id']);
                // Delete course enrollments first
                $stmt = $db->prepare("DELETE FROM enrollments WHERE course_id = ?");
                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                
                // Then delete the course
                $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->bind_param("i", $course_id);
                $stmt->execute() ? $_SESSION['success_message'] = "Course deleted successfully." : $_SESSION['error_message'] = "Error deleting course: " . $stmt->error;
                break;
        }
    }
    // Handle Get Course (AJAX request)
    else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
        $course_id = intval($_GET['id']);
        $stmt = $db->prepare("
            SELECT c.*, GROUP_CONCAT(
                CONCAT(s.day, '|', s.start_time, '|', s.end_time, '|', s.room)
                SEPARATOR '||'
            ) as schedule_info
            FROM courses c 
            LEFT JOIN schedules s ON c.id = s.course_id 
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $course = $result->fetch_assoc();
            // Parse schedule information
            $schedules = [];
            if (!empty($course['schedule_info'])) {
                $schedule_entries = explode('||', $course['schedule_info']);
                foreach ($schedule_entries as $entry) {
                    list($day, $start, $end, $room) = explode('|', $entry);
                    $schedules[] = [
                        'day' => $day,
                        'start_time' => $start,
                        'end_time' => $end,
                        'room' => $room
                    ];
                }
            }
            $course['schedules'] = $schedules;
            
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'course' => $course]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Course not found']);
        }
        exit();
    }
    
    // Get all courses for display
    $stmt = $db->prepare("
        SELECT c.*, GROUP_CONCAT(
            CONCAT(s.day, ' ', TIME_FORMAT(s.start_time, '%h:%i %p'), '-', TIME_FORMAT(s.end_time, '%h:%i %p'), ' (', s.room, ')')
            SEPARATOR '; '
        ) as schedule_info
        FROM courses c 
        LEFT JOIN schedules s ON c.id = s.course_id 
        GROUP BY c.id
        ORDER BY c.course_code
    ");
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while processing your request. Please try again later.";
    $courses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - EPU</title>
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
                    <h1 class="h2">Manage Courses</h1>
                    <button class="btn btn-primary" onclick="toggleCourseForm()">
                        <i class="bi bi-plus-circle"></i> Add New Course
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

                <!-- Course Form (Combined Add/Edit) -->
                <div id="courseForm" class="card mb-4" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title mb-0" id="formTitle">Add New Course</h5>
                    </div>
                    <div class="card-body">
                        <form action="courses.php" method="POST" class="course-form">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" id="course_id" name="id">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="course_code" class="form-label">Course Code:</label>
                                    <input type="text" class="form-control" id="course_code" name="course_code" required pattern="[A-Z0-9]+" title="Course code should contain only uppercase letters and numbers">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="course_name" class="form-label">Course Name:</label>
                                    <input type="text" class="form-control" id="course_name" name="course_name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="instructor" class="form-label">Instructor:</label>
                                    <select class="form-select" id="instructor" name="instructor" required>
                                        <option value="">Select Instructor</option>
                                        <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($teacher['full_name']); ?>">
                                                <?php echo htmlspecialchars($teacher['full_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">Department:</label>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="Information Systems Engineering">Information Systems Engineering</option>
                                        <option value="Mechanical and Energy Engineering">Mechanical and Energy Engineering</option>
                                        <option value="Highway and Bridge Engineering">Highway and Bridge Engineering</option>
                                        <option value="Civil Engineering">Civil Engineering</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="credits" class="form-label">Credits:</label>
                                    <input type="number" class="form-control" id="credits" name="credits" required min="1" max="6">
                                </div>
                            </div>

                            <!-- Schedule Section -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Course Schedule</h6>
                                </div>
                                <div class="card-body">
                                    <div id="scheduleContainer">
                                        <div class="schedule-entry row mb-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Day:</label>
                                                <select class="form-select" name="schedule_day[]" required>
                                                    <option value="">Select Day</option>
                                                    <option value="Monday">Monday</option>
                                                    <option value="Tuesday">Tuesday</option>
                                                    <option value="Wednesday">Wednesday</option>
                                                    <option value="Thursday">Thursday</option>
                                                    <option value="Friday">Friday</option>
                                                    <option value="Saturday">Saturday</option>
                                                    <option value="Sunday">Sunday</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Start Time:</label>
                                                <input type="time" class="form-control" name="schedule_start[]" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">End Time:</label>
                                                <input type="time" class="form-control" name="schedule_end[]" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Room:</label>
                                                <input type="text" class="form-control" name="schedule_room[]" required>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-danger btn-sm mb-3" onclick="removeSchedule(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="addSchedule()">
                                        <i class="bi bi-plus-circle"></i> Add Another Schedule
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" class="btn btn-secondary me-2" onclick="toggleCourseForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">Add Course</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Courses List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Current Courses</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Instructor</th>
                                        <th>Schedule</th>
                                        <th>Credits</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($courses)): ?>
                                        <?php foreach ($courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                <td><?php echo htmlspecialchars($course['instructor']); ?></td>
                                                <td>
                                                    <?php if (!empty($course['schedule_info'])): ?>
                                                        <?php 
                                                        $schedules = explode('; ', $course['schedule_info']);
                                                        foreach ($schedules as $schedule) {
                                                            echo '<div class="mb-1">' . htmlspecialchars($schedule) . '</div>';
                                                        }
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No schedule set</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($course['credits']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="editCourse(<?php echo $course['id']; ?>)">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <form action="courses.php" method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this course?')">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No courses found.</td>
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
    <script>
        function toggleCourseForm() {
            const form = document.getElementById('courseForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'none') {
                resetForm();
            }
        }

        function resetForm() {
            document.getElementById('formTitle').textContent = 'Add New Course';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').textContent = 'Add Course';
            document.getElementById('course_id').value = '';
            document.querySelector('.course-form').reset();
            // Reset schedule entries to one
            const container = document.getElementById('scheduleContainer');
            while (container.children.length > 1) {
                container.removeChild(container.lastChild);
            }
        }

        function addSchedule() {
            const container = document.getElementById('scheduleContainer');
            const template = container.children[0].cloneNode(true);
            // Clear values
            template.querySelectorAll('input, select').forEach(input => input.value = '');
            container.appendChild(template);
        }

        function removeSchedule(button) {
            const container = document.getElementById('scheduleContainer');
            if (container.children.length > 1) {
                button.closest('.schedule-entry').remove();
            }
        }

        function editCourse(courseId) {
            fetch('courses.php?action=get&id=' + courseId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const course = data.course;
                        document.getElementById('formTitle').textContent = 'Edit Course';
                        document.getElementById('formAction').value = 'update';
                        document.getElementById('submitBtn').textContent = 'Update Course';
                        document.getElementById('course_id').value = course.id;
                        document.getElementById('course_code').value = course.course_code;
                        document.getElementById('course_name').value = course.course_name;
                        document.getElementById('instructor').value = course.instructor || '';
                        document.getElementById('credits').value = course.credits || '';
                        
                        const department = document.getElementById('department');
                        for (let i = 0; i < department.options.length; i++) {
                            if (department.options[i].value === course.department) {
                                department.selectedIndex = i;
                                break;
                            }
                        }

                        // Handle schedules
                        const container = document.getElementById('scheduleContainer');
                        container.innerHTML = ''; // Clear existing schedules
                        
                        if (course.schedules && course.schedules.length > 0) {
                            course.schedules.forEach(schedule => {
                                const scheduleEntry = createScheduleEntry();
                                scheduleEntry.querySelector('[name="schedule_day[]"]').value = schedule.day;
                                scheduleEntry.querySelector('[name="schedule_start[]"]').value = schedule.start_time;
                                scheduleEntry.querySelector('[name="schedule_end[]"]').value = schedule.end_time;
                                scheduleEntry.querySelector('[name="schedule_room[]"]').value = schedule.room;
                                container.appendChild(scheduleEntry);
                            });
                        } else {
                            // Add one empty schedule entry if no schedules exist
                            container.appendChild(createScheduleEntry());
                        }
                        
                        toggleCourseForm();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching course data.');
                });
        }

        function createScheduleEntry() {
            const template = document.createElement('div');
            template.className = 'schedule-entry row mb-3';
            template.innerHTML = `
                <div class="col-md-3">
                    <label class="form-label">Day:</label>
                    <select class="form-select" name="schedule_day[]" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Time:</label>
                    <input type="time" class="form-control" name="schedule_start[]" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Time:</label>
                    <input type="time" class="form-control" name="schedule_end[]" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Room:</label>
                    <input type="text" class="form-control" name="schedule_room[]" required>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm mb-3" onclick="removeSchedule(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            return template;
        }
    </script>
</body>
</html> 