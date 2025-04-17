<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Get all bugs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter options
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$project_filter = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Build query based on filters
$query = "SELECT b.*, p.name as project_name, 
          u1.fullname as reported_by_name, 
          u2.fullname as assigned_to_name 
          FROM bugs b 
          LEFT JOIN projects p ON b.project_id = p.id 
          LEFT JOIN users u1 ON b.reported_by = u1.id 
          LEFT JOIN users u2 ON b.assigned_to = u2.id 
          WHERE 1=1";

$params = [];

if (!empty($status_filter)) {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
}

if (!empty($priority_filter)) {
    $query .= " AND b.priority = ?";
    $params[] = $priority_filter;
}

if ($project_filter > 0) {
    $query .= " AND b.project_id = ?";
    $params[] = $project_filter;
}

$query .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
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
$bugs = $result->fetch_all(MYSQLI_ASSOC);

// Get total bugs count for pagination
$count_query = "SELECT COUNT(*) as total FROM bugs WHERE 1=1";
$count_params = [];

if (!empty($status_filter)) {
    $count_query .= " AND status = ?";
    $count_params[] = $status_filter;
}

if (!empty($priority_filter)) {
    $count_query .= " AND priority = ?";
    $count_params[] = $priority_filter;
}

if ($project_filter > 0) {
    $count_query .= " AND project_id = ?";
    $count_params[] = $project_filter;
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
$total_bugs = $count_row['total'];
$total_pages = ceil($total_bugs / $limit);

// Get all projects for filter dropdown
$projects_query = "SELECT id, name FROM projects ORDER BY name";
$projects_result = $conn->query($projects_query);
$projects = $projects_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bugs - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Manage Bugs</h1>
            <a href="view_bug.php?action=new" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Bug
            </a>
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
                            <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="assigned" <?php echo $status_filter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="reopened" <?php echo $status_filter === 'reopened' ? 'selected' : ''; ?>>Reopened</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select name="priority" id="priority" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="critical" <?php echo $priority_filter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="project_id" class="form-label">Project</label>
                        <select name="project_id" id="project_id" class="form-select">
                            <option value="0">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>" <?php echo $project_filter === $project['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="bugs.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bugs Table -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">All Bugs (<?php echo $total_bugs; ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Title</th>
                                <th>Project</th>
                                <th>Reported By</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bugs)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No bugs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bugs as $bug): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($bug['ticket_number']); ?></td>
                                        <td><?php echo htmlspecialchars($bug['title']); ?></td>
                                        <td><?php echo htmlspecialchars($bug['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($bug['reported_by_name']); ?></td>
                                        <td>
                                            <?php echo $bug['assigned_to_name'] ? htmlspecialchars($bug['assigned_to_name']) : '<span class="badge bg-warning">Unassigned</span>'; ?>
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
                                            <a href="view_bug.php?id=<?php echo $bug['id']; ?>&action=edit" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&project_id=<?php echo $project_filter; ?>">
                                    Previous
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&project_id=<?php echo $project_filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&project_id=<?php echo $project_filter; ?>">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>