<?php
// Include configuration
require_once '../config/config.php';
require_once '../config/database.php';
require_once 'functions.php';

// Include PHPMailer
require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullname = sanitize_input($_POST['fullname']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = sanitize_input($_POST['user_type']);
    $terms = isset($_POST['terms']) ? true : false;
    
    // Validate form data
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        redirect('../register.php?error=Please fill in all fields');
    }
    
    if ($password !== $confirm_password) {
        redirect('../register.php?error=Passwords do not match');
    }
    
    if (strlen($password) < 8) {
        redirect('../register.php?error=Password must be at least 8 characters long');
    }
    
    if (!$terms) {
        redirect('../register.php?error=You must agree to the terms and conditions');
    }
    
    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        redirect('../register.php?error=Email already exists');
    }
    
    // Only allow customer registration
    if ($user_type !== ROLE_CUSTOMER) {
        $user_type = ROLE_CUSTOMER;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user into database
    $sql = "INSERT INTO users (fullname, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $user_type);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Send welcome email
        $email_sent = send_welcome_email($fullname, $email);
        
        // Log action
        log_action('User registered', $user_id);
        
        // Redirect to login page
        redirect('../login.php?success=Registration successful! You can now login.');
    } else {
        redirect('../register.php?error=Registration failed. Please try again.');
    }
} else {
    redirect('../register.php');
}

/**
 * Send welcome email to newly registered user
 * 
 * @param string $name User's full name
 * @param string $email User's email address
 * @return bool Whether the email was sent successfully
 */
function send_welcome_email($name, $email) {
    // Get site URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $site_url = $protocol . '://' . $host;
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Use the same SMTP settings as in forgot_password.php
        $mail->SMTPAuth = true;
        $mail->Username = 'booda9963@gmail.com'; // Use your email
        $mail->Password = 'bsrl cmwe ckwe wcxd'; // Use your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('noreply@bugtracker.com', 'BugTracker');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to BugTracker!';
        
        // Email body
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                h2 { color: #4361ee; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #4361ee; color: #ffffff; text-decoration: none; border-radius: 5px; }
                .footer { margin-top: 30px; font-size: 12px; color: #777; }
                .features { background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .feature-item { margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Welcome to BugTracker!</h2>
                <p>Hello $name,</p>
                <p>Thank you for registering with BugTracker. We're excited to have you on board!</p>
                
                <div class='features'>
                    <h3>Here's what you can do with BugTracker:</h3>
                    <div class='feature-item'>✅ Track and manage bugs efficiently</div>
                    <div class='feature-item'>✅ Collaborate with team members</div>
                    <div class='feature-item'>✅ Monitor project progress</div>
                    <div class='feature-item'>✅ Generate detailed reports</div>
                </div>
                
                <p>Ready to get started? Click the button below to log in to your account:</p>
                
                <p><a href='{$site_url}/login.php' class='btn' style='color:#fff;'>Log In to Your Account</a></p>
                
                <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                
                <p>Best regards,<br>The BugTracker Team</p>
                
                <div class='footer'>
                    <p>BugTracker - Manage your bugs efficiently</p>
                    <p>This email was sent to $email</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Hello $name,\n\nThank you for registering with BugTracker. We're excited to have you on board!\n\nHere's what you can do with BugTracker:\n- Track and manage bugs efficiently\n- Collaborate with team members\n- Monitor project progress\n- Generate detailed reports\n\nReady to get started? Log in to your account: {$site_url}/login.php\n\nIf you have any questions or need assistance, please don't hesitate to contact our support team.\n\nBest regards,\nThe BugTracker Team";
        
        return $mail->send();
    } catch (Exception $e) {
        // Log the error for administrators
        error_log("Welcome email failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>