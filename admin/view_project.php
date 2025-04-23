<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Get project ID from URL
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id <= 0) {
    redirect('projects.php');
}

// Get project details
$project_query = "SELECT p.*, u.fullname as created_by_name, 
                 (SELECT COUNT(*) FROM bugs WHERE project_id = p.id) as bug_count 
                 FROM projects p 
                 LEFT JOIN users u ON p.created_by = u.id 
                 WHERE p.id = ?";
$project_stmt = $conn->prepare($project_query);
$project_stmt->bind_param("i", $project_id);
$project_stmt->execute();
$project_result = $project_stmt->get_result();

if ($project_result->num_rows === 0) {
    redirect('projects.php');
}

$project = $project_result->fetch_assoc();

// Get recent bugs for this project
$bugs_query = "SELECT b.*, u.fullname as reported_by_name, 
              u2.fullname as assigned_to_name
              FROM bugs b 
              LEFT JOIN users u ON b.reported_by = u.id
              LEFT JOIN users u2 ON b.assigned_to = u2.id
              WHERE b.project_id = ? 
              ORDER BY b.created_at DESC LIMIT 5";
$bugs_stmt = $conn->prepare($bugs_query);
$bugs_stmt->bind_param("i", $project_id);
$bugs_stmt->execute();
$bugs_result = $bugs_stmt->get_result();
$bugs = $bugs_result->fetch_all(MYSQLI_ASSOC);

