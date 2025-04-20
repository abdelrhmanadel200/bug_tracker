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
    <title>About Us - BugTracker</title>
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
                    <h1 class="fw-bold">About BugTracker</h1>
                    <p class="lead">Learn about our mission, team, and the story behind our bug tracking system</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->

    <section class="our-story py-5">
  <div class="container">
    <div class="row">
      <div class="col-lg-6 mx-auto text-center">
      <h2 class="fw-bold mb-4">Our Story</h2>
                    <p class="lead">BugTracker was born out of necessity and a passion for improving software development processes.</p>
                    <p>In 2018, a team of developers faced the challenge of managing bugs across multiple projects. Frustrated with existing solutions that were either too complex or too simple, they decided to build their own bug tracking system that would strike the perfect balance between functionality and usability.</p>
                    <p>What started as an internal tool quickly gained attention from other development teams who saw its potential. After refining the system based on feedback from early adopters, BugTracker was officially launched to the public in 2020.</p>
                    <p>Today, BugTracker is used by thousands of development teams worldwide, from small startups to large enterprises, all benefiting from our streamlined approach to bug tracking and issue management.</p>
      </div>
    </div>
  </div>
</section>


    <!-- Our Mission Section -->
    <section class="our-mission py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="fw-bold mb-4">Our Mission</h2>
                    <p class="lead">To simplify bug tracking and empower development teams to deliver higher quality software.</p>
                    <p>We believe that effective bug tracking should be accessible to everyone, regardless of team size or technical expertise. Our mission is to provide a powerful yet intuitive platform that helps teams identify, track, and resolve software issues efficiently.</p>
                    <div class="row mt-5">
                        <div class="col-md-4 mb-4">
                            <div class="value-card p-4 bg-white rounded shadow-sm text-center h-100">
                                <i class="fas fa-lightbulb fa-3x text-primary mb-3"></i>
                                <h4>Simplicity</h4>
                                <p>We believe in keeping things simple and intuitive, focusing on what matters most.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="value-card p-4 bg-white rounded shadow-sm text-center h-100">
                                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                <h4>Collaboration</h4>
                                <p>We foster teamwork and communication between developers, testers, and customers.</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="value-card p-4 bg-white rounded shadow-sm text-center h-100">
                                <i class="fas fa-rocket fa-3x text-primary mb-3"></i>
                                <h4>Innovation</h4>
                                <p>We continuously improve our platform with new features and capabilities.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Meet Our Team</h2>
                <p class="lead text-muted">The talented people behind BugTracker</p>
            </div>
            
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-member card border-0 shadow-sm h-100">
                        <img src="assets/images/team-member-1.jpg" class="card-img-top" alt="Team Member">
                        <div class="card-body text-center">
                            <h5 class="card-title">Michael Johnson</h5>
                            <p class="card-text text-muted">CEO & Co-Founder</p>
                            <div class="social-icons">
                                <a href="#" class="text-decoration-none me-2"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="text-decoration-none me-2"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="text-decoration-none"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-member card border-0 shadow-sm h-100">
                        <img src="assets/images/team-member-2.jpg" class="card-img-top" alt="Team Member">
                        <div class="card-body text-center">
                            <h5 class="card-title">Sarah Williams</h5>
                            <p class="card-text text-muted">CTO & Co-Founder</p>
                            <div class="social-icons">
                                <a href="#" class="text-decoration-none me-2"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="text-decoration-none me-2"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="text-decoration-none"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-member card border-0 shadow-sm h-100">
                        <img src="assets/images/team-member-3.jpg" class="card-img-top" alt="Team Member">
                        <div class="card-body text-center">
                            <h5 class="card-title">David Chen</h5>
                            <p class="card-text text-muted">Lead Developer</p>
                            <div class="social-icons">
                                <a href="#" class="text-decoration-none me-2"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="text-decoration-none me-2"><i class="fab fa-github"></i></a>
                                <a href="#" class="text-decoration-none"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="team-member card border-0 shadow-sm h-100">
                        <img src="assets/images/team-member-4.jpg" class="card-img-top" alt="Team Member">
                        <div class="card-body text-center">
                            <h5 class="card-title">Emily Rodriguez</h5>
                            <p class="card-text text-muted">UX/UI Designer</p>
                            <div class="social-icons">
                                <a href="#" class="text-decoration-none me-2"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="text-decoration-none me-2"><i class="fab fa-dribbble"></i></a>
                                <a href="#" class="text-decoration-none"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Achievements Section -->
    <section class="achievements-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Our Achievements</h2>
                <p class="lead text-muted">Milestones we've reached along the way</p>
            </div>
            
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="achievement-card p-4 bg-white rounded shadow-sm h-100">
                        <div class="achievement-number h1 fw-bold text-primary">5000+</div>
                        <div class="achievement-label">Active Users</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="achievement-card p-4 bg-white rounded shadow-sm h-100">
                        <div class="achievement-number h1 fw-bold text-primary">1M+</div>
                        <div class="achievement-label">Bugs Tracked</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="achievement-card p-4 bg-white rounded shadow-sm h-100">
                        <div class="achievement-number h1 fw-bold text-primary">50+</div>
                        <div class="achievement-label">Countries</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="achievement-card p-4 bg-white rounded shadow-sm h-100">
                        <div class="achievement-number h1 fw-bold text-primary">98%</div>
                        <div class="achievement-label">Customer Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Our Journey</h2>
                <p class="lead text-muted">Key milestones in our company history</p>
            </div>
            
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-badge bg-primary">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="timeline-panel card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">2018</h5>
                            <p class="card-text">BugTracker was conceived as an internal tool by a team of developers frustrated with existing bug tracking solutions.</p>
                        </div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-badge bg-primary">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="timeline-panel card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">2020</h5>
                            <p class="card-text">Official launch of BugTracker to the public after extensive testing and refinement.</p>
                        </div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-badge bg-primary">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="timeline-panel card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">2021</h5>
                            <p class="card-text">Reached 1,000 active users and won "Best New Developer Tool" award.</p>
                        </div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-badge bg-primary">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="timeline-panel card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">2022</h5>
                            <p class="card-text">Expanded to international markets and launched enterprise-level features.</p>
                        </div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-badge bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="timeline-panel card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">2023</h5>
                            <p class="card-text">Surpassed 5,000 active users and expanded our team to 20 members.</p>
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
                    <h2 class="fw-bold">Want to join our team?</h2>
                    <p class="lead mb-0">We're always looking for talented individuals to help us grow.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="contact.php" class="btn btn-light btn-lg px-4">Contact Us</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <style>
        /* Custom styles for the about page */
        .page-header {
            background-color: var(--bs-primary);
            background-image: linear-gradient(135deg, var(--bs-primary) 0%, #0056b3 100%);
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 3px;
            margin-left: -1.5px;
            background-color: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }
        
        .timeline-badge {
            position: absolute;
            top: 16px;
            left: 50%;
            width: 40px;
            height: 40px;
            margin-left: -20px;
            border-radius: 50%;
            text-align: center;
            color: #fff;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .timeline-panel {
            width: 45%;
            float: left;
            border-radius: 8px;
        }
        
        .timeline-item:nth-child(even) .timeline-panel {
            float: right;
        }
        
        .timeline-item:after {
            content: "";
            display: table;
            clear: both;
        }
        
        @media (max-width: 767px) {
            .timeline:before {
                left: 40px;
            }
            
            .timeline-badge {
                left: 40px;
                margin-left: 0;
            }
            
            .timeline-panel {
                width: calc(100% - 90px);
                float: right;
            }
        }
    </style>
</body>
</html>