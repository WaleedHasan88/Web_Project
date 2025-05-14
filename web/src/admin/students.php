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
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle Add Student
        if (isset($_POST['action']) && $_POST['action'] === 'add') {
            $student_id = trim($_POST['student_id']);
            $email = trim($_POST['email']);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate passwords match
            if ($password !== $confirm_password) {
                $_SESSION['error_message'] = "Passwords do not match.";
            } else {
                // Check if student ID or email already exists
                $stmt = $db->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
                $stmt->bind_param("ss", $student_id, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("INSERT INTO students (student_id, email, first_name, last_name, password) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $student_id, $email, $first_name, $last_name, $hashed_password);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Student added successfully.";
                    } else {
                        $_SESSION['error_message'] = "Error adding student: " . $stmt->error;
                    }
                } else {
                    $_SESSION['error_message'] = "Student ID or email already exists.";
                }
            }
        }
        // Handle Update Student
        else if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $id = intval($_POST['id']);
            $student_id = trim($_POST['student_id']);
            $email = trim($_POST['email']);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Check if student ID or email exists for other students
            $stmt = $db->prepare("SELECT id FROM students WHERE (student_id = ? OR email = ?) AND id != ?");
            $stmt->bind_param("ssi", $student_id, $email, $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                if (!empty($password)) {
                    // Validate passwords match
                    if ($password !== $confirm_password) {
                        $_SESSION['error_message'] = "Passwords do not match.";
                    } else {
                        // Hash new password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        $stmt = $db->prepare("UPDATE students SET student_id = ?, email = ?, first_name = ?, last_name = ?, password = ? WHERE id = ?");
                        $stmt->bind_param("sssssi", $student_id, $email, $first_name, $last_name, $hashed_password, $id);
                    }
                } else {
                    // Update without changing password
                    $stmt = $db->prepare("UPDATE students SET student_id = ?, email = ?, first_name = ?, last_name = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $student_id, $email, $first_name, $last_name, $id);
                }
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Student updated successfully.";
                } else {
                    $_SESSION['error_message'] = "Error updating student: " . $stmt->error;
                }
            } else {
                $_SESSION['error_message'] = "Student ID or email already exists for another student.";
            }
        }
        // Handle Delete Student
        else if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $student_id = intval($_POST['student_id']);
            
            // First delete student enrollments
            $stmt = $db->prepare("DELETE FROM enrollments WHERE student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            
            // Then delete the student
            $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Student deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Error deleting student: " . $stmt->error;
            }
        }
    }
    // Handle Get Student (AJAX request)
    else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
        $student_id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT id, student_id, email, first_name, last_name FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'student' => $student]);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Student not found']);
            exit();
        }
    }
    
    // Get all students for display
    $stmt = $db->prepare("SELECT * FROM students ORDER BY student_id");
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while processing your request. Please try again later.";
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - EPU</title>
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
                    <h1 class="h2">Manage Students</h1>
                    <button class="btn btn-primary" onclick="showAddStudentForm()">
                        <i class="bi bi-plus-circle"></i> Add New Student
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

                <!-- Add Student Form -->
                <div id="addStudentForm" class="card mb-4" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title">Add New Student</h5>
                    </div>
                    <div class="card-body">
                        <form action="students.php" method="POST" class="admin-form">
                            <input type="hidden" name="action" value="add">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="student_id" class="form-label">Student ID:</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" required pattern="[A-Z0-9]+" title="Student ID should contain only uppercase letters and numbers">
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
                                <button type="button" class="btn btn-secondary me-2" onclick="hideAddStudentForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Student</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Student Form -->
                <div id="editStudentForm" class="modal fade" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Student</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editStudentFormContent" action="students.php" method="POST" class="admin-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" id="edit_id">
                                    
                                    <div class="mb-3">
                                        <label for="edit_student_id" class="form-label">Student ID</label>
                                        <input type="text" class="form-control" id="edit_student_id" name="student_id" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="edit_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="edit_email" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="edit_first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="edit_last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="edit_password" class="form-label">New Password (leave blank to keep current password)</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="edit_password" name="password" minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Password must contain at least one number, one uppercase and lowercase letter, and at least 8 characters">
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="edit_confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="edit_confirm_password" name="confirm_password" minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Password must contain at least one number, one uppercase and lowercase letter, and at least 8 characters">
                                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" form="editStudentFormContent" class="btn btn-primary">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Current Students</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($students)): ?>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary me-2" onclick="editStudent(<?php echo $student['id']; ?>)">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <form action="students.php" method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No students found.</td>
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
        function showAddStudentForm() {
            document.getElementById('addStudentForm').style.display = 'block';
        }

        function hideAddStudentForm() {
            document.getElementById('addStudentForm').style.display = 'none';
        }

        function editStudent(studentId) {
            fetch('students.php?action=get&id=' + studentId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        // Populate the edit form
                        document.getElementById('edit_id').value = data.student.id;
                        document.getElementById('edit_student_id').value = data.student.student_id;
                        document.getElementById('edit_email').value = data.student.email;
                        document.getElementById('edit_first_name').value = data.student.first_name || '';
                        document.getElementById('edit_last_name').value = data.student.last_name || '';
                        
                        // Show the modal
                        const editModal = new bootstrap.Modal(document.getElementById('editStudentForm'));
                        editModal.show();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching student data.');
                });
        }

        // Password confirmation validation
        document.querySelectorAll('.admin-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const password = this.querySelector('input[name="password"]');
                const confirmPassword = this.querySelector('input[name="confirm_password"]');
                
                if (password.value && password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            });
        });

        // Show/hide password functionality
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
        });
    </script>
</body>
</html> 