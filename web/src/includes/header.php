<?php
require_once __DIR__ . '/config.php';

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if (empty($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.html');
}

// Function to check if a nav item is active
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo SITE_NAME; ?></title>
    <link rel="icon" href="<?php echo SITE_URL; ?>/../assets/images/logo.png" type="image/icon type" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/../assets/css/style.css" />
    <?php if (isset($additionalStyles)) echo $additionalStyles; ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a href="<?php echo SITE_URL; ?>/index.php" class="navbar-brand">
                <img src="<?php echo SITE_URL; ?>/../assets/images/logo.png" alt="Epu Logo" style="width: 40px; height: 50px" />
            </a>
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php">Erbil Polytechnic University</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('index'); ?>" href="<?php echo SITE_URL; ?>/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('about'); ?>" href="<?php echo SITE_URL; ?>/about.php">About</a>
                    </li>
                    <li class="nav-item"></li>
                        <a class="nav-link <?php echo isActive('research'); ?>" href="<?php echo SITE_URL; ?>/research.php">Research</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('admissions'); ?>" href="<?php echo SITE_URL; ?>/admissions.php">Admissions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('campus'); ?>" href="<?php echo SITE_URL; ?>/campus.php">Campus Life</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('contact'); ?>" href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="ms-3">
                        <a href="<?php echo SITE_URL; ?>/<?php echo $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'; ?>" 
                           class="btn btn-outline-light">Dashboard</a>
                        <a href="<?php echo SITE_URL; ?>/../includes/logout.php" class="btn btn-danger ms-2">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/portal.php" class="btn btn-primary ms-3">Portal</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="content-wrapper"> 