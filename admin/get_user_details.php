<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_logged_in() || !is_admin()) {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit;
}

// Get user ID from request
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    echo '<div class="alert alert-danger">Invalid user ID</div>';
    exit;
}

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    echo '<div class="alert alert-danger">User not found</div>';
    exit;
}

$user = $user_result->fetch_assoc();

// Get bug statistics
$bug_stats_query = "SELECT 
    COUNT(*) as total_bugs,
    SUM(CASE WHEN status = 'resolved' OR status = 'closed' THEN 1 ELSE 0 END) as resolved_bugs
    FROM bugs WHERE ";

if ($user['role'] === 'customer') {
    $bug_stats_query .= "reported_by = ?";
} else {
    $bug_stats_query .= "assigned_to = ?";
}

$bug_stats_stmt = $conn->prepare($bug_stats_query);
$bug_stats_stmt->bind_param("i", $user_id);
$bug_stats_stmt->execute();
$bug_stats_result = $bug_stats_stmt->get_result();
$bug_stats = $bug_stats_result->fetch_assoc();

// Set role and status classes
$role_class = '';
switch ($user['role']) {
    case 'admin':
        $role_class = 'bg-danger';
        break;
    case 'staff':
        $role_class = 'bg-primary';
        break;
    case 'customer':
        $role_class = 'bg-info';
        break;
}

$status_class = '';
switch ($user['status']) {
    case 'active':
        $status_class = 'bg-success';
        break;
    case 'inactive':
        $status_class = 'bg-warning';
        break;
    case 'banned':
        $status_class = 'bg-danger';
        break;
}
?>

<div class="row" data-role="<?php echo $user['role']; ?>">
    <div class="col-md-4 text-center mb-3">
        <?php if (!empty($user['profile_image'])): ?>
            <img src="../uploads/profile/<?php echo $user['profile_image']; ?>" alt="Profile" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
        <?php else: ?>
            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px; font-size: 60px;">
                <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        <h4><?php echo htmlspecialchars($user['fullname']); ?></h4>
        <span class="badge <?php echo $role_class; ?> mb-2">
            <?php echo ucfirst($user['role']); ?>
        </span>
        <span class="badge <?php echo $status_class; ?>">
            <?php echo ucfirst($user['status']); ?>
        </span>
    </div>
    <div class="col-md-8">
        <h5>Contact Information</h5>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <?php if (!empty($user['phone'])): ?>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        <?php endif; ?>
        
        <h5 class="mt-4">Account Information</h5>
        <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
        <p><strong>Registered:</strong> <?php echo date('F d, Y H:i:s', strtotime($user['created_at'])); ?></p>
        <?php if ($user['updated_at']): ?>
            <p><strong>Last Updated:</strong> <?php echo date('F d, Y H:i:s', strtotime($user['updated_at'])); ?></p>
        <?php endif; ?>
        
        <h5 class="mt-4">Bug Statistics</h5>
        <?php if ($user['role'] === 'customer'): ?>
            <p><strong>Total Reported Bugs:</strong> <?php echo $bug_stats['total_bugs']; ?></p>
            <p><strong>Resolved Bugs:</strong> <?php echo $bug_stats['resolved_bugs']; ?></p>
        <?php else: ?>
            <p><strong>Total Assigned Bugs:</strong> <?php echo $bug_stats['total_bugs']; ?></p>
            <p><strong>Resolved Bugs:</strong> <?php echo $bug_stats['resolved_bugs']; ?></p>
        <?php endif; ?>
        
        <?php if (!empty($user['bio'])): ?>
            <h5 class="mt-4">Bio</h5>
            <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
        <?php endif; ?>
    </div>
</div>