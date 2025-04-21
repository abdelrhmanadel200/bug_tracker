<?php
// Include configuration
require_once '../config/config.php';

// Check if user is logged in and is a customer
if (!is_logged_in() || !is_customer()) {
    redirect('../login.php');
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $project_id = sanitize_input($_POST['project_id']);
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $priority = sanitize_input($_POST['priority']);
    $environment = sanitize_input($_POST['environment']);
    $user_id = $_SESSION['user_id'];
    
    // Validate form data
    if (empty($project_id) || empty($title) || empty($description) || empty($priority)) {
        redirect('submit_bug.php?error=Please fill in all required fields');
    }
    
    // Validate priority
    $valid_priorities = [PRIORITY_LOW, PRIORITY_MEDIUM, PRIORITY_HIGH, PRIORITY_CRITICAL];
    if (!in_array($priority, $valid_priorities)) {
        $priority = PRIORITY_MEDIUM;
    }
    
    // Upload screenshot if provided
    $screenshot_path = null;
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] !== UPLOAD_ERR_NO_FILE) {
        $screenshot_path = upload_file($_FILES['screenshot'], '../uploads/screenshots/');
        
        if (!$screenshot_path) {
            redirect('submit_bug.php?error=Failed to upload screenshot. Please check file size and format.');
        }
    }
    
    // Generate ticket number
    $ticket_number = 'BUG-' . date('Ymd') . '-' . strtoupper(generate_random_string(6));
    
    // Insert bug into database
    $sql = "INSERT INTO bugs (project_id, reported_by, title, description, priority, environment, screenshot, ticket_number, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $status = STATUS_NEW;
    $stmt->bind_param("iisssssss", $project_id, $user_id, $title, $description, $priority, $environment, $screenshot_path, $ticket_number, $status);
    
    if ($stmt->execute()) {
        $bug_id = $stmt->insert_id;
        
        // Log action
        // log_action('Bug reported', $user_id, $bug_id);
        
        // Redirect to success page
        redirect('view_bug.php?id=' . $bug_id . '&success=Bug reported successfully! Your ticket number is ' . $ticket_number);
    } else {
        redirect('submit_bug.php?error=Failed to submit bug. Please try again.');
    }
} else {
    redirect('submit_bug.php');
}
?>