<?php
// Include configuration
require_once '../config/config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate form data
    if (empty($email) || empty($password)) {
        redirect('../login.php?error=Please fill in all fields');
    }
    
    // Check if user exists
    $sql = "SELECT id, fullname, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Set remember me cookie
            if ($remember) {
                $token = generate_random_string(32);
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $sql = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $expires_at = date('Y-m-d H:i:s', $expiry);
                $stmt->bind_param("iss", $user['id'], $token, $expires_at);
                $stmt->execute();
                
                // Set cookie
                setcookie('remember_token', $token, $expiry, '/', '', false, true);
            }
            
            // Log action
            log_action('User logged in', $user['id']);
            
            // Redirect based on role
            switch ($user['role']) {
                case ROLE_ADMIN:
                    redirect('../admin/dashboard.php');
                    break;
                case ROLE_STAFF:
                    redirect('../staff/dashboard.php');
                    break;
                case ROLE_CUSTOMER:
                    redirect('../customer/dashboard.php');
                    break;
                default:
                    redirect('../index.php');
                    break;
            }
        } else {
            redirect('../login.php?error=Invalid email or password');
        }
    } else {
        redirect('../login.php?error=Invalid email or password');
    }
} else {
    redirect('../login.php');
}
?>