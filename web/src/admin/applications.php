<?php
// Session configuration - must be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/portal.php");
    exit();
}

$db = get_db_connection();

// Handle application status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id']) && isset($_POST['status'])) {
    $applicationId = $_POST['application_id'];
    $status = $_POST['status'];
    $adminComment = $_POST['admin_comment'] ?? '';

    // Validate status
    $validStatuses = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $validStatuses)) {
        $_SESSION['error_message'] = "Invalid status value provided.";
        header("Location: applications.php");
        exit();
    }

    // Validate application ID
    if (!is_numeric($applicationId)) {
        $_SESSION['error_message'] = "Invalid application ID.";
        header("Location: applications.php");
        exit();
    }

    $sql = "UPDATE applications SET status = ?, admin_comment = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        $_SESSION['error_message'] = "Database error: " . $db->error;
        header("Location: applications.php");
        exit();
    }

    $stmt->bind_param("ssi", $status, $adminComment, $applicationId);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Application status updated successfully.";
    } else {
        $_SESSION['error_message'] = "Error updating application status: " . $stmt->error;
    }
    
    header("Location: applications.php");
    exit();
}

// Handle Delete Application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $applicationId = $_POST['application_id'];
    
    if (!is_numeric($applicationId)) {
        $_SESSION['error_message'] = "Invalid application ID.";
        header("Location: applications.php");
        exit();
    }

    $sql = "DELETE FROM applications WHERE id = ?";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        $_SESSION['error_message'] = "Database error: " . $db->error;
        header("Location: applications.php");
        exit();
    }

    $stmt->bind_param("i", $applicationId);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Application deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error deleting application: " . $stmt->error;
    }
    
    header("Location: applications.php");
    exit();
}

// Fetch all applications with filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM applications WHERE 1=1";
if ($status_filter !== 'all') {
    $sql .= " AND status = ?";
}
if (!empty($search)) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);

if ($status_filter !== 'all' && !empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("ssss", $status_filter, $search_param, $search_param, $search_param);
} elseif ($status_filter !== 'all') {
    $stmt->bind_param("s", $status_filter);
} elseif (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - EPU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .modal-open {
            overflow: hidden;
        }
        .modal {
            overflow: hidden;
        }
        .modal-dialog {
            margin: 1.75rem auto;
            max-height: calc(100vh - 3.5rem);
        }
        .modal-content {
            max-height: calc(100vh - 3.5rem);
            overflow-y: auto;
        }
        .modal-body {
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #printContent, #printContent * {
                visibility: visible;
            }
            #printContent {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'Sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Applications</h1>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php 
                        echo htmlspecialchars($_SESSION['success_message']);
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php 
                        echo htmlspecialchars($_SESSION['error_message']);
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or email">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Applications Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Major</th>
                                        <th>GPA</th>
                                        <th>Status</th>
                                        <th>Applied Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['program']); ?></td>
                                            <td><?php echo htmlspecialchars($row['major']); ?></td>
                                            <td><?php echo htmlspecialchars($row['gpa']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $row['status'] === 'approved' ? 'success' : 
                                                        ($row['status'] === 'rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm" onclick="openViewModal(<?php echo $row['id']; ?>)">
                                                    <i class="bi bi-eye"></i> View
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

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" onclick="printApplication()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <form method="POST" action="applications.php" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="application_id" id="delete_application_id">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this application? This action cannot be undone.')">
                            <i class="bi bi-trash"></i> Delete Application
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openViewModal(id) {
            // Set the application ID for delete action
            document.getElementById('delete_application_id').value = id;
            
            // Fetch application details
            fetch(`get_application.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('viewModal')).show();
                });
        }

        function printApplication() {
            const printContent = document.getElementById('modalContent').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <div id="printContent">
                    <div class="container mt-4">
                        <h2 class="text-center mb-4">Application Details</h2>
                        ${printContent}
                    </div>
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            
            // Reinitialize the modal
            const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            viewModal.show();
        }
    </script>
</body>
</html> 