<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if staff is logged in
// if (!is_logged_in() || !is_staff()) {
//     redirect('../login.php');
// }

// Get user ID
$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullname = sanitize_input($_POST['fullname']);
        $email = sanitize_input($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate inputs
        if (empty($fullname)) {
            $error_message = "Full name is required.";
        } elseif (empty($email)) {
            $error_message = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } else {
            // Check if email already exists (for another user)
            $check_email_query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_email_stmt = $conn->prepare($check_email_query);
            $check_email_stmt->bind_param("si", $email, $user_id);
            $check_email_stmt->execute();
            $check_email_result = $check_email_stmt->get_result();
            
            if ($check_email_result->num_rows > 0) {
                $error_message = "Email already in use by another account.";
            } else {
                // If changing password, verify current password
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error_message = "Current password is required to set a new password.";
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "New password and confirmation do not match.";
                    } elseif (strlen($new_password) < 8) {
                        $error_message = "New password must be at least 8 characters long.";
                    } elseif (!password_verify($current_password, $user['password'])) {
                        $error_message = "Current password is incorrect.";
                    }
                }
                
                if (empty($error_message)) {
                    // Handle profile image upload
                    $profile_image = $user['profile_image'];
                    
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                        $file_type = $_FILES['profile_image']['type'];
                        
                        if (!in_array($file_type, $allowed_types)) {
                            $error_message = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
                        } else {
                            $upload_dir = '../uploads/profile/';
                            
                            // Create directory if it doesn't exist
                            if (!file_exists($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $file_name = time() . '_' . basename($_FILES['profile_image']['name']);
                            $upload_path = $upload_dir . $file_name;
                            
                            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                                // Delete old profile image if exists
                                if (!empty($profile_image) && file_exists($upload_dir . $profile_image)) {
                                    unlink($upload_dir . $profile_image);
                                }
                                
                                $profile_image = $file_name;
                            } else {
                                $error_message = "Failed to upload profile image.";
                            }
                        }
                    }
                    
                    if (empty($error_message)) {
                        // Update user profile
                        if (!empty($new_password)) {
                            // Update with new password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_query = "UPDATE users SET fullname = ?, email = ?, password = ?, profile_image = ?, updated_at = NOW() WHERE id = ?";
                            $update_stmt = $conn->prepare($update_query);
                            $update_stmt->bind_param("ssssi", $fullname, $email, $hashed_password, $profile_image, $user_id);
                        } else {
                            // Update without changing password
                            $update_query = "UPDATE users SET fullname = ?, email = ?, profile_image = ?, updated_at = NOW() WHERE id = ?";
                            $update_stmt = $conn->prepare($update_query);
                            $update_stmt->bind_param("sssi", $fullname, $email, $profile_image, $user_id);
                        }
                        
                        if ($update_stmt->execute()) {
                            $success_message = "Profile updated successfully.";
                            
                            // Update session variables
                            $_SESSION['fullname'] = $fullname;
                            $_SESSION['email'] = $email;
                            
                            // Refresh user data
                            $user_stmt->execute();
                            $user_result = $user_stmt->get_result();
                            $user = $user_result->fetch_assoc();
                        } else {
                            $error_message = "Error updating profile: " . $conn->error;
                        }
                    }
                }
            }
        }
    }
}

// Get user statistics
$stats_query = "SELECT 
                COUNT(*) as total_bugs,
                SUM(CASE WHEN status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as open_bugs,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_bugs,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_bugs,
                COUNT(DISTINCT project_id) as total_projects
                FROM bugs WHERE assigned_to = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get recent activity
$activity_query = "SELECT a.*, b.title as bug_title, b.id as bug_id 
                  FROM activity_logs a 
                  LEFT JOIN bugs b ON a.bug_id = b.id 
                  WHERE a.user_id = ? 
                  ORDER BY a.created_at DESC LIMIT 10";
$activity_stmt = $conn->prepare($activity_query);
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$activity_result = $activity_stmt->get_result();
$activities = $activity_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/staff_header.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-12 mb-4">
                <h1 class="h3">My Profile</h1>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="../uploads/profile/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px; font-size: 60px;">
                                <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($user['fullname']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p>
                            <span class="badge bg-primary">Staff</span>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'inactive' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </p>
                        <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Bug Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h6>Total Bugs</h6>
                                <h3 class="text-primary"><?php echo $stats['total_bugs']; ?></h3>
                            </div>
                            <div class="col-6 mb-3">
                                <h6>Projects</h6>
                                <h3 class="text-info"><?php echo $stats['total_projects']; ?></h3>
                            </div>
                            <div class="col-6">
                                <h6>Resolved</h6>
                                <h3 class="text-success"><?php echo $stats['resolved_bugs']; ?></h3>
                            </div>
                            <div class="col-6">
                                <h6>Closed</h6>
                                <h3 class="text-secondary"><?php echo $stats['closed_bugs']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="fullname" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image">
                                <div class="form-text">Upload a new profile image (JPG, PNG, or GIF only).</div>
                            </div>
                            
                            <h5 class="mt-4 mb-3">Change Password</h5>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <div class="form-text">Enter your current password to confirm changes or set a new password.</div>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No recent activity found.
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($activities as $activity): ?>
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <i class="fas fa-history text-info me-2"></i>
                                                <?php echo htmlspecialchars($activity['action']); ?>
                                                <?php if (!empty($activity['bug_id'])): ?>
                                                    <a href="view_bug.php?id=<?php echo $activity['bug_id']; ?>">
                                                        <?php echo htmlspecialchars($activity['bug_title']); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>