<?php
// Include configuration
require_once '../config/config.php';

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
?>