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
                    if (is_logged_in()) {
                        if (is_admin()) {
                            redirect('admin/dashboard.php');
                        } else if (is_staff()) {
                            redirect('staff/dashboard.php');
                        } else if (is_customer()) {
                            redirect('customer/dashboard.php');
                        }
                    } else {
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