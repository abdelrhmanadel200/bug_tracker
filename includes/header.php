<?php
// Include configuration
require_once 'config/config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>

<header class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light container">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bug text-primary me-2"></i>
                <span class="fw-bold">BugTracker</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <?php if (is_admin()): ?>
                            <a href="admin/dashboard.php" class="nav-link">Dashboard</a>
                        <?php elseif (is_staff()): ?>
                            <a href="staff/dashboard.php" class="nav-link">Dashboard</a>
                        <?php elseif (is_customer()): ?>
                            <a href="customer/dashboard.php" class="nav-link">Dashboard</a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="features.php">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <?php
                    if (!is_logged_in()) {

                    ?>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="login.php">Login</a>
                        </li>
                    <?php
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>
</header>