<?php
// Include configuration
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Include PHPMailer
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize email
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Check if email exists in the database
        $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Don't reveal that the email doesn't exist (security best practice)
            $success_message = 'If your email exists in our system, you will receive a password reset link shortly.';
        } else {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $user_name = $user['fullname'];
            
            // Generate a unique token
            $token = bin2hex(random_bytes(32)); // 64 characters
            
            // Set expiration time (1 hour from now)
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete any existing tokens for this email
            $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $delete_stmt->bind_param("s", $email);
            $delete_stmt->execute();
            
            // Store the token in the database
            $insert_stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $email, $token, $expires_at);
            
            if ($insert_stmt->execute()) {
                // Define the reset link
                // Use absolute URL since ROOT_URL might not be defined
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $reset_link = $protocol . '://' . $host . '/reset_password.php?token=' . $token;
                
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'booda9963@gmail.com'; // Change to your email
                    $mail->Password = 'bsrl cmwe ckwe wcxd'; // Change to your app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
        
                    // Recipients
                    $mail->setFrom('noreply@bugtracker.com', 'BugTracker');
                    $mail->addAddress($email, $user_name);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Your BugTracker Password';
                    
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
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <h2>Reset Your Password</h2>
                            <p>Hello $user_name,</p>
                            <p>We received a request to reset your password for your BugTracker account. Click the button below to reset your password:</p>
                            <p><a href='$reset_link' class='btn' style='color:#fff;'>Reset Password</a></p>
                            <p>If you didn't request a password reset, you can safely ignore this email.</p>
                            <p>This link will expire in 1 hour for security reasons.</p>
                            <p>If the button above doesn't work, copy and paste this URL into your browser:</p>
                            <p>$reset_link</p>
                            <div class='footer'>
                                <p>BugTracker - Manage your bugs efficiently</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    $mail->AltBody = "Hello $user_name,\n\nWe received a request to reset your password for your BugTracker account. Click the link below to reset your password:\n\n$reset_link\n\nIf you didn't request a password reset, you can safely ignore this email.\n\nThis link will expire in 1 hour for security reasons.\n\nBugTracker - Manage your bugs efficiently";
                    
                    $mail->send();
                    
                    // Log the activity
                    $action = "Requested password reset";
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())");
                    $log_stmt->bind_param("is", $user_id, $action);
                    $log_stmt->execute();
                    
                    $success_message = 'If your email exists in our system, you will receive a password reset link shortly.';
                } catch (Exception $e) {
                    $error_message = "There was an error sending the password reset email. Please try again later.";
                    // Log the error for administrators
                    error_log("Password reset email failed: {$mail->ErrorInfo}");
                }
            } else {
                $error_message = "There was an error processing your request. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-lg border-0 overflow-hidden animate-fadeIn">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <a href="index.php" class="d-inline-block mb-3">
                                    <i class="fas fa-bug fa-3x text-primary"></i>
                                </a>
                                <h2 class="animate-slideInUp">Forgot Password</h2>
                                <p class="text-muted animate-slideInUp delay-1">Enter your email to receive a password reset link</p>
                            </div>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success animate-fadeIn">
                                    <?php echo htmlspecialchars($success_message); ?>
                                </div>
                                <div class="text-center mt-4">
                                    <p>Return to <a href="login.php" class="text-decoration-none text-primary">Login</a></p>
                                </div>
                            <?php else: ?>
                                <?php if (!empty($error_message)): ?>
                                    <div class="alert alert-danger animate-fadeIn">
                                        <?php echo htmlspecialchars($error_message); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form action="" method="post">
                                    <div class="mb-4 animate-slideInLeft delay-1">
                                        <label for="email" class="form-label">Email address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>
                                    <div class="d-grid animate-slideInUp delay-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                                        </button>
                                    </div>
                                </form>
                                
                                <hr class="my-4">
                                
                                <div class="text-center animate-fadeIn delay-3">
                                    <p>Remember your password? <a href="login.php" class="text-decoration-none text-primary">Login</a></p>
                                    <p>Don't have an account? <a href="register.php" class="text-decoration-none text-primary">Register</a></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Animated Bubbles -->
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>