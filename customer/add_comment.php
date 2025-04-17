<?php
// Include configuration
require_once '../config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../login.php');
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $bug_id = (int)$_POST['bug_id'];
    $comment = sanitize_input($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    
    // Validate form data
    if (empty($bug_id) || empty($comment)) {
        redirect('view_bug.php?id=' . $bug_id . '&error=Please enter a comment');
    }
    
    // Check if bug exists and user has permission to comment
    if (is_customer()) {
        $sql = "SELECT id FROM bugs WHERE id = ? AND reported_by = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $bug_id, $user_id);
    } else {
        $sql = "SELECT id FROM bugs WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bug_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        redirect('dashboard.php?error=Bug not found or you do not have permission to comment');
    }
    
    // Insert comment into database
    $sql = "INSERT INTO comments (bug_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $bug_id, $user_id, $comment);
    
    if ($stmt->execute()) {
        // Log action
        log_action('Comment added', $user_id, $bug_id);
        
        // Update bug's updated_at timestamp
        $sql = "UPDATE bugs SET updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bug_id);
        $stmt->execute();
        
        // Add to bug history
        $action = 'added a comment';
        $sql = "INSERT INTO bug_history (bug_id, user_id, action, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $bug_id, $user_id, $action);
        $stmt->execute();
        
        // Redirect back to bug page
        redirect('view_bug.php?id=' . $bug_id . '&success=Comment added successfully');
    } else {
        redirect('view_bug.php?id=' . $bug_id . '&error=Failed to add comment');
    }
} else {
    redirect('dashboard.php');
}
?>