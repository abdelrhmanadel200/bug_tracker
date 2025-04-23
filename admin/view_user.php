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

if ($user_id <= 0) {
    redirect('users.php');
}

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    redirect('users.php');
}

$user = $user_result->fetch_assoc();

// Handle user status update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $new_status = sanitize_input($_POST['status']);
        $new_role = sanitize_input($_POST['role']);
        
        // Update user status
        $update_query = "UPDATE users SET status = ?, role = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssi", $new_status, $new_role, $user_id);
        
        if ($update_stmt->execute()) {
            $success_message = "User status and role updated successfully.";
            
            // Refresh user data
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
            
            // Log the activity
            $action = "Updated user {$user['fullname']} (ID: $user_id) - Status: $new_status, Role: $new_role";
            // log_admin_action($conn, $_SESSION['user_id'], $action);
        } else {
            $error_message = "Error updating user: " . $conn->error;
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
                FROM bugs WHERE reported_by = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get assigned bugs stats if user is staff
$assigned_stats = null;
if ($user['role'] === 'staff') {
    $assigned_query = "SELECT 
                    COUNT(*) as total_assigned,
                    SUM(CASE WHEN status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as open_assigned,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_assigned,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_assigned
                    FROM bugs WHERE assigned_to = ?";
    $assigned_stmt = $conn->prepare($assigned_query);
    $assigned_stmt->bind_param("i", $user_id);
    $assigned_stmt->execute();
    $assigned_result = $assigned_stmt->get_result();
    $assigned_stats = $assigned_result->fetch_assoc();
}

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

// Get user's bugs
$bugs_query = "SELECT b.*, p.name as project_name, 
              u.fullname as assigned_to_name
              FROM bugs b 
              LEFT JOIN projects p ON b.project_id = p.id
              LEFT JOIN users u ON b.assigned_to = u.id
              WHERE b.reported_by = ? 
              ORDER BY b.created_at DESC LIMIT 5";
$bugs_stmt = $conn->prepare($bugs_query);
$bugs_stmt->bind_param("i", $user_id);
$bugs_stmt->execute();
$bugs_result = $bugs_stmt->get_result();
$bugs = $bugs_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User: <?php echo htmlspecialchars($user['fullname']); ?> - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">User Profile: <?php echo htmlspecialchars($user['fullname']); ?></h1>
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Users
            </a>
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
                <!-- User Profile Card -->
                <div class="card border-0 shadow-sm">
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
                        <div class="mb-3">
                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'primary' : 'success'); ?> me-1">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'inactive' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                        <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo !empty($user['updated_at']) ? date('F d, Y H:i', strtotime($user['updated_at'])) : 'Never updated'; ?></p>
                    </div>
                </div>

                <!-- User Statistics Card -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">User Statistics</h5>
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

                        <?php if ($assigned_stats): ?>
                            <hr>
                            <h6 class="text-center mb-3">Assigned Bugs</h6>
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <h6>Total</h6>
                                    <h3 class="text-primary"><?php echo $assigned_stats['total_assigned']; ?></h3>
                                </div>
                                <div class="col-6 mb-3">
                                    <h6>Open</h6>
                                    <h3 class="text-warning"><?php echo $assigned_stats['open_assigned']; ?></h3>
                                </div>
                                <div class="col-6">
                                    <h6>Resolved</h6>
                                    <h3 class="text-success"><?php echo $assigned_stats['resolved_assigned']; ?></h3>
                                </div>
                                <div class="col-6">
                                    <h6>Closed</h6>
                                    <h3 class="text-secondary"><?php echo $assigned_stats['closed_assigned']; ?></h3>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- User Management Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Manage User</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <label for="email" class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="role" class="col-sm-3 col-form-label">Role</label>
                                <div class="col-sm-9">
                                    <select class="form-select" id="role" name="role">
                                        <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="status" class="col-sm-3 col-form-label">Status</label>
                                <div class="col-sm-9">
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update User
                                </button>
                                <a href="admin_reset_password.php?id=<?php echo $user_id; ?>" class="btn btn-warning">
                                <i class="fas fa-key me-1"></i> Reset Password
                                </a>
                                <?php if ($user['status'] !== 'suspended'): ?>
                                    <a href="suspend_user.php?id=<?php echo $user_id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to suspend this user?');">
                                        <i class="fas fa-ban me-1"></i> Suspend User
                                    </a>
                                <?php else: ?>
                                    <a href="activate_user.php?id=<?php echo $user_id; ?>" class="btn btn-success">
                                        <i class="fas fa-check me-1"></i> Activate User
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Bugs Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Bugs</h5>
                        <a href="user_bugs.php?id=<?php echo $user_id; ?>" class="btn btn-sm btn-light">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bugs)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>This user hasn't reported any bugs yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Project</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bugs as $bug): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($bug['ticket_number']); ?></td>
                                                <td><?php echo htmlspecialchars($bug['title']); ?></td>
                                                <td><?php echo htmlspecialchars($bug['project_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch ($bug['status']) {
                                                            case 'new': echo 'info'; break;
                                                            case 'assigned': echo 'primary'; break;
                                                            case 'in_progress': echo 'warning'; break;
                                                            case 'resolved': echo 'success'; break;
                                                            case 'closed': echo 'secondary'; break;
                                                            case 'reopened': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $bug['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($bug['created_at'])); ?></td>
                                                <td>
                                                    <a href="../admin/view_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No recent activity found for this user.
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
                                                    <a href="../staff/view_bug.php?id=<?php echo $activity['bug_id']; ?>">
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