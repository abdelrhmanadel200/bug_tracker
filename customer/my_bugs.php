<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if customer is logged in
// if (!is_logged_in() || $_SESSION['role'] == 'customer') {
//     redirect('../login.php');
// }

// Get user ID
$user_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$project_filter = isset($_GET['project']) ? (int)$_GET['project'] : 0;
$priority_filter = isset($_GET['priority']) ? sanitize_input($_GET['priority']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build the query
$query = "SELECT b.*, p.name as project_name, 
          u.fullname as assigned_to_name
          FROM bugs b 
          LEFT JOIN projects p ON b.project_id = p.id
          LEFT JOIN users u ON b.assigned_to = u.id
          WHERE b.reported_by = ?";
$params = [$user_id];
$types = "i";

// Add filters
if (!empty($status_filter)) {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($project_filter > 0) {
    $query .= " AND b.project_id = ?";
    $params[] = $project_filter;
    $types .= "i";
}

if (!empty($priority_filter)) {
    $query .= " AND b.priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (b.title LIKE ? OR b.description LIKE ? OR b.ticket_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Count total records for pagination
$count_query = $query;
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->num_rows;
$total_pages = ceil($total_records / $records_per_page);

// Add sorting and pagination
$query .= " ORDER BY b.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

// Execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$bugs = $result->fetch_all(MYSQLI_ASSOC);

// Get projects for filter dropdown
$projects_query = "SELECT DISTINCT p.id, p.name 
                  FROM projects p 
                  JOIN bugs b ON p.id = b.project_id 
                  WHERE b.reported_by = ?";
$projects_stmt = $conn->prepare($projects_query);
$projects_stmt->bind_param("i", $user_id);
$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();
$projects = $projects_result->fetch_all(MYSQLI_ASSOC);

// Get bug statistics
$stats_query = "SELECT 
                COUNT(*) as total_bugs,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_bugs,
                SUM(CASE WHEN status = 'assigned' OR status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_bugs,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_bugs,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_bugs,
                SUM(CASE WHEN status = 'reopened' THEN 1 ELSE 0 END) as reopened_bugs
                FROM bugs WHERE reported_by = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bugs - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/customer_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">My Reported Bugs</h1>
            <a href="report_bug.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Report New Bug
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total</h5>
                        <p class="card-text display-6"><?php echo $stats['total_bugs']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">New</h5>
                        <p class="card-text display-6 text-info"><?php echo $stats['new_bugs']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">In Progress</h5>
                        <p class="card-text display-6 text-warning"><?php echo $stats['in_progress_bugs']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Resolved</h5>
                        <p class="card-text display-6 text-success"><?php echo $stats['resolved_bugs']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Closed</h5>
                        <p class="card-text display-6 text-secondary"><?php echo $stats['closed_bugs']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Reopened</h5>
                        <p class="card-text display-6 text-danger"><?php echo $stats['reopened_bugs']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="assigned" <?php echo $status_filter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="reopened" <?php echo $status_filter === 'reopened' ? 'selected' : ''; ?>>Reopened</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="project" class="form-label">Project</label>
                        <select class="form-select" id="project" name="project">
                            <option value="0">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>" <?php echo $project_filter == $project['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">All Priorities</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="critical" <?php echo $priority_filter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search bugs..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bugs Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php if (empty($bugs)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No bugs found matching your criteria.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Project</th>
                                    <th>Priority</th>
                                    <th>Status</th>
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
                                        <td><?php echo htmlspecialchars($bug['project_name']); ?></td>
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
                                            <?php if ($bug['assigned_to']): ?>
                                                <?php echo htmlspecialchars($bug['assigned_to_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($bug['created_at'])); ?></td>
                                        <td>
                                            <a href="view_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($bug['status'] === 'resolved'): ?>
                                                <a href="close_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="reopen_bug.php?id=<?php echo $bug['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-redo"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&project=<?php echo $project_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo $search; ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">Previous</span>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&project=<?php echo $project_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo $search; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&project=<?php echo $project_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo $search; ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">Next</span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>