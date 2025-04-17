<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
session_start();
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_mode = $user_id > 0;

// Initialize variables
$fullname = '';
$email = '';
$password = '';
$confirm_password = '';
$status = 'active';
$errors = [];

// If in edit mode, get user data
if ($edit_mode) {
    $user_query = "SELECT * FROM users WHERE id = ? AND role = 'staff'";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        header('Location: users.php');
        exit();
    }
    
    $user = $user_result->fetch_assoc();
    $fullname = $user['fullname'];
    $email = $user['email'];
    $status = $user['status'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $status = $_POST['status'];
    
    if (!$edit_mode || !empty($_POST['password'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
    }
    
    if (empty($fullname)) {
        $errors[] = "Full name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists (except for the current user in edit mode)
        $email_check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $email_check_stmt = $conn->prepare($email_check_query);
        $email_check_stmt->bind_param("si", $email, $user_id);
        $email_check_stmt->execute();
        $email_check_result = $email_check_stmt->get_result();
        
        if ($email_check_result->num_rows > 0) {
            $errors[] = "Email already exists. Please use a different email.";
        }
    }
    
    if (!$edit_mode || !empty($_POST['password'])) {
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }
    
    if (empty($errors)) {
        if ($edit_mode) {
            // Update existing staff
            if (!empty($_POST['password'])) {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET fullname = ?, email = ?, password = ?, status = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssssi", $fullname, $email, $hashed_password, $status, $user_id);
            } else {
                // Update without changing password
                $update_query = "UPDATE users SET fullname = ?, email = ?, status = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("sssi", $fullname, $email, $status, $user_id);
            }
            
            if ($update_stmt->execute()) {
                // Log the activity
                $admin_id = $_SESSION['user_id'];
                $action = "Updated staff member: $fullname (ID: $user_id)";
                log_activity($conn, $admin_id, null, $action);
                
                header('Location: users.php?success=updated');
                exit();
            } else {
                $errors[] = "Error updating staff member: " . $conn->error;
            }
        } else {
            // Create new staff
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'staff';
            $created_at = date('Y-m-d H:i:s');
            
            $insert_query = "INSERT INTO users (fullname, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssssss", $fullname, $email, $hashed_password, $role, $status, $created_at);
            
            if ($insert_stmt->execute()) {
                $new_user_id = $conn->insert_id;
                
                // Log the activity
                $admin_id = $_SESSION['user_id'];
                $action = "Created new staff member: $fullname (ID: $new_user_id)";
                log_activity($conn, $admin_id, null, $action);
                
                header('Location: users.php?success=created');
                exit();
            } else {
                $errors[] = "Error creating staff member: " . $conn->error;
            }
        }
    }
}

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo $edit_mode ? 'Edit Staff Member' : 'Add New Staff Member'; ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo $edit_mode ? 'New Password (leave blank to keep current)' : 'Password <span class="text-danger">*</span>'; ?></label>
                            <input type="password" class="form-control" id="password" name="password" <?php echo $edit_mode ? '' : 'required'; ?>>
                            <?php if ($edit_mode): ?>
                                <div class="form-text">Leave blank to keep the current password.</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><?php echo $edit_mode ? 'Confirm New Password' : 'Confirm Password <span class="text-danger">*</span>'; ?></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" <?php echo $edit_mode ? '' : 'required'; ?>>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="banned" <?php echo $status === 'banned' ? 'selected' : ''; ?>>Banned</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_mode ? 'Update Staff Member' : 'Create Staff Member'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
