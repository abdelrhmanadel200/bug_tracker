<?php
// Include configuration
require_once 'config/config.php';

// Check if user is logged in
if (is_logged_in()) {
    // Log action
    log_action('User logged out', $_SESSION['user_id']);
    
    // Clear session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        // Delete token from database
        $token = $_COOKIE['remember_token'];
        $sql = "DELETE FROM remember_tokens WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Delete cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

// Redirect to login page
redirect('login.php?success=You have been logged out successfully');
?>