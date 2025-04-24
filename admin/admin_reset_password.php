<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_logged_in() || $_SESSION['user_role'] !== ROLE_ADMIN) {
    redirect('../login.php');
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    redirect('users.php');
}

// Get user details
$user_query = "SELECT id, fullname, email, role, status FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    redirect('users.php');
}

$user = $user_result->fetch_assoc();

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        // Update the user's password
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            // Log the password reset in activity_logs


            $admin_id = $_SESSION['user_id'];
            $admin_name = $_SESSION['user_name'];
            $user_info = "{$user['fullname']}";
            log_user_deletion($admin_id, $user_info, $admin_name);

            
            
            $admin_id = $_SESSION['user_id'];
            $action = "admin {$admin_name} Reset password for user {$user_info} ";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())");
            $log_stmt->bind_param("is", $admin_id, $action);
            $log_stmt->execute();
            
            $success_message = "Password for {$user['fullname']} has been successfully reset.";
        } else {
            $error_message = 'There was an error updating the password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password for <?php echo htmlspecialchars($user['fullname']); ?> - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Reset Password for <?php echo htmlspecialchars($user['fullname']); ?></h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h6 class="mb-1">User: <?php echo htmlspecialchars($user['fullname']); ?></h6>
                                <p class="text-muted mb-0">Email: <?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'primary' : 'success'); ?> me-1">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                                <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'inactive' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                            <div class="text-center mt-4">
                                <a href="view_user.php?id=<?php echo $user_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-user me-2"></i>Return to User Profile
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-3">
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
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="generate_password">
                                        <label class="form-check-label" for="generate_password">
                                            Generate a strong password
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-4" id="password_strength_container">
                                    <label class="form-label">Password Strength</label>
                                    <div class="progress">
                                        <div id="password_strength_bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small id="password_strength_text" class="form-text">Enter a password</small>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="view_user.php?id=<?php echo $user_id; ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-1"></i> Reset Password
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const generatePasswordCheckbox = document.getElementById('generate_password');
            const passwordStrengthBar = document.getElementById('password_strength_bar');
            const passwordStrengthText = document.getElementById('password_strength_text');
            
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    confirmPasswordInput.setAttribute('type', type);
                    
                    // Toggle eye icon
                    const icon = this.querySelector('i');
                    if (type === 'text') {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }
            
            // Generate random password
            if (generatePasswordCheckbox) {
                generatePasswordCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        const password = generateStrongPassword();
                        passwordInput.value = password;
                        confirmPasswordInput.value = password;
                        updatePasswordStrength(password);
                    } else {
                        passwordInput.value = '';
                        confirmPasswordInput.value = '';
                        updatePasswordStrength('');
                    }
                });
            }
            
            // Password strength meter
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    updatePasswordStrength(this.value);
                });
            }
            
            // Function to generate a strong password
            function generateStrongPassword() {
                const length = 12;
                const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
                let password = "";
                
                // Ensure at least one character from each category
                password += getRandomChar("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
                password += getRandomChar("abcdefghijklmnopqrstuvwxyz");
                password += getRandomChar("0123456789");
                password += getRandomChar("!@#$%^&*()_+");
                
                // Fill the rest with random characters
                for (let i = password.length; i < length; i++) {
                    password += charset.charAt(Math.floor(Math.random() * charset.length));
                }
                
                // Shuffle the password
                return password.split('').sort(() => 0.5 - Math.random()).join('');
            }
            
            function getRandomChar(charset) {
                return charset.charAt(Math.floor(Math.random() * charset.length));
            }
            
            // Function to update password strength indicator
            function updatePasswordStrength(password) {
                let strength = 0;
                let feedback = '';
                
                if (password.length === 0) {
                    strength = 0;
                    feedback = 'Enter a password';
                } else if (password.length < 8) {
                    strength = 25;
                    feedback = 'Weak: Too short';
                } else {
                    // Check for various character types
                    const hasLower = /[a-z]/.test(password);
                    const hasUpper = /[A-Z]/.test(password);
                    const hasNumber = /[0-9]/.test(password);
                    const hasSpecial = /[^a-zA-Z0-9]/.test(password);
                    
                    const typesCount = [hasLower, hasUpper, hasNumber, hasSpecial].filter(Boolean).length;
                    
                    if (password.length >= 12 && typesCount >= 3) {
                        strength = 100;
                        feedback = 'Strong';
                    } else if (password.length >= 10 && typesCount >= 2) {
                        strength = 75;
                        feedback = 'Good';
                    } else {
                        strength = 50;
                        feedback = 'Moderate';
                    }
                }
                
                // Update UI
                passwordStrengthBar.style.width = strength + '%';
                passwordStrengthText.textContent = feedback;
                
                // Update color based on strength
                if (strength < 50) {
                    passwordStrengthBar.className = 'progress-bar bg-danger';
                    passwordStrengthText.className = 'form-text text-danger';
                } else if (strength < 75) {
                    passwordStrengthBar.className = 'progress-bar bg-warning';
                    passwordStrengthText.className = 'form-text text-warning';
                } else {
                    passwordStrengthBar.className = 'progress-bar bg-success';
                    passwordStrengthText.className = 'form-text text-success';
                }
            }
        });
    </script>
</body>
</html>