// Get bug statistics
$stats_query = "SELECT 
                COUNT(*) as total_bugs,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_bugs,
                SUM(CASE WHEN status = 'assigned' OR status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_bugs,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_bugs,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_bugs
                FROM bugs WHERE project_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $project_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get team members working on this project
$team_query = "SELECT DISTINCT u.id, u.fullname, u.email, u.role
               FROM users u
               JOIN bugs b ON u.id = b.assigned_to
               WHERE b.project_id = ?
               ORDER BY u.fullname";
$team_stmt = $conn->prepare($team_query);
$team_stmt->bind_param("i", $project_id);
$team_stmt->execute();
$team_result = $team_stmt->get_result();
$team_members = $team_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - Project Details - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Project Details</h1>
            <div>
                <a href="projects.php" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
                <a href="add_project.php?id=<?php echo $project_id; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Project
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Project Overview Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Project Overview</h5>
                    </div>
                    <div class="card-body">
                        <h2><?php echo htmlspecialchars($project['name']); ?></h2>
                        <p class="text-muted">Created on <?php echo date('F d, Y', strtotime($project['created_at'])); ?> by <?php echo htmlspecialchars($project['created_by_name']); ?></p>
                        
                        <?php
                        $status_class = '';
                        switch ($project['status']) {
                            case 'active':
                                $status_class = 'bg-success';
                                break;
                            case 'inactive':
                                $status_class = 'bg-warning';
                                break;
                            case 'archived':
                                $status_class = 'bg-secondary';
                                break;
                        }
                        ?>
                        <div class="mb-4">
                            <span class="badge <?php echo $status_class; ?> fs-6">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </div>
                        
                        <h5>Description</h5>
                        <div class="p-3 bg-light rounded mb-4">
                            <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Start Date</h5>
                                <p><?php echo !empty($project['start_date']) ? date('F d, Y', strtotime($project['start_date'])) : 'Not specified'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>End Date</h5>
                                <p><?php echo !empty($project['end_date']) ? date('F d, Y', strtotime($project['end_date'])) : 'Not specified'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bugs Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Bugs</h5>
                        <a href="bugs.php?project_id=<?php echo $project_id; ?>" class="btn btn-sm btn-light">View All Bugs</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bugs)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No bugs have been reported for this project yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Priority</th>
                                            <th>Reported By</th>
                                            <th>Assigned To</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bugs as $bug): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($bug['ticket_number']); ?></td>
                                                <td><?php echo htmlspecialchars($bug['title']); ?></td>
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
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch ($bug['priority']) {
                                                            case 'low': echo 'success'; break;
                                                            case 'medium': echo 'info'; break;
                                                            case 'high': echo 'warning'; break;
                                                            case 'critical': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($bug['priority']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($bug['reported_by_name']); ?></td>
                                                <td>
                                                    <?php if ($bug['assigned_to']): ?>
                                                        <?php echo htmlspecialchars($bug['assigned_to_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Unassigned</span>
                                                    <?php endif; ?>
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
                
                <!-- Team Members Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Team Members</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($team_members)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No team members have been assigned to bugs in this project yet.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($team_members as $member): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center p-3 border rounded">
                                            <div class="flex-shrink-0">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 20px;">
                                                    <?php echo strtoupper(substr($member['fullname'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($member['fullname']); ?></h6>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($member['email']); ?></p>
                                                <span class="badge bg-<?php echo $member['role'] === 'admin' ? 'danger' : ($member['role'] === 'staff' ? 'primary' : 'success'); ?>">
                                                    <?php echo ucfirst($member['role']); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <a href="view_user.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-user"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Project Stats Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Bug Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-4">
                                <h6>Total Bugs</h6>
                                <h2 class="display-6"><?php echo $stats['total_bugs']; ?></h2>
                            </div>
                            <div class="col-6 mb-4">
                                <h6>New</h6>
                                <h2 class="display-6 text-info"><?php echo $stats['new_bugs']; ?></h2>
                            </div>
                            <div class="col-6 mb-4">
                                <h6>In Progress</h6>
                                <h2 class="display-6 text-warning"><?php echo $stats['in_progress_bugs']; ?></h2>
                            </div>
                            <div class="col-6 mb-4">
                                <h6>Resolved</h6>
                                <h2 class="display-6 text-success"><?php echo $stats['resolved_bugs']; ?></h2>
                            </div>
                        </div>
                        
                        <?php if ($stats['total_bugs'] > 0): ?>
                            <div class="mt-3">
                                <h6>Progress</h6>
                                <div class="progress" style="height: 25px;">
                                    <?php
                                    $new_percent = ($stats['new_bugs'] / $stats['total_bugs']) * 100;
                                    $in_progress_percent = ($stats['in_progress_bugs'] / $stats['total_bugs']) * 100;
                                    $resolved_percent = ($stats['resolved_bugs'] / $stats['total_bugs']) * 100;
                                    $closed_percent = ($stats['closed_bugs'] / $stats['total_bugs']) * 100;
                                    ?>
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $new_percent; ?>%" title="New: <?php echo $stats['new_bugs']; ?>"></div>
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $in_progress_percent; ?>%" title="In Progress: <?php echo $stats['in_progress_bugs']; ?>"></div>
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $resolved_percent; ?>%" title="Resolved: <?php echo $stats['resolved_bugs']; ?>"></div>
                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo $closed_percent; ?>%" title="Closed: <?php echo $stats['closed_bugs']; ?>"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2 small">
                                    <span class="text-info">New</span>
                                    <span class="text-warning">In Progress</span>
                                    <span class="text-success">Resolved</span>
                                    <span class="text-secondary">Closed</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="view_bug.php?action=new" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> Add New Bug
                            </a>
                            <a href="bugs.php?project_id=<?php echo $project_id; ?>" class="btn btn-info">
                                <i class="fas fa-bug me-2"></i> View All Bugs
                            </a>
                            <a href="add_project.php?id=<?php echo $project_id; ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i> Edit Project
                            </a>
                            <?php if ($stats['total_bugs'] === 0): ?>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteProjectModal">
                                    <i class="fas fa-trash-alt me-2"></i> Delete Project
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-danger" disabled title="Cannot delete project with associated bugs">
                                    <i class="fas fa-trash-alt me-2"></i> Delete Project
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Project Info Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Project Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Project ID</span>
                                <span class="badge bg-primary rounded-pill"><?php echo $project['id']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Created By</span>
                                <span><?php echo htmlspecialchars($project['created_by_name']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Created Date</span>
                                <span><?php echo date('M d, Y', strtotime($project['created_at'])); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Last Updated</span>
                                <span><?php echo !empty($project['updated_at']) ? date('M d, Y', strtotime($project['updated_at'])) : 'Not available'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>Status</span>
                                <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($project['status']); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Project Modal -->
    <?php if ($stats['total_bugs'] === 0): ?>
        <div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteProjectModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the project: <strong><?php echo htmlspecialchars($project['name']); ?></strong>?</p>
                        <p>This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="projects.php">
                            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                            <button type="submit" name="delete_project" class="btn btn-danger">Delete Project</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>