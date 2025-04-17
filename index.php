<?php
// Include configuration
require_once 'config/config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>

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

    <!-- Hero Section -->
    <section class="hero-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-primary mb-3">Efficient Bug Tracking Solution</h1>
                    <p class="lead mb-4">Track, manage, and resolve software issues with our comprehensive bug tracking system. Streamline your development process and improve team collaboration.</p>
                    
                    <?php if (!$is_logged_in): ?>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                        <a href="register.php" class="btn btn-primary btn-lg px-4 me-md-2">Get Started</a>
                        <a href="login.php" class="btn btn-outline-secondary btn-lg px-4">Login</a>
                    </div>
                    <?php else: ?>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                        <?php if ($user_role === 'admin'): ?>
                            <a href="admin/dashboard.php" class="btn btn-primary btn-lg px-4 me-md-2">Admin Dashboard</a>
                        <?php elseif ($user_role === 'staff'): ?>
                            <a href="staff/dashboard.php" class="btn btn-primary btn-lg px-4 me-md-2">Staff Dashboard</a>
                        <?php else: ?>
                            <a href="customer/dashboard.php" class="btn btn-primary btn-lg px-4 me-md-2">My Dashboard</a>
                        <?php endif; ?>
                        <a href="features.php" class="btn btn-outline-secondary btn-lg px-4">Explore Features</a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0">
                    <div class="hero-image text-center">
                        <img src="assets/images/bug-tracking-illustration.svg" alt="Bug Tracking Illustration" class="img-fluid rounded shadow-lg" style="max-height: 400px;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Powerful Features</h2>
                <p class="lead text-muted">Everything you need to manage software issues effectively</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary text-white rounded-circle mb-3 mx-auto">
                                <i class="fas fa-bug fa-2x"></i>
                            </div>
                            <h4>Bug Reporting</h4>
                            <p class="text-muted">Submit detailed bug reports with screenshots and environment information to help developers reproduce and fix issues faster.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary text-white rounded-circle mb-3 mx-auto">
                                <i class="fas fa-tasks fa-2x"></i>
                            </div>
                            <h4>Issue Tracking</h4>
                            <p class="text-muted">Monitor bug status and resolution progress with a comprehensive dashboard and real-time updates on issue resolution.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary text-white rounded-circle mb-3 mx-auto">
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                            <h4>Communication</h4>
                            <p class="text-muted">Direct messaging between teams and customers ensures clear communication and faster resolution of issues.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="features.php" class="btn btn-outline-primary">Explore All Features</a>
            </div>
        </div>
    </section>

    <!-- User Roles Section -->
    <section class="user-roles-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Designed for Everyone</h2>
                <p class="lead text-muted">Tailored experiences for different user roles</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="role-icon bg-danger text-white rounded-circle me-3">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h4 class="mb-0">For Administrators</h4>
                            </div>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i> Manage staff and user accounts</li>
                                <li><i class="fas fa-check text-success me-2"></i> Create and oversee projects</li>
                                <li><i class="fas fa-check text-success me-2"></i> Assign bugs to appropriate team members</li>
                                <li><i class="fas fa-check text-success me-2"></i> Generate reports and analytics</li>
                                <li><i class="fas fa-check text-success me-2"></i> Monitor overall system performance</li>
                            </ul>
                            <?php if ($is_logged_in && $user_role === 'admin'): ?>
                                <a href="admin/dashboard.php" class="btn btn-sm btn-outline-danger mt-3">Go to Admin Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="role-icon bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <h4 class="mb-0">For Staff</h4>
                            </div>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i> View assigned bugs and tasks</li>
                                <li><i class="fas fa-check text-success me-2"></i> Update bug status and progress</li>
                                <li><i class="fas fa-check text-success me-2"></i> Communicate with customers</li>
                                <li><i class="fas fa-check text-success me-2"></i> Collaborate with team members</li>
                                <li><i class="fas fa-check text-success me-2"></i> Track resolution time and efficiency</li>
                            </ul>
                            <?php if ($is_logged_in && $user_role === 'staff'): ?>
                                <a href="staff/dashboard.php" class="btn btn-sm btn-outline-primary mt-3">Go to Staff Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="role-icon bg-success text-white rounded-circle me-3">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h4 class="mb-0">For Customers</h4>
                            </div>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i> Submit detailed bug reports</li>
                                <li><i class="fas fa-check text-success me-2"></i> Attach screenshots and files</li>
                                <li><i class="fas fa-check text-success me-2"></i> Track resolution progress</li>
                                <li><i class="fas fa-check text-success me-2"></i> Communicate with support team</li>
                                <li><i class="fas fa-check text-success me-2"></i> View history of reported issues</li>
                            </ul>
                            <?php if ($is_logged_in && $user_role === 'customer'): ?>
                                <a href="customer/dashboard.php" class="btn btn-sm btn-outline-success mt-3">Go to Customer Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">What Our Users Say</h2>
                <p class="lead text-muted">Trusted by development teams worldwide</p>
            </div>
            
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="testimonial-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <p class="testimonial-text">"This bug tracking system has transformed how our development team works. We've reduced resolution time by 40% and improved customer satisfaction significantly."</p>
                            <div class="d-flex align-items-center mt-3">
                                <div class="testimonial-avatar bg-primary text-white rounded-circle">JD</div>
                                <div class="ms-3">
                                    <h5 class="mb-0">John Doe</h5>
                                    <p class="text-muted mb-0">CTO, Tech Solutions Inc.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="testimonial-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <p class="testimonial-text">"As a project manager, I love how easy it is to track issues and assign them to team members. The reporting features help me keep stakeholders informed about our progress."</p>
                            <div class="d-flex align-items-center mt-3">
                                <div class="testimonial-avatar bg-success text-white rounded-circle">JS</div>
                                <div class="ms-3">
                                    <h5 class="mb-0">Jane Smith</h5>
                                    <p class="text-muted mb-0">Project Manager, Innovate Labs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="testimonial-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star-half-alt text-warning"></i>
                            </div>
                            <p class="testimonial-text">"The customer portal makes it so easy to report bugs and track their status. I appreciate the transparency and communication throughout the resolution process."</p>
                            <div class="d-flex align-items-center mt-3">
                                <div class="testimonial-avatar bg-info text-white rounded-circle">RJ</div>
                                <div class="ms-3">
                                    <h5 class="mb-0">Robert Johnson</h5>
                                    <p class="text-muted mb-0">Customer, Global Retail</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h2 class="fw-bold">Ready to streamline your bug tracking process?</h2>
                    <p class="lead mb-0">Join thousands of teams who trust our platform for their issue management needs.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if (!$is_logged_in): ?>
                        <a href="register.php" class="btn btn-light btn-lg px-4">Get Started Today</a>
                    <?php else: ?>
                        <a href="<?php echo $user_role; ?>/dashboard.php" class="btn btn-light btn-lg px-4">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <style>
        /* Custom styles for the homepage */
        .feature-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .role-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .testimonial-avatar {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .testimonial-text {
            font-style: italic;
            color: #555;
        }
    </style>
</body>
</html>