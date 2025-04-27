<?php
// Include PHPMailer
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Send an email using PHPMailer
 * 
 * @param string $to_email Recipient email
 * @param string $to_name Recipient name
 * @param string $subject Email subject
 * @param string $html_body HTML email body
 * @param string $text_body Plain text email body
 * @return bool Whether the email was sent successfully
 */
function send_email($to_email, $to_name, $subject, $html_body, $text_body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        $mail->AltBody = $text_body;
        
        return $mail->send();
    } catch (Exception $e) {
        // Log the error for administrators
        error_log("Email failed: {$mail->ErrorInfo}");
        return false;
    }
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
    
    $subject = 'Welcome to BugTracker!';
    
    $html_body = "
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
    
    $text_body = "Hello $name,\n\nThank you for registering with BugTracker. We're excited to have you on board!\n\nHere's what you can do with BugTracker:\n- Track and manage bugs efficiently\n- Collaborate with team members\n- Monitor project progress\n- Generate detailed reports\n\nReady to get started? Log in to your account: {$site_url}/login.php\n\nIf you have any questions or need assistance, please don't hesitate to contact our support team.\n\nBest regards,\nThe BugTracker Team";
    
    return send_email($email, $name, $subject, $html_body, $text_body);
}

/**
 * Send password reset email
 * 
 * @param string $name User's full name
 * @param string $email User's email address
 * @param string $token Reset token
 * @return bool Whether the email was sent successfully
 */
function send_password_reset_email($name, $email, $token) {
    // Get site URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $site_url = $protocol . '://' . $host;
    
    // Create reset link
    $reset_link = "$site_url/reset_password.php?token=$token";
    
    $subject = 'Reset Your BugTracker Password';
    
    $html_body = "
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
            <p>Hello $name,</p>
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
    
    $text_body = "Hello $name,\n\nWe received a request to reset your password for your BugTracker account. Click the link below to reset your password:\n\n$reset_link\n\nIf you didn't request a password reset, you can safely ignore this email.\n\nThis link will expire in 1 hour for security reasons.\n\nBugTracker - Manage your bugs efficiently";
    
    return send_email($email, $name, $subject, $html_body, $text_body);
}

/**
 * Send bug notification email to admins
 * 
 * @param array $admin_info Array of admin information (name, email)
 * @param array $bug_info Array of bug information
 * @param array $reporter_info Array of reporter information
 * @param array $project_info Array of project information
 * @return bool Whether the email was sent successfully
 */
function send_bug_notification_email($admin_info, $bug_info, $reporter_info, $project_info) {
    // Get site URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $site_url = $protocol . '://' . $host;
    
    // Create bug view link
    $bug_link = "$site_url/admin/view_bug.php?id={$bug_info['id']}";
    
    // Format priority for display
    $priority_class = '';
    switch ($bug_info['priority']) {
        case PRIORITY_LOW:
            $priority_class = 'color: #28a745;'; // Green
            break;
        case PRIORITY_MEDIUM:
            $priority_class = 'color: #17a2b8;'; // Blue
            break;
        case PRIORITY_HIGH:
            $priority_class = 'color: #ffc107;'; // Yellow
            break;
        case PRIORITY_CRITICAL:
            $priority_class = 'color: #dc3545;'; // Red
            break;
    }
    
    $subject = "New Bug Reported: {$bug_info['ticket_number']} - {$bug_info['title']}";
    
    $html_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h2 { color: #4361ee; }
            .btn { display: inline-block; padding: 10px 20px; background-color: #4361ee; color: #ffffff; text-decoration: none; border-radius: 5px; }
            .footer { margin-top: 30px; font-size: 12px; color: #777; }
            .bug-details { background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .bug-property { margin-bottom: 10px; }
            .priority { font-weight: bold; $priority_class }
            .description { background-color: #fff; padding: 10px; border-radius: 3px; border-left: 4px solid #4361ee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>New Bug Reported</h2>
            <p>Hello {$admin_info['name']},</p>
            <p>A new bug has been reported in the BugTracker system.</p>
            
            <div class='bug-details'>
                <div class='bug-property'><strong>Ticket Number:</strong> {$bug_info['ticket_number']}</div>
                <div class='bug-property'><strong>Title:</strong> {$bug_info['title']}</div>
                <div class='bug-property'><strong>Project:</strong> {$project_info['name']}</div>
                <div class='bug-property'><strong>Reported By:</strong> {$reporter_info['fullname']}</div>
                <div class='bug-property'><strong>Priority:</strong> <span class='priority'>" . ucfirst($bug_info['priority']) . "</span></div>
                <div class='bug-property'><strong>Status:</strong> " . ucfirst(str_replace('_', ' ', $bug_info['status'])) . "</div>
                <div class='bug-property'><strong>Reported On:</strong> " . date('F j, Y, g:i a', strtotime($bug_info['created_at'])) . "</div>
                
                <div class='bug-property'><strong>Description:</strong></div>
                <div class='description'>" . nl2br(htmlspecialchars($bug_info['description'])) . "</div>
                
                " . (!empty($bug_info['environment']) ? "<div class='bug-property'><strong>Environment:</strong> {$bug_info['environment']}</div>" : "") . "
            </div>
            
            <p>Please review this bug and take appropriate action.</p>
            
            <p><a href='$bug_link' class='btn' style='color:#fff;'>View Bug Details</a></p>
            
            <div class='footer'>
                <p>BugTracker - Manage your bugs efficiently</p>
                <p>This is an automated notification. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $text_body = "Hello {$admin_info['name']},\n\nA new bug has been reported in the BugTracker system.\n\n" .
                "Ticket Number: {$bug_info['ticket_number']}\n" .
                "Title: {$bug_info['title']}\n" .
                "Project: {$project_info['name']}\n" .
                "Reported By: {$reporter_info['fullname']}\n" .
                "Priority: " . ucfirst($bug_info['priority']) . "\n" .
                "Status: " . ucfirst(str_replace('_', ' ', $bug_info['status'])) . "\n" .
                "Reported On: " . date('F j, Y, g:i a', strtotime($bug_info['created_at'])) . "\n\n" .
                "Description:\n{$bug_info['description']}\n\n" .
                (!empty($bug_info['environment']) ? "Environment: {$bug_info['environment']}\n\n" : "") .
                "Please review this bug and take appropriate action.\n\n" .
                "View Bug Details: $bug_link\n\n" .
                "BugTracker - Manage your bugs efficiently\n" .
                "This is an automated notification. Please do not reply to this email.";
    
    return send_email($admin_info['email'], $admin_info['name'], $subject, $html_body, $text_body);
}
?>