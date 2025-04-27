<?php
/**
 * Helper functions for the Bug Tracker application
 */

/**
 * Sanitize user input
 * 
 * @param string $data The input to sanitize
 * @return string The sanitized input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Redirect to a specific page
 * 
 * @param string $location The URL to redirect to
 * @return void
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has a specific role
 * 
 * @param string $role The role to check
 * @return bool True if user has the role, false otherwise
 */
function has_role($role) {
    if (!is_logged_in()) {
        return false;
    }
    
    return $_SESSION['user_role'] === $role;
}

/**
 * Check if user is an admin
 * 
 * @return bool True if user is an admin, false otherwise
 */
function is_admin() {
    return has_role(ROLE_ADMIN);
}

/**
 * Check if user is a staff member
 * 
 * @return bool True if user is a staff member, false otherwise
 */
function is_staff() {
    return has_role(ROLE_STAFF);
}

/**
 * Check if user is a customer
 * 
 * @return bool True if user is a customer, false otherwise
 */
function is_customer() {
    return has_role(ROLE_CUSTOMER);
}

/**
 * Generate a random string
 * 
 * @param int $length The length of the string
 * @return string The random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Format date and time
 * 
 * @param string $datetime The date and time to format
 * @return string The formatted date and time
 */
function format_datetime($datetime) {
    $date = new DateTime($datetime);
    return $date->format('M j, Y g:i A');
}

/**
 * Get status badge HTML
 * 
 * @param string $status The bug status
 * @return string The HTML for the status badge
 */
function get_status_badge($status) {
    $badge_class = '';
    $status_text = ucfirst($status);
    
    switch ($status) {
        case STATUS_NEW:
            $badge_class = 'bg-info';
            break;
        case STATUS_ASSIGNED:
            $badge_class = 'bg-primary';
            break;
        case STATUS_IN_PROGRESS:
            $badge_class = 'bg-warning';
            break;
        case STATUS_RESOLVED:
            $badge_class = 'bg-success';
            break;
        case STATUS_CLOSED:
            $badge_class = 'bg-secondary';
            break;
        case STATUS_REOPENED:
            $badge_class = 'bg-danger';
            break;
        default:
            $badge_class = 'bg-light text-dark';
            break;
    }
    
    return '<span class="badge ' . $badge_class . ' status-badge">' . $status_text . '</span>';
}

/**
 * Get priority badge HTML
 * 
 * @param string $priority The bug priority
 * @return string The HTML for the priority badge
 */
function get_priority_badge($priority) {
    $badge_class = '';
    $priority_text = ucfirst($priority);
    
    switch ($priority) {
        case PRIORITY_LOW:
            $badge_class = 'bg-success';
            break;
        case PRIORITY_MEDIUM:
            $badge_class = 'bg-info';
            break;
        case PRIORITY_HIGH:
            $badge_class = 'bg-warning text-dark';
            break;
        case PRIORITY_CRITICAL:
            $badge_class = 'bg-danger';
            break;
        default:
            $badge_class = 'bg-light text-dark';
            break;
    }
    
    return '<span class="badge ' . $badge_class . ' status-badge">' . $priority_text . '</span>';
}

/**
 * Upload a file
 * 
 * @param array $file The file to upload ($_FILES['file'])
 * @param string $destination The destination directory
 * @return string|bool The file path if successful, false otherwise
 */
function upload_file($file, $destination) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Check file extension
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Generate a unique filename
    $new_filename = generate_random_string() . '.' . $extension;
    $upload_path = $destination . $new_filename;
    
    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $upload_path;
    }
    
    return false;
}

/**

 * Log an action in the activity_logs table
 * 
 * @param string $action The action to log
 * @param int $user_id The user ID
 * @param int|null $bug_id The bug ID (optional)
 * @param string|null $user_name The user name (optional)
 * @return bool True if successful, false otherwise
 */
function log_action($action, $user_id, $bug_id = null, $user_name = null) {
    global $conn;
    
    // If user_name is not provided but we have user_id, try to fetch the name from database
    if ($user_name === null && $user_id > 0) {
        // This is optional - only fetch name if not provided
        $user_stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ? LIMIT 1");
        if ($user_stmt) {
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            if ($user_result->num_rows === 1) {
                $user = $user_result->fetch_assoc();
                $user_name = $user['fullname'];
            }
        }
    }
    
    // Insert into activity_logs with user_name
    $sql = "INSERT INTO activity_logs (user_id, user_name, bug_id, action, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("isis", $user_id, $user_name, $bug_id, $action);
        return $stmt->execute();
    }
    
    return false;
}

/**
 * Log user registration activity
 * 
 * @param int $user_id The user ID
 * @param string|null $user_name The user name (optional)
 * @return bool True if successful, false otherwise
 */
function log_registration($user_id, $user_name = null) {
    return log_action('User registered', $user_id, null, $user_name);
}

/**
 * Log user login activity
 * 
 * @param int $user_id The user ID
 * @param string|null $user_name The user name (optional)
 * @return bool True if successful, false otherwise
 */
function log_login($user_id, $user_name = null) {
    return log_action('User logged in', $user_id, null, $user_name);
}

/**
 * Log user logout activity
 * 
 * @param int $user_id The user ID
 * @param string|null $user_name The user name (optional)
 * @return bool True if successful, false otherwise
 */
function log_logout($user_id, $user_name = null) {
    return log_action('User logged out', $user_id, null, $user_name);
}

/**
 * Log password reset request activity
 * 
 * @param int $user_id The user ID
 * @param string|null $user_name The user name (optional)
 * @return bool True if successful, false otherwise
 */
function log_password_reset_request($user_id, $user_name = null) {
    return log_action('Requested password reset', $user_id, null, $user_name);
}

/**
 * Log password reset completion activity
 * 
 * @param int $user_id The user ID
 * @param string|null $user_name The user name (optional)
 * @return bool True if successful, false otherwise
 */
function log_password_reset_complete($user_id, $user_name = null) {
    return log_action('Reset password via email link', $user_id, null, $user_name);
}

/**
 * Log user deletion activity
 * 
 * @param int $admin_id The admin ID who performed the deletion
 * @param string $user_info Information about the deleted user
 * @param string|null $admin_name The admin name (optional)
 * @return bool True if successful, false otherwise
 */
function log_user_deletion($admin_id, $user_info, $admin_name = null) {
    return log_action("Deleted user: $user_info", $admin_id, null, $admin_name);
}

/**
 * Log user status change activity
 * 
 * @param int $admin_id The admin ID who performed the status change
 * @param int $user_id The user ID whose status was changed
 * @param string $new_status The new status
 * @param string $user_name The user's name
 * @param string|null $admin_name The admin name (optional)
 * @return bool True if successful, false otherwise
 */
function log_user_status_change($admin_id, $user_id, $new_status, $user_name, $admin_name = null) {
    return log_action("Changed status of user $user_name (ID: $user_id) to $new_status", $admin_id, null, $admin_name);
}

?>