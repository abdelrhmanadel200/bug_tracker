<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$confirm = isset($_GET['confirm']) ? $_GET['confirm'] : '';

// Validate user ID
if ($user_id <= 0) {
    redirect('users.php?error=Invalid user ID');
}

// Check if the user exists
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    redirect('users.php?error=User not found');
}

$user = $user_result->fetch_assoc();

// Prevent deleting your own account
if ($user_id == $_SESSION['user_id']) {
    redirect('users.php?error=You cannot delete your own account');
}

// Check if confirmation is provided
if ($confirm !== 'yes') {
    redirect('view_user.php?id=' . $user_id . '&error=Confirmation required to delete user');
}

// Begin transaction
$conn->begin_transaction();

try {
    // Delete user's comments
    $delete_comments_query = "DELETE FROM comments WHERE user_id = ?";
    $delete_comments_stmt = $conn->prepare($delete_comments_query);
    $delete_comments_stmt->bind_param("i", $user_id);
    $delete_comments_stmt->execute();
    
    // Delete user's activity logs
    $delete_logs_query = "DELETE FROM activity_logs WHERE user_id = ?";
    $delete_logs_stmt = $conn->prepare($delete_logs_query);
    $delete_logs_stmt->bind_param("i", $user_id);
    $delete_logs_stmt->execute();
    
    // Update bugs reported by user to be assigned to admin
    $update_bugs_query = "UPDATE bugs SET reported_by = ? WHERE reported_by = ?";
    $update_bugs_stmt = $conn->prepare($update_bugs_query);
    $admin_id = $_SESSION['user_id']; // Current admin
    $update_bugs_stmt->bind_param("ii", $admin_id, $user_id);
    $update_bugs_stmt->execute();
    
    // Unassign bugs assigned to this user
    $unassign_bugs_query = "UPDATE bugs SET assigned_to = NULL WHERE assigned_to = ?";
    $unassign_bugs_stmt = $conn->prepare($unassign_bugs_query);
    $unassign_bugs_stmt->bind_param("i", $user_id);
    $unassign_bugs_stmt->execute();
    
    // Delete user
    $delete_user_query = "DELETE FROM users WHERE id = ?";
    $delete_user_stmt = $conn->prepare($delete_user_query);
    $delete_user_stmt->bind_param("i", $user_id);
    $delete_user_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    $admin_id = $_SESSION['user_id'];
    $admin_name = $_SESSION['user_name'];
    $user_info = "{$user['fullname']} (ID: $user_id)";
    log_user_deletion($admin_id, $user_info, $admin_name);
    
    redirect('users.php?success=User deleted successfully');
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Redirect with error message
    redirect('users.php?error=Failed to delete user: ' . $e->getMessage());
}
?>
