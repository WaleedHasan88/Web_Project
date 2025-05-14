<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}

if (!isset($_GET['id'])) {
    exit('No application ID provided');
}

$db = get_db_connection();
$id = (int)$_GET['id'];

$sql = "SELECT * FROM applications WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    exit('Application not found');
}
?>

<div class="row mb-3">
    <div class="col-md-6">
        <strong>Name:</strong> <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
    </div>
    <div class="col-md-6">
        <strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-6">
        <strong>Phone:</strong> <?php echo htmlspecialchars($row['phone']); ?>
    </div>
    <div class="col-md-6">
        <strong>Date of Birth:</strong> <?php echo date('M d, Y', strtotime($row['date_of_birth'])); ?>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-6">
        <strong>Program:</strong> <?php echo htmlspecialchars($row['program']); ?>
    </div>
    <div class="col-md-6">
        <strong>Major:</strong> <?php echo htmlspecialchars($row['major']); ?>
    </div>
</div>
<div class="mb-3">
    <strong>GPA:</strong> <?php echo htmlspecialchars($row['gpa']); ?>
</div>
<div class="mb-3">
    <strong>Personal Statement:</strong>
    <p><?php echo nl2br(htmlspecialchars($row['personal_statement'])); ?></p>
</div>
<div class="mb-3">
    <strong>Extracurricular Activities:</strong>
    <p><?php echo nl2br(htmlspecialchars($row['extracurricular_activities'])); ?></p>
</div>
<div class="mb-3">
    <strong>Documents:</strong>
    <ul>
        <li><a href="../<?php echo htmlspecialchars($row['transcript_path']); ?>" target="_blank">Transcript</a></li>
        <li><a href="../<?php echo htmlspecialchars($row['test_scores_path']); ?>" target="_blank">Test Scores</a></li>
        <li><a href="../<?php echo htmlspecialchars($row['recommendation_path']); ?>" target="_blank">Recommendation</a></li>
    </ul>
</div>
<form method="POST" action="applications.php">
    <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
    <div class="mb-3">
        <label class="form-label">Status:</label>
        <select name="status" class="form-select">
            <option value="pending" <?php echo $row['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="approved" <?php echo $row['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo $row['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Admin Comment:</label>
        <textarea name="admin_comment" class="form-control" rows="3"><?php echo htmlspecialchars($row['admin_comment'] ?? ''); ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Update Status</button>
</form> 