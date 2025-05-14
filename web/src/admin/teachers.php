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
    
    // Handle GET request for teacher data
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
        $teacher_id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT * FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $teacher = $result->fetch_assoc();
        
        if ($teacher) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'teacher' => $teacher]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Teacher not found']);
        }
        exit();
    }
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle Add Teacher
        if (isset($_POST['action']) && $_POST['action'] === 'add') {
            $teacher_id = trim($_POST['teacher_id']);
            $email = trim($_POST['email']);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $department = trim($_POST['department']);
            $specialization = trim($_POST['specialization']);

            // Validate passwords match
            if ($password !== $confirm_password) {
                $_SESSION['error_message'] = "Passwords do not match.";
            } else {
                // Check if teacher ID or email already exists
                $stmt = $db->prepare("SELECT id FROM teachers WHERE teacher_id = ? OR email = ?");
                $stmt->bind_param("ss", $teacher_id, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $_SESSION['error_message'] = "Teacher ID or email already exists.";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $full_name = $first_name . ' ' . $last_name;

                    // Insert new teacher
                    $stmt = $db->prepare("INSERT INTO teachers (teacher_id, first_name, last_name, email, password, full_name, department, specialization) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssss", $teacher_id, $first_name, $last_name, $email, $hashed_password, $full_name, $department, $specialization);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Teacher added successfully.";
                    } else {
                        $_SESSION['error_message'] = "Error adding teacher: " . $stmt->error;
                    }
                }
            }
        }
        // Handle Edit Teacher
        elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
            $teacher_id = intval($_POST['teacher_id']);
            $email = trim($_POST['email']);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $department = trim($_POST['department']);
            $specialization = trim($_POST['specialization']);
            $full_name = $first_name . ' ' . $last_name;

            // Check if email already exists for other teachers
            $stmt = $db->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $teacher_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $_SESSION['error_message'] = "Email already exists for another teacher.";
            } else {
                // Update teacher information
                $stmt = $db->prepare("UPDATE teachers SET email = ?, first_name = ?, last_name = ?, full_name = ?, department = ?, specialization = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $email, $first_name, $last_name, $full_name, $department, $specialization, $teacher_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Teacher information updated successfully.";
                } else {
                    $_SESSION['error_message'] = "Error updating teacher: " . $stmt->error;
                }
            }
        }
        // Handle Delete Teacher
        elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $teacher_id = intval($_POST['teacher_id']);
            
            // Check if teacher has any assigned courses
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM courses WHERE instructor = (SELECT full_name FROM teachers WHERE id = ?)");
            $stmt->bind_param("i", $teacher_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $course_count = $result->fetch_assoc()['count'];

            if ($course_count > 0) {
                $_SESSION['error_message'] = "Cannot delete teacher with assigned courses. Please reassign courses first.";
            } else {
                $stmt = $db->prepare("DELETE FROM teachers WHERE id = ?");
                $stmt->bind_param("i", $teacher_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Teacher deleted successfully.";
                } else {
                    $_SESSION['error_message'] = "Error deleting teacher: " . $stmt->error;
                }
            }
        }
    }

    // Get all teachers
    $teachers = $db->query("SELECT * FROM teachers ORDER BY department, full_name");
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - EPU</title>
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
                    <h1 class="h2">Manage Teachers</h1>
                    <button class="btn btn-primary" onclick="showAddTeacherForm()">
                        <i class="bi bi-plus-circle"></i> Add New Teacher
                    </button>
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

                <!-- Add Teacher Form -->
                <div id="addTeacherForm" class="card mb-4" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title">Add New Teacher</h5>
                    </div>
                    <div class="card-body">
                        <form action="teachers.php" method="POST" class="admin-form">
                            <input type="hidden" name="action" value="add">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="teacher_id" class="form-label">Teacher ID:</label>
                                    <input type="text" class="form-control" id="teacher_id" name="teacher_id" required pattern="[A-Z0-9]+" title="Teacher ID should contain only uppercase letters and numbers">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email:</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name:</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required pattern="[A-Za-z]+" title="First name should contain only letters">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name:</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required pattern="[A-Za-z]+" title="Last name should contain only letters">
                                </div>
                            </div>
                            <div class="row">
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
                                <div class="col-md-6 mb-3">
                                    <label for="specialization" class="form-label">Specialization:</label>
                                    <input type="text" class="form-control" id="specialization" name="specialization" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password:</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Password must contain at least one number, one uppercase and lowercase letter, and at least 8 characters">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password:</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Password must contain at least one number, one uppercase and lowercase letter, and at least 8 characters">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="hideAddTeacherForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Teacher</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Teachers Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Current Teachers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Teacher ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Specialization</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['department']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['specialization']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-2" onclick="editTeacher(<?php echo $teacher['id']; ?>)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteTeacher(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['full_name']); ?>')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="teacher_id" id="edit_teacher_id">
                        <div class="mb-3">
                            <label class="form-label">Teacher ID</label>
                            <input type="text" class="form-control" id="edit_teacher_id_display" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department" id="edit_department" required>
                                <option value="">Select Department</option>
                                <option value="Information Systems Engineering">Information Systems Engineering</option>
                                <option value="Mechanical and Energy Engineering">Mechanical and Energy Engineering</option>
                                <option value="Highway and Bridge Engineering">Highway and Bridge Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Specialization</label>
                            <input type="text" class="form-control" name="specialization" id="edit_specialization" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Teacher Modal -->
    <div class="modal fade" id="deleteTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="teacher_id" id="delete_teacher_id">
                        <p>Are you sure you want to delete teacher: <span id="delete_teacher_name"></span>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAddTeacherForm() {
            document.getElementById('addTeacherForm').style.display = 'block';
        }

        function hideAddTeacherForm() {
            document.getElementById('addTeacherForm').style.display = 'none';
        }

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        });

        function editTeacher(teacherId) {
            // Fetch teacher data and populate edit form
            fetch('teachers.php?action=get&id=' + teacherId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('edit_teacher_id').value = data.teacher.id;
                        document.getElementById('edit_teacher_id_display').value = data.teacher.teacher_id;
                        document.getElementById('edit_first_name').value = data.teacher.first_name;
                        document.getElementById('edit_last_name').value = data.teacher.last_name;
                        document.getElementById('edit_email').value = data.teacher.email;
                        document.getElementById('edit_department').value = data.teacher.department;
                        document.getElementById('edit_specialization').value = data.teacher.specialization;
                        
                        const editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
                        editModal.show();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to load teacher data'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading teacher data. Please try again.');
                });
        }

        function deleteTeacher(teacherId, teacherName) {
            if (confirm('Are you sure you want to delete teacher: ' + teacherName + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'teachers.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'teacher_id';
                idInput.value = teacherId;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 