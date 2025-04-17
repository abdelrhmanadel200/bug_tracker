<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BugTracker - Track and Manage Software Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold text-primary">Efficient Bug Tracking Solution</h1>
                <p class="lead">Track, manage, and resolve software issues with our comprehensive bug tracking system.</p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                    <a href="register.php" class="btn btn-primary btn-lg px-4 me-md-2">Get Started</a>
                    <a href="login.php" class="btn btn-outline-secondary btn-lg px-4">Login</a>
                </div>
                <div class="mt-5">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-bug fa-3x text-primary mb-3"></i>
                                <h5>Bug Reporting</h5>
                                <p class="small">Submit detailed bug reports with screenshots</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                                <h5>Issue Tracking</h5>
                                <p class="small">Monitor bug status and resolution progress</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-comments fa-3x text-primary mb-3"></i>
                                <h5>Communication</h5>
                                <p class="small">Direct messaging between teams and customers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <!-- <img src="assets/images/bug-tracking.svg" alt="Bug Tracking Illustration" class="img-fluid"> -->
            </div>
        </div>

        <div class="row mt-5 pt-5 border-top">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-tie text-primary me-2"></i>For Administrators</h5>
                        <p class="card-text">Manage staff, projects, and oversee the entire bug resolution process. Assign bugs to appropriate team members and monitor progress.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-cog text-primary me-2"></i>For Staff</h5>
                        <p class="card-text">View assigned bugs, update status, and communicate with customers. Collaborate with team members to resolve issues efficiently.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user text-primary me-2"></i>For Customers</h5>
                        <p class="card-text">Submit bug reports with screenshots, track resolution progress, and communicate with the support team for updates.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>