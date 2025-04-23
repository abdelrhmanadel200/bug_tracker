<?php
// Include configuration
require_once 'config/config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="page-header bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-8 mx-auto text-center">
                    <h1 class="fw-bold">Powerful Features</h1>
                    <p class="lead">Discover all the tools and capabilities that make our bug tracking system exceptional</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Features Section -->
    <section class="main-features py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="feature-icon bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-bug"></i>
                                </div>
                                <h3>Comprehensive Bug Reporting</h3>
                            </div>
                            <p>Our detailed bug reporting system allows users to submit issues with all the necessary information developers need to reproduce and fix problems quickly:</p>
                            <ul>
                                <li>Customizable bug report templates</li>
                                <li>Screenshot and file attachment support</li>
                                <li>Environment details capture (browser, OS, device)</li>
                                <li>Severity and priority classification</li>
                                <li>Automatic ticket number generation</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="feature-icon bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <h3>Advanced Issue Tracking</h3>
                            </div>
                            <p>Keep track of all bugs and their status with our powerful tracking system:</p>
                            <ul>
                                <li>Real-time status updates</li>
                                <li>Customizable workflow states</li>
                                <li>Filtering and sorting capabilities</li>
                                <li>Detailed bug history and audit trail</li>
                                <li>Due date and milestone tracking</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="feature-icon bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3>Team Collaboration</h3>
                            </div>
                            <p>Enhance team productivity with built-in collaboration tools:</p>
                            <ul>
                                <li>Role-based access control</li>
                                <li>Bug assignment and reassignment</li>
                                <li>Comment threads on each issue</li>
                                <li>@mentions for team members</li>
                                <li>Email notifications for updates</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="feature-icon bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <h3>Project Management</h3>
                            </div>
                            <p>Organize bugs by projects and keep everything structured:</p>
                            <ul>
                                <li>Multiple project support</li>
                                <li>Project-specific bug tracking</li>
                                <li>Custom fields per project</li>
                                <li>Project status monitoring</li>
                                <li>Resource allocation across projects</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Additional Features Section -->
    <section class="additional-features py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">More Powerful Features</h2>
                <p class="lead text-muted">Discover all the tools that make our platform stand out</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-chart-bar fa-3x text-primary mb-3"></i>
                            <h4>Analytics & Reporting</h4>
                            <p>Generate insightful reports on bug trends, resolution times, and team performance to identify areas for improvement.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                            <h4>Mobile Responsive</h4>
                            <p>Access the bug tracking system from any device with our fully responsive design that works on desktops, tablets, and smartphones.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                            <h4>Security</h4>
                            <p>Rest easy knowing your data is protected with our robust security measures, including encryption and secure authentication.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-bell fa-3x text-primary mb-3"></i>
                            <h4>Notifications</h4>
                            <p>Stay informed with customizable email and in-app notifications for bug updates, comments, and status changes.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-search fa-3x text-primary mb-3"></i>
                            <h4>Advanced Search</h4>
                            <p>Quickly find bugs with our powerful search functionality that includes filters, tags, and full-text search capabilities.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 text-center">
                            <i class="fas fa-cogs fa-3x text-primary mb-3"></i>
                            <h4>Customization</h4>
                            <p>Tailor the system to your needs with customizable fields, workflows, and user interface options.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Comparison -->
    <section class="feature-comparison py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Plan Comparison</h2>
                <p class="lead text-muted">Choose the right plan for your team</p>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Features</th>
                            <th class="text-center">Free</th>
                            <th class="text-center">Professional</th>
                            <th class="text-center">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Bug Reporting</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Issue Tracking</td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>File Attachments</td>
                            <td class="text-center">Up to 5MB</td>
                            <td class="text-center">Up to 50MB</td>
                            <td class="text-center">Unlimited</td>
                        </tr>
                        <tr>
                            <td>Team Members</td>
                            <td class="text-center">Up to 3</td>
                            <td class="text-center">Up to 15</td>
                            <td class="text-center">Unlimited</td>
                        </tr>
                        <tr>
                            <td>Projects</td>
                            <td class="text-center">1</td>
                            <td class="text-center">10</td>
                            <td class="text-center">Unlimited</td>
                        </tr>
                        <tr>
                            <td>Advanced Reporting</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>API Access</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Custom Fields</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Priority Support</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Single Sign-On (SSO)</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td>Custom Branding</td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                            <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td class="text-center">
                                <div class="fw-bold mb-2">Free</div>
                                <div class="h4 mb-3">$0</div>
                                <a href="register.php" class="btn btn-outline-primary btn-sm">Sign Up</a>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold mb-2">Professional</div>
                                <div class="h4 mb-3">$19<small>/month</small></div>
                                <a href="contact.php" class="btn btn-primary btn-sm">Contact Sales</a>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold mb-2">Enterprise</div>
                                <div class="h4 mb-3">$49<small>/month</small></div>
                                <a href="contact.php" class="btn btn-primary btn-sm">Contact Sales</a>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h2 class="fw-bold">Ready to get started?</h2>
                    <p class="lead mb-0">Join thousands of teams who trust our platform for their bug tracking needs.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if (!$is_logged_in): ?>
                        <a href="register.php" class="btn btn-light btn-lg px-4">Sign Up Now</a>
                    <?php else: ?>
                        <a href="contact.php" class="btn btn-light btn-lg px-4">Contact Us</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <style>
        /* Custom styles for the features page */
        .feature-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .page-header {
            background-color: var(--bs-primary);
            background-image: linear-gradient(135deg, var(--bs-primary) 0%, #0056b3 100%);
        }
    </style>
</body>
</html>