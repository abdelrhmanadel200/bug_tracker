<?php
// Include configuration
require_once 'config/config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$user_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // In a real application, you would send an email here
        // For now, we'll just simulate success
        $success_message = 'Thank you for your message! We will get back to you soon.';
        
        // Clear form fields after successful submission
        $name = $email = $subject = $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - BugTracker</title>
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
                    <h1 class="fw-bold">Contact Us</h1>
                    <p class="lead">Get in touch with our team for support, feedback, or inquiries</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mb-5 mb-lg-0">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="fw-bold mb-4">Send Us a Message</h2>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($success_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($is_logged_in ? $user_name : ($name ?? '')); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($is_logged_in ? $user_email : ($email ?? '')); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="6" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="contact-info card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h3 class="fw-bold mb-4">Contact Information</h3>
                            <div class="d-flex mb-3">
                                <div class="contact-icon bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h5>Address</h5>
                                    <p class="mb-0">123 Tech Street, Suite 456<br>San Francisco, CA 94107</p>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="contact-icon bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <h5>Phone</h5>
                                    <p class="mb-0"><a href="tel:+14155552671" class="text-decoration-none">(415) 555-2671</a></p>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="contact-icon bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h5>Email</h5>
                                    <p class="mb-0"><a href="mailto:support@bugtracker.com" class="text-decoration-none">support@bugtracker.com</a></p>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="contact-icon bg-primary text-white rounded-circle me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <h5>Business Hours</h5>
                                    <p class="mb-0">Monday - Friday: 9:00 AM - 5:00 PM<br>Saturday & Sunday: Closed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-media card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="fw-bold mb-4">Connect With Us</h3>
                            <div class="d-flex justify-content-between">
                                <a href="#" class="social-icon bg-primary text-white rounded-circle">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-icon bg-info text-white rounded-circle">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-icon bg-danger text-white rounded-circle">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="social-icon bg-dark text-white rounded-circle">
                                    <i class="fab fa-github"></i>
                                </a>
                                <a href="#" class="social-icon bg-primary text-white rounded-circle">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Find Us</h2>
                <p class="lead text-muted">Visit our office</p>
            </div>
            
            <div class="map-container rounded shadow">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.0968143067466!2d-122.40058568468176!3d37.78532701975535!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80858085d2d8fb5d%3A0x4f5e8afd35bace4f!2sSan%20Francisco%2C%20CA%2094107!5e0!3m2!1sen!2sus!4v1617978546234!5m2!1sen!2sus" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Frequently Asked Questions</h2>
                <p class="lead text-muted">Find answers to common questions</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How do I get started with BugTracker?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Getting started is easy! Simply <a href="register.php">create an account</a>, verify your email, and you'll have immediate access to our platform. You can then create your first project and start tracking bugs right away.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Is there a free trial available?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! We offer a free tier that allows you to use basic features with up to 3 team members and 1 project. This is perfect for small teams or individuals who want to try out our platform before upgrading to a paid plan.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    How can I upgrade my plan?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You can upgrade your plan at any time from your account settings. Simply navigate to the "Billing" section, select your desired plan, and follow the payment instructions. Your account will be upgraded immediately after payment is processed.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Do you offer custom enterprise solutions?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, we offer custom enterprise solutions for larger organizations with specific requirements. Please <a href="contact.php">contact our sales team</a> to discuss your needs and get a tailored quote.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item border-0 shadow-sm">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    How can I get support?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We offer support through multiple channels. You can email us at support@bugtracker.com, use the contact form on this page, or access our knowledge base and community forums from your dashboard. Enterprise customers also receive priority support with dedicated account managers.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <style>
        /* Custom styles for the contact page */
        .page-header {
            background-color: var(--bs-primary);
            background-image: linear-gradient(135deg, var(--bs-primary) 0%, #0056b3 100%);
        }
        
        .contact-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            transform: translateY(-3px);
        }
        
        .map-container {
            overflow: hidden;
        }
    </style>
</body>
</html>