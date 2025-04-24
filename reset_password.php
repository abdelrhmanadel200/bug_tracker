<?php
// Include configuration
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize variables
$error_message = '';
$success_message = '';
$token = '';
$email = '';
$token_valid = false;

// Check if token is provided
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token
    $stmt = $conn->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $reset_data = $result->fetch_assoc();
        $email = $reset_data['email'];
        $expires_at = strtotime($reset_data['expires_at']);
        $used = $reset_data['used'];
        
        // Check if token is expired or already used
        if ($expires_at < time()) {
            $error_message = 'This password reset link has expired. Please request a new one.';
        } elseif ($used) {
            $error_message = 'This password reset link has already been used. Please request a new one.';
        } else {
            $token_valid = true;
        }
    } else {
        $error_message = 'Invalid password reset link. Please request a new one.';
    }
} else {
    $error_message = 'No reset token provided. Please request a password reset from the forgot password page.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    // Validate password
    if (empty($password)) {
        $error_message = 'Please enter a password.';
    } elseif (strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Get user ID for activity logging
        $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $user_stmt->bind_param("s", $email);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user_id = null;
        
        if ($user_result->num_rows === 1) {
            $user = $user_result->fetch_assoc();
            $user_id = $user['id'];
        }
        
        // Update the user's password
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
        $update_stmt->bind_param("ss", $hashed_password, $email);
        
        if ($update_stmt->execute()) {
            // Mark the token as used
            $mark_used_stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $mark_used_stmt->bind_param("s", $token);
            $mark_used_stmt->execute();
// After successful password reset
            $user_id = $user['id'];
            $user_name = $user['fullname'];
            log_password_reset_complete($user_id, $user_name);            // Log the password reset in activity_logs

            
            $success_message = 'Your password has been successfully reset. You can now login with your new password.';
            $token_valid = false; // Hide the form
        } else {
            $error_message = 'There was an error updating your password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BugTracker</title>
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
                                <h2 class="animate-slideInUp">Reset Password</h2>
                                <p class="text-muted animate-slideInUp delay-1">Create a new password for your account</p>
                            </div>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success animate-fadeIn">
                                    <?php echo htmlspecialchars($success_message); ?>
                                </div>
                                <div class="text-center mt-4">
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                                    </a>
                                </div>
                            <?php elseif (!empty($error_message)): ?>
                                <div class="alert alert-danger animate-fadeIn">
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                                <div class="text-center mt-4">
                                    <a href="forgot_password.php" class="btn btn-primary">
                                        <i class="fas fa-redo me-2"></i>Request New Reset Link
                                    </a>
                                </div>
                            <?php elseif ($token_valid): ?>
                                <form action="?token=<?php echo htmlspecialchars($token); ?>" method="post">
                                    <div class="mb-3 animate-slideInLeft delay-1">
                                        <label for="password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Password must be at least 8 characters long.</div>
                                    </div>
                                    <div class="mb-4 animate-slideInLeft delay-2">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                    <div class="d-grid animate-slideInUp delay-3">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save me-2"></i>Reset Password
                                        </button>
                                    </div>
                                </form>
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
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Password strength validation
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthText = document.getElementById('password-strength');
            
            if (!strengthText) return;
            
            if (password.length < 8) {
                strengthText.textContent = 'Weak';
                strengthText.className = 'text-danger';
            } else if (password.length < 12) {
                strengthText.textContent = 'Medium';
                strengthText.className = 'text-warning';
            } else {
                strengthText.textContent = 'Strong';
                strengthText.className = 'text-success';
            }
        });
    </script>
</body>
</html>