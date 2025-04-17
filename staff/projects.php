<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if staff is logged in
if (!is_logged_in() || !is_staff()) {
    redirect('../login.php');
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Check if a specific project is requested
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id > 0) {
    // Get project details
    $project_query = "SELECT p.*, u.fullname as created_by_name 
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
    
    // Get bugs for this project
    $bugs_query = "SELECT b.*, u1.fullname as reported_by_name, u2.fullname as assigned_to_name 
                  FROM bugs b 
                  LEFT JOIN users u1 ON b.reported_by = u1.id 
                  LEFT JOIN users u2 ON b.assigned_to = u2.id 
                  WHERE b.project_id = ? 
                  ORDER BY b.created_at DESC";
    $bugs_stmt = $conn->prepare($bugs_query);
    $bugs_stmt->bind_param("i", $project_id);
    $bugs_stmt->execute();
    $bugs_result = $bugs_stmt->get_result();
    $bugs = $bugs_result->fetch_all(MYSQLI_ASSOC);
    
    // Get bug statistics
    $stats_query = "SELECT 
                    COUNT(*) as total_bugs,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_bugs,
                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_bugs,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_bugs,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_bugs,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_bugs,
                    SUM(CASE WHEN status = 'reopened' THEN 1 ELSE 0 END) as reopened_bugs,
                    SUM(CASE WHEN assigned_to = ? THEN 1 ELSE 0 END) as my_bugs
                    FROM bugs WHERE project_id = ?";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->bind_param("ii", $user_id, $project_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
} else {
    // Get all projects with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Filter by status if provided
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';
    $my_projects = isset($_GET['my_projects']) && $_GET['my_projects'] === '1';

    // Build query based on filters
    if ($my_projects) {
        $query = "SELECT DISTINCT p.*, u.fullname as created_by_name, 
                 (SELECT COUNT(*) FROM bugs WHERE project_id = p.id) as bug_count,
                 (SELECT COUNT(*) FROM bugs WHERE project_id = p.id AND assigned_to = ?) as my_bug_count 
                 FROM projects p 
                 LEFT JOIN users u ON p.created_by = u.id 
                 JOIN bugs b ON p.id = b.project_id 
                 WHERE b.assigned_to = ?";
        $params = [$user_id, $user_id];
    } else {
        $query = "SELECT p.*, u.fullname as created_by_name, 
                 (SELECT COUNT(*) FROM bugs WHERE project_id = p.id) as bug_count,
                 (SELECT COUNT(*) FROM bugs WHERE project_id = p.id AND assigned_to = ?) as my_bug_count 
                 FROM projects p 
                 LEFT JOIN users u ON p.created_by = u.id 
                 WHERE 1=1";
        $params = [$user_id];
    }

    if (!empty($status_filter)) {
        $query .= " AND p.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($search_term)) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare($query);

    // Bind parameters dynamically
    if (!empty($params)) {
        $types = str_repeat('s', count($params) - 2) . 'ii'; // string types + 2 integers for limit and offset
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $projects = $result->fetch_all(MYSQLI_ASSOC);

    // Get total projects count for pagination
    if ($my_projects) {
        $count_query = "SELECT COUNT(DISTINCT p.id) as total 
                       FROM projects p 
                       JOIN bugs b ON p.id = b.project_id 
                       WHERE b.assigned_to = ?";
        $count_params = [$user_id];
    } else {
        $count_query = "SELECT COUNT(*) as total FROM projects WHERE 1=1";
        $count_params = [];
    }

    if (!empty($status_filter)) {
        $count_query .= " AND status = ?";
        $count_params[] = $status_filter;
    }

    if (!empty($search_term)) {
        $count_query .= " AND (name LIKE ? OR description LIKE ?)";
        $search_param = "%$search_term%";
        $count_params[] = $search_param;
        $count_params[] = $search_param;
    }

    $count_stmt = $conn->prepare($count_query);

    // Bind parameters dynamically
    if (!empty($count_params)) {
        $count_types = str_repeat('s', count($count_params));
        $count_stmt->bind_param($count_types, ...$count_params);
    }

    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total_projects = $count_row['total'];
    $total_pages = ceil($total_projects / $limit);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project_id > 0 ? htmlspecialchars($project['name']) : 'Projects'; ?> - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/staff_header.php'; ?>

    <div class="container-fluid py-4">
        <?php if ($project_id > 0): ?>
            <!-- Single Project View -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><?php echo htmlspecialchars($project['name']); ?></h1>
                <div>
                    <a href="projects.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Projects
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Project Details</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'inactive' ? 'warning' : 'secondary'); ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </p>
                            <p><strong>Created By:</strong> <?php echo htmlspecialchars($project['created_by_name']); ?></p>
                            <p><strong>Created On:</strong> <?php echo date('M d, Y', strtotime($project['created_at'])); ?></p>
                            
                            <h6 class="mt-4">Description</h6>
                            <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Bug Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center mb-3">
                                    <div class="p-3 border rounded">
                                        <h6>Total Bugs</h6>
                                        <h3 class="text-primary"><?php echo $stats['total_bugs']; ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <div class="p-3 border rounded">
                                        <h6>My Assigned</h6>
                                        <h3 class="text-info"><?php echo $stats['my_bugs']; ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <div class="p-3 border rounded">
                                        <h6>In Progress</h6>
                                        <h3 class="text-warning"><?php echo $stats['in_progress_bugs']; ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <div class="p-3 border rounded">
                                        <h6>Resolved</h6>
                                        <h3 class="text-success"><?php echo $stats['resolved_bugs']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="progress" style="height: 25px;">
                                        <?php
                                        $total = max(1, $stats['total_bugs']); // Avoid division by zero
                                        $new_percent = ($stats['new_bugs'] / $total) * 100;
                                        $assigned_percent = ($stats['assigned_bugs'] / $total) * 100;
                                        $in_progress_percent = ($stats['in_progress_bugs'] / $total) * 100;
                                        $resolved_percent = ($stats['resolved_bugs'] / $total) * 100;
                                        $closed_percent = ($stats['closed_bugs'] / $total) * 100;
                                        $reopened_percent = ($stats['reopened_bugs'] / $total) * 100;
                                        ?>
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $new_percent; ?>%" title="New: <?php echo $stats['new_bugs']; ?>"></div>
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $assigned_percent; ?>%" title="Assigned: <?php echo $stats['assigned_bugs']; ?>"></div>
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $in_progress_percent; ?>%" title="In Progress: <?php echo $stats['in_progress_bugs']; ?>"></div>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $resolved_percent; ?>%" title="Resolved: <?php echo $stats['resolved_bugs']; ?>"></div>
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo $closed_percent; ?>%" title="Closed: <?php echo $stats['closed_bugs']; ?>"></div>
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $reopened_percent; ?>%" title="Reopened: <?php echo $stats['reopened_bugs']; ?>"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2 small">
                                        <span><i class="fas fa-circle text-info"></i> New</span>
                                        <span><i class="fas fa-circle text-primary"></i> Assigned</span>
                                        <span><i class="fas fa-circle text-warning"></i> In Progress</span>
                                        <span><i class="fas fa-circle text-success"></i> Resolved</span>
                                        <span><i class="fas fa-circle text-secondary"></i> Closed</span>
                                        <span><i class="fas fa-circle text-danger"></i> Reopened</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Project Bugs (<?php echo count($bugs); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bugs)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No bugs found for this project.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Ticket #</th>
                                        <th>Title</th>
                                        <th>Reported By</th>
                                        <th>Assigned To</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bugs as $bug): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($bug['ticket_number']); ?></td>
                                            <td><?php echo htmlspecialchars($bug['title']); ?></td>
                                            <td><?php echo htmlspecialchars($bug['reported_by_name']); ?></td>
                                            <td>
                                                <?php if ($bug['assigned_to_name']): ?>
                                                    <?php echo htmlspecialchars($bug['assigned_to_name']); ?>
                                                    <?php if ($bug['assigned_to'] == $user_id): ?>
                                                        <span class="badge bg-primary">You</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
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
                                            <td><?php echo date('M d, Y', strtotime($bug['created_at'])); ?></td>
                                            <td>
                                                <a href="view_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (empty($bug['assigned_to'])): ?>
                                                    <a href="view_bug.php?id=<?php echo $bug['id']; ?>&action=assign" class="btn btn-sm btn-success">
                                                        <i class="fas fa-user-plus"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Projects List View -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Projects</h1>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or description" value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="my_projects" class="form-label">Show Only</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="my_projects" name="my_projects" value="1" <?php echo $my_projects ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="my_projects">
                                    My Projects
                                </label>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                            <a href="projects.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">All Projects (<?php echo $total_projects; ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Total Bugs</th>
                                    <th>My Bugs</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($projects)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No projects found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($projects as $project): ?>
                                        <tr>
                                            <td><?php echo $project['id']; ?></td>
                                            <td>
                                                <a href="projects.php?id=<?php echo $project['id']; ?>">
                                                    <?php echo htmlspecialchars($project['name']); ?>
                                                </a>
                                            </td>
                                            <td>
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
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($project['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                            <td>
                                                <a href="all_bugs.php?project_id=<?php echo $project['id']; ?>" class="badge bg-info text-decoration-none">
                                                    <?php echo $project['bug_count']; ?> bugs
                                                </a>
                                            </td>
                                            <td>
                                                <a href="assigned_bugs.php?project_id=<?php echo $project['id']; ?>" class="badge bg-primary text-decoration-none">
                                                    <?php echo $project['my_bug_count']; ?> bugs
                                                </a>
                                            </td>
                                            <td>
                                                <a href="projects.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mt-4">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>&my_projects=<?php echo $my_projects ? '1' : ''; ?>">
                                        Previous
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>&my_projects=<?php echo $my_projects ? '1' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>&my_projects=<?php echo $my_projects ? '1' : ''; ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>