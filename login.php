<?php
// Include configuration
require_once 'config/config.php';

// Check if user is already logged in

if (is_logged_in() && is_admin()) {
    redirect('admin/dashboard.php');
}
else if (is_logged_in() && is_staff()) {
    redirect('staff/dashboard.php');
}
else if (is_logged_in() && is_customer()) {
    redirect('customer/dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="auth-form bg-white shadow-sm rounded p-4 p-md-5">
                    <h2 class="text-center mb-4">Login to Your Account</h2>
                    
                    <?php
                    // Display error message if any
                    if (isset($_GET['error'])) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
                    }
                    
                    // Display success message if any
                    if (isset($_GET['success'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
                    }
                    ?>
                    
                    <form action="includes/login_process.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Login</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p>Don't have an account? <a href="register.php" class="text-decoration-none">Register now</a></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
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
    </script>
</body>
</html>