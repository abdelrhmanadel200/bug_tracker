<?php
// Include configuration
require_once '../config/config.php';

// Check if user is logged in and is a staff member
if (!is_logged_in() || !is_staff()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Get assigned bugs
$sql = "SELECT b.*, p.name as project_name, u.fullname as reported_by_name 
        FROM bugs b 
        JOIN projects p ON b.project_id = p.id 
        JOIN users u ON b.reported_by = u.id 
        WHERE b.assigned_to = ? AND b.status NOT IN (?, ?)
        ORDER BY 
            CASE 
                WHEN b.priority = ? THEN 1
                WHEN b.priority = ? THEN 2
                WHEN b.priority = ? THEN 3
                WHEN b.priority = ? THEN 4
                ELSE 5
            END,
            b.created_at DESC";
$stmt = $conn->prepare($sql);
$closed = STATUS_CLOSED;
$resolved = STATUS_RESOLVED;
$critical = PRIORITY_CRITICAL;
$high = PRIORITY_HIGH;
$medium = PRIORITY_MEDIUM;
$low = PRIORITY_LOW;
$stmt->bind_param("issssss", $user_id, $closed, $resolved, $critical, $high, $medium, $low);
$stmt->execute();
$assigned_bugs = $stmt->get_result();

// Get total assigned bugs
$sql = "SELECT COUNT(*) as total FROM bugs WHERE assigned_to = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_assigned = $result->fetch_assoc()['total'];

// Get open assigned bugs
$sql = "SELECT COUNT(*) as open FROM bugs WHERE assigned_to = ? AND status NOT IN (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $closed, $resolved);
$stmt->execute();
$result = $stmt->get_result();
$open_assigned = $result->fetch_assoc()['open'];

// Get resolved bugs
$sql = "SELECT COUNT(*) as resolved FROM bugs WHERE assigned_to = ? AND status = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $resolved);
$stmt->execute();
$result = $stmt->get_result();
$resolved_bugs = $result->fetch_assoc()['resolved'];

// Get recent activity
$sql = "SELECT h.*, b.title as bug_title, u.fullname 
        FROM bug_history h 
        JOIN bugs b ON h.bug_id = b.id 
        JOIN users u ON h.user_id = u.id 
        WHERE b.assigned_to = ? 
        ORDER BY h.created_at DESC 
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_activity = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/staff_header.php'; ?>

    <main class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-tachometer-alt text-primary me-2"></i>Staff Dashboard</h1>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Assigned</h5>
                        <p class="card-text display-4"><?php echo $total_assigned; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Open Assigned</h5>
                        <p class="card-text display-4"><?php echo $open_assigned; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Resolved Bugs</h5>
                        <p class="card-text display-4"><?php echo $resolved_bugs; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Assigned Bugs</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($assigned_bugs->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Project</th>
                                            <th>Status</th>
                                            <th>Priority</th>
                                            <th>Reported By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($bug = $assigned_bugs->fetch_assoc()): ?>
                                        <tr class="bug-priority-<?php echo $bug['priority']; ?>">
                                            <td>#<?php echo $bug['id']; ?></td>
                                            <td><?php echo htmlspecialchars($bug['title']); ?></td>
                                            <td><?php echo htmlspecialchars($bug['project_name']); ?></td>
                                            <td><?php echo get_status_badge($bug['status']); ?></td>
                                            <td><?php echo get_priority_badge($bug['priority']); ?></td>
                                            <td><?php echo htmlspecialchars($bug['reported_by_name']); ?></td>
                                            <td>
                                                <a href="view_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No bugs assigned to you yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_activity->num_rows > 0): ?>
                            <div class="timeline">
                                <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($activity['fullname']); ?></strong>
                                                <span><?php echo htmlspecialchars($activity['action']); ?></span>
                                                <a href="view_bug.php?id=<?php echo $activity['bug_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($activity['bug_title']); ?>
                                                </a>
                                            </div>
                                            <small class="text-muted"><?php echo format_datetime($activity['created_at']); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No recent activity.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>