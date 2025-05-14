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

// Get teacher and course information in a single query
$stmt = $db->prepare("
    SELECT t.*, c.id as course_id, c.course_code, c.course_name 
    FROM teachers t 
    LEFT JOIN courses c ON c.instructor = t.full_name 
    WHERE t.id = ?
    ORDER BY c.course_code
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Get selected course or default to first course
$selected_course_id = $_GET['course_id'] ?? null;
if (!$selected_course_id && $result->num_rows > 0) {
    $selected_course_id = $teacher['course_id'];
    $result->data_seek(0);
}

// Handle file operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_lecture'])) {
    handleFileUpload($db, $_SESSION['user_id'], $_POST, $_FILES);
} elseif (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    handleFileDeletion($db, $_SESSION['user_id'], $_GET['delete']);
}

// Get lecture files for selected course
$lecture_files = null;
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

function handleFileUpload($db, $teacher_id, $post, $files) {
    $course_id = $post['course_id'];
    $title = $post['title'];
    $description = $post['description'];
    
    if (!$course_id) {
        $_SESSION['error_message'] = "Please select a course.";
        header("Location: lectures.php");
        exit();
    }
    
    if (!isset($files['lecture_file']) || $files['lecture_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "Please select a file to upload.";
        header("Location: lectures.php");
        exit();
    }

    $file = $files['lecture_file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        $_SESSION['error_message'] = "Invalid file type. Allowed types: " . implode(', ', $allowed_ext);
        header("Location: lectures.php");
        exit();
    }

    $upload_dir = '../uploads/lectures/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $new_file_name = uniqid() . '_' . $file['name'];
    $file_path = $upload_dir . $new_file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $stmt = $db->prepare("
            INSERT INTO lecture_files (course_id, title, description, file_name, file_path, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssi", $course_id, $title, $description, $file['name'], $new_file_name, $teacher_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Lecture file uploaded successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to save file information.";
        }
    } else {
        $_SESSION['error_message'] = "Failed to upload file.";
    }
    
    header("Location: lectures.php");
    exit();
}

function handleFileDeletion($db, $teacher_id, $file_id) {
    $stmt = $db->prepare("SELECT file_path FROM lecture_files WHERE id = ? AND uploaded_by = ?");
    $stmt->bind_param("ii", $file_id, $teacher_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();
    
    if ($file) {
        $file_path = '../uploads/lectures/' . $file['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $stmt = $db->prepare("DELETE FROM lecture_files WHERE id = ?");
        $stmt->bind_param("i", $file_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Lecture file deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete file.";
        }
    }
    
    header("Location: lectures.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lectures - EPU</title>
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
                    <h1 class="h2">Manage Lectures</h1>
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

                <!-- Course Selection -->
                <div class="card mb-4 course-select">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="course_id" class="form-label">Select Course</label>
                                <select class="form-select" id="course_id" name="course_id" onchange="this.form.submit()">
                                    <?php while ($course = $result->fetch_assoc()): ?>
                                        <option value="<?php echo $course['course_id']; ?>" <?php echo $selected_course_id == $course['course_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Upload Form -->
                <div class="card mb-4 lecture-card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-cloud-upload me-2"></i>Upload Lecture File
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                            <div class="upload-area mb-4">
                                <i class="bi bi-file-earmark-arrow-up file-icon"></i>
                                <h5>Drag & Drop or Click to Upload</h5>
                                <p class="text-muted mb-3">Supported formats: PDF, DOC, DOCX, PPT, PPTX, TXT, ZIP, RAR</p>
                                <input type="file" class="form-control" id="lecture_file" name="lecture_file" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="1"></textarea>
                                </div>
                            </div>
                            <button type="submit" name="upload_lecture" class="btn btn-primary">
                                <i class="bi bi-upload me-1"></i> Upload File
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Lecture Files List -->
                <?php if ($lecture_files && $lecture_files->num_rows > 0): ?>
                    <div class="card lecture-card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-folder me-2"></i>Lecture Files
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
                                            <th class="text-end">Actions</th>
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
                                                    <a href="lectures.php?delete=<?php echo $file['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this file?')">
                                                        <i class="bi bi-trash"></i>
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
                        No lecture files uploaded for this course yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 