<!-- Sidebar -->
<div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <img src="../assets/images/logo.png" alt="EPU Logo" class="img-fluid mb-2 logo-img">
            <h6 class="text-white">Student Portal</h6>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'courses.php' ? 'active' : ''; ?>" href="courses.php">
                    <i class="bi bi-book me-2"></i> Courses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'lectures.php' ? 'active' : ''; ?>" href="lectures.php">
                    <i class="bi bi-file-earmark-text me-2"></i> Lectures
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'schedule.php' ? 'active' : ''; ?>" href="schedule.php">
                    <i class="bi bi-calendar3 me-2"></i> Schedule
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'grades.php' ? ' active' : ''; ?>" href="grades.php">
                    <i class="bi bi-card-list me-2"></i> Grades
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="bi bi-gear me-2"></i>
                    Settings
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../includes/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</div>
            