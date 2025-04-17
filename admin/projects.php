<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Handle project deletion
if (isset($_POST['delete_project'])) {
    $project_id = (int)$_POST['project_id'];
    
    // Check if there are bugs associated with this project
    $check_bugs_query = "SELECT COUNT(*) as bug_count FROM bugs WHERE project_id = ?";
    $check_stmt = $conn->prepare($check_bugs_query);
    $check_stmt->bind_param("i", $project_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $bug_count = $check_result->fetch_assoc()['bug_count'];
    
    if ($bug_count > 0) {
        $delete_error = "Cannot delete project because it has associated bugs. Please reassign or delete the bugs first.";
    } else {
        // Delete the project
        $delete_query = "DELETE FROM projects WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $project_id);
        
        if ($delete_stmt->execute()) {
            $delete_success = "Project deleted successfully.";
            
            // Log the activity
            $user_id = $_SESSION['user_id'];
            $action = "Deleted project ID: $project_id";
            log_activity($conn, $user_id, null, $action);
        } else {
            $delete_error = "Error deleting project: " . $conn->error;
        }
    }
}

// Get all projects with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter by status if provided
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build query based on filters
$query = "SELECT p.*, u.fullname as created_by_name, 
          (SELECT COUNT(*) FROM bugs WHERE project_id = p.id) as bug_count 
          FROM projects p 
          LEFT JOIN users u ON p.created_by = u.id 
          WHERE 1=1";

$params = [];

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
$count_query = "SELECT COUNT(*) as total FROM projects WHERE 1=1";
$count_params = [];

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
        <h1 class="h3">Manage Projects</h1>
        <a href="add_project.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Project
        </a>
    </div>

    <?php if (isset($delete_success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $delete_success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($delete_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $delete_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or description" value="<?php echo htmlspecialchars($search_term); ?>">
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
                            <th>Bugs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No projects found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td><?php echo $project['id']; ?></td>
                                    <td>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $project['id']; ?>">
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
                                        <a href="bugs.php?project_id=<?php echo $project['id']; ?>" class="badge bg-info text-decoration-none">
                                            <?php echo $project['bug_count']; ?> bugs
                                        </a>
                                    </td>
                                    <td>
                                        <a href="add_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $project['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Project Details Modal -->
                                <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="projectModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="projectModalLabel<?php echo $project['id']; ?>">Project Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h4><?php echo htmlspecialchars($project['name']); ?></h4>
                                                <p class="text-muted">Created on <?php echo date('F d, Y', strtotime($project['created_at'])); ?> by <?php echo htmlspecialchars($project['created_by_name']); ?></p>
                                                
                                                <div class="mb-3">
                                                    <h5>Description</h5>
                                                    <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h5>Status</h5>
                                                        <p><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($project['status']); ?></span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5>Bug Count</h5>
                                                        <p><?php echo $project['bug_count']; ?> bugs</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <a href="add_project.php?id=<?php echo $project['id']; ?>" class="btn btn-warning">Edit Project</a>
                                                <a href="bugs.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary">View Bugs</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $project['id']; ?>">Confirm Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete the project: <strong><?php echo htmlspecialchars($project['name']); ?></strong>?</p>
                                                <?php if ($project['bug_count'] > 0): ?>
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> This project has <?php echo $project['bug_count']; ?> associated bugs. You must reassign or delete these bugs before deleting the project.
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                    <button type="submit" name="delete_project" class="btn btn-danger" <?php echo $project['bug_count'] > 0 ? 'disabled' : ''; ?>>Delete Project</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>