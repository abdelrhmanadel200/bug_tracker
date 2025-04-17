<?php
// Include configuration
require_once '../config/config.php';

// Check if user is logged in and is a staff member
if (!is_logged_in() || !is_staff()) {
    redirect('../login.php');
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get statistics
// Total assigned bugs
$sql = "SELECT COUNT(*) as total FROM bugs WHERE assigned_to = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_assigned_bugs = $result->fetch_assoc()['total'];

// Open assigned bugs
$sql = "SELECT COUNT(*) as open FROM bugs WHERE assigned_to = ? AND status NOT IN (?, ?)";
$stmt = $conn->prepare($sql);
$closed = STATUS_CLOSED;
$resolved = STATUS_RESOLVED;
$stmt->bind_param("iss", $user_id, $closed, $resolved);
$stmt->execute();
$result = $stmt->get_result();
$open_assigned_bugs = $result->fetch_assoc()['open'];

// Resolved bugs
$sql = "SELECT COUNT(*) as resolved FROM bugs WHERE assigned_to = ? AND status = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $resolved);
$stmt->execute();
$result = $stmt->get_result();
$resolved_bugs = $result->fetch_assoc()['resolved'];

// Closed bugs
$sql = "SELECT COUNT(*) as closed FROM bugs WHERE assigned_to = ? AND status = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $closed);
$stmt->execute();
$result = $stmt->get_result();
$closed_bugs = $result->fetch_assoc()['closed'];

// Recent assigned bugs
$sql = "SELECT b.*, p.name as project_name, u.fullname as reported_by_name 
        FROM bugs b 
        JOIN projects p ON b.project_id = p.id 
        JOIN users u ON b.reported_by = u.id 
        WHERE b.assigned_to = ? 
        ORDER BY b.created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_assigned_bugs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all projects the staff is working on (projects with assigned bugs)
$sql = "SELECT DISTINCT p.id, p.name, p.description, p.status, 
        (SELECT COUNT(*) FROM bugs WHERE project_id = p.id AND assigned_to = ?) as bug_count 
        FROM projects p 
        JOIN bugs b ON p.id = b.project_id 
        WHERE b.assigned_to = ? 
        ORDER BY bug_count DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$staff_projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Assigned Bugs</h5>
                        <p class="card-text display-4"><?php echo $total_assigned_bugs; ?></p>
                        <a href="assigned_bugs.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Open Bugs</h5>
                        <p class="card-text display-4"><?php echo $open_assigned_bugs; ?></p>
                        <a href="assigned_bugs.php?status=open" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Resolved Bugs</h5>
                        <p class="card-text display-4"><?php echo $resolved_bugs; ?></p>
                        <a href="assigned_bugs.php?status=resolved" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Closed Bugs</h5>
                        <p class="card-text display-4"><?php echo $closed_bugs; ?></p>
                        <a href="assigned_bugs.php?status=closed" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bug me-2"></i>Recently Assigned Bugs</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_assigned_bugs) > 0): ?>
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
                                        <?php foreach ($recent_assigned_bugs as $bug): ?>
                                        <tr>
                                            <td>#<?php echo $bug['id']; ?></td>
                                            <td><?php echo htmlspecialchars($bug['title']); ?></td>
                                            <td><?php echo htmlspecialchars($bug['project_name']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($bug['status']) {
                                                    case 'new':
                                                        $status_class = 'bg-info';
                                                        break;
                                                    case 'assigned':
                                                        $status_class = 'bg-primary';
                                                        break;
                                                    case 'in_progress':
                                                        $status_class = 'bg-warning';
                                                        break;
                                                    case 'resolved':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'closed':
                                                        $status_class = 'bg-secondary';
                                                        break;
                                                    case 'reopened':
                                                        $status_class = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $bug['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $priority_class = '';
                                                switch ($bug['priority']) {
                                                    case 'low':
                                                        $priority_class = 'bg-success';
                                                        break;
                                                    case 'medium':
                                                        $priority_class = 'bg-info';
                                                        break;
                                                    case 'high':
                                                        $priority_class = 'bg-warning';
                                                        break;
                                                    case 'critical':
                                                        $priority_class = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $priority_class; ?>">
                                                    <?php echo ucfirst($bug['priority']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($bug['reported_by_name']); ?></td>
                                            <td>
                                                <a href="view_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <a href="assigned_bugs.php" class="btn btn-primary">View All Assigned Bugs</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>You don't have any assigned bugs yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i>My Projects</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($staff_projects) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($staff_projects as $project): ?>
                                <a href="projects.php?id=<?php echo $project['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($project['name']); ?></h6>
                                        <span class="badge bg-primary rounded-pill"><?php echo $project['bug_count']; ?> bugs</span>
                                    </div>
                                    <p class="mb-1 text-truncate"><?php echo htmlspecialchars($project['description']); ?></p>
                                    <small class="text-muted">
                                        Status: 
                                        <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'inactive' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </small>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-end mt-3">
                                <a href="projects.php" class="btn btn-success">View All Projects</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>You are not assigned to any projects yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Bug Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="p-3">
                                    <h5>Total Assigned</h5>
                                    <h2 class="text-primary"><?php echo $total_assigned_bugs; ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="p-3">
                                    <h5>Open Bugs</h5>
                                    <h2 class="text-warning"><?php echo $open_assigned_bugs; ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="p-3">
                                    <h5>Resolved Bugs</h5>
                                    <h2 class="text-success"><?php echo $resolved_bugs; ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="p-3">
                                    <h5>Closed Bugs</h5>
                                    <h2 class="text-secondary"><?php echo $closed_bugs; ?></h2>
                                </div>
                            </div>
                        </div>
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