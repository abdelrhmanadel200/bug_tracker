<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Handle user status change
if (isset($_POST['change_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    // Validate status
    $valid_statuses = ['active', 'inactive', 'banned'];
    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $user_id);
        
        if ($update_stmt->execute()) {
            $status_success = "User status updated successfully.";
            
            // Log the activity
            $admin_id = $_SESSION['user_id'];
            $action = "Changed user ID: $user_id status to $new_status";
            // log_activity($conn, $admin_id, null, $action);
        } else {
            $status_error = "Error updating user status: " . $conn->error;
        }
    } else {
        $status_error = "Invalid status value.";
    }
}

// Get all users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter options
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build query based on filters
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($search_term)) {
    $query .= " AND (fullname LIKE ? OR email LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
$users = $result->fetch_all(MYSQLI_ASSOC);

// Get total users count for pagination
$count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$count_params = [];

if (!empty($role_filter)) {
    $count_query .= " AND role = ?";
    $count_params[] = $role_filter;
}

if (!empty($status_filter)) {
    $count_query .= " AND status = ?";
    $count_params[] = $status_filter;
}

if (!empty($search_term)) {
    $count_query .= " AND (fullname LIKE ? OR email LIKE ?)";
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
$total_users = $count_row['total'];
$total_pages = ceil($total_users / $limit);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Manage Users</h1>
        <a href="add_staff.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Staff
        </a>
    </div>

    <?php if (isset($status_success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $status_success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($status_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $status_error; ?>
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
                <div class="col-md-3">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Banned</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="users.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">All Users (<?php echo $total_users; ?>)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive"  >
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($user['profile_image'])): ?>
                                                <img src="../uploads/profile/<?php echo $user['profile_image']; ?>" alt="Profile" class="rounded-circle me-2" width="40" height="40">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                    <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($user['fullname']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php
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
                                        ?>
                                        <span class="badge <?php echo $role_class; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
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
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $user['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $user['id']; ?>">
                                                <li>
                                                    <a class="dropdown-item " href="view_user.php?id=<?php echo $user['id']; ?>">
                                                        <i class="fas fa-eye me-2"></i> View Details
                                                    </a>
                                                </li>
                                                <?php if ($user['role'] === 'staff'): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="add_staff.php?id=<?php echo $user['id']; ?>">
                                                            <i class="fas fa-edit me-2"></i> Edit
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-success change-status" href="#" 
                                                       data-user-id="<?php echo $user['id']; ?>" 
                                                       data-status="active"
                                                       data-user-name="<?php echo htmlspecialchars($user['fullname']); ?>">
                                                        <i class="fas fa-check-circle me-2"></i> Set Active
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-warning change-status" href="#" 
                                                       data-user-id="<?php echo $user['id']; ?>" 
                                                       data-status="inactive"
                                                       data-user-name="<?php echo htmlspecialchars($user['fullname']); ?>">
                                                        <i class="fas fa-pause-circle me-2"></i> Set Inactive
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger change-status" href="#" 
                                                       data-user-id="<?php echo $user['id']; ?>" 
                                                       data-status="banned"
                                                       data-user-name="<?php echo htmlspecialchars($user['fullname']); ?>">
                                                        <i class="fas fa-ban me-2"></i> Ban User
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Single View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewUserModalBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading user details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="editUserLink" class="btn btn-primary d-none">Edit User</a>
            </div>
        </div>
    </div>
</div>

<!-- Single Change Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">Change User Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="changeStatusForm">
                <div class="modal-body">
                    <p>Are you sure you want to change the status of <strong id="statusUserName"></strong>?</p>
                    <input type="hidden" name="user_id" id="statusUserId" value="">
                    <input type="hidden" name="new_status" id="newStatusValue" value="">
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input status-option" type="radio" name="status_option" id="statusActive" value="active">
                        <label class="form-check-label" for="statusActive">
                            <span class="badge bg-success">Active</span> - User can log in and use the system
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input status-option" type="radio" name="status_option" id="statusInactive" value="inactive">
                        <label class="form-check-label" for="statusInactive">
                            <span class="badge bg-warning">Inactive</span> - User account is temporarily disabled
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input status-option" type="radio" name="status_option" id="statusBanned" value="banned">
                        <label class="form-check-label" for="statusBanned">
                            <span class="badge bg-danger">Banned</span> - User is permanently banned from the system
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_status" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View User Modal
    const viewUserModal = document.getElementById('viewUserModal');
    const viewUserModalInstance = new bootstrap.Modal(viewUserModal);
    
    // Change Status Modal
    const changeStatusModal = document.getElementById('changeStatusModal');
    const changeStatusModalInstance = new bootstrap.Modal(changeStatusModal);
    
    // Handle View User clicks
    document.querySelectorAll('.view-user').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            
            // Show the modal with loading state
            document.getElementById('viewUserModalBody').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading user details...</p>
                </div>
            `;
            
            viewUserModalInstance.show();
            
            // Fetch user details via AJAX
            fetch(`get_user_details.php?id=${userId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('viewUserModalBody').innerHTML = html;
                    
                    // Show edit button if staff
                    const isStaff = document.getElementById('viewUserModalBody').querySelector('[data-role="staff"]');
                    const editLink = document.getElementById('editUserLink');
                    
                    if (isStaff) {
                        editLink.href = `add_staff.php?id=${userId}`;
                        editLink.classList.remove('d-none');
                    } else {
                        editLink.classList.add('d-none');
                    }
                })
                .catch(error => {
                    document.getElementById('viewUserModalBody').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading user details. Please try again.
                        </div>
                    `;
                    console.error('Error fetching user details:', error);
                });
        });
    });
    
    // Handle Change Status clicks
    document.querySelectorAll('.change-status').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            const status = this.getAttribute('data-status');
            
            // Set values in the modal
            document.getElementById('statusUserId').value = userId;
            document.getElementById('statusUserName').textContent = userName;
            document.getElementById('newStatusValue').value = status;
            
            // Check the appropriate radio button
            document.querySelectorAll('.status-option').forEach(function(radio) {
                radio.checked = radio.value === status;
            });
            
            // Show the modal
            changeStatusModalInstance.show();
        });
    });
    
    // Update hidden status field when radio buttons change
    document.querySelectorAll('.status-option').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.getElementById('newStatusValue').value = this.value;
        });
    });
    
    // Clean up modals when hidden
    viewUserModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('viewUserModalBody').innerHTML = '';
        document.getElementById('editUserLink').classList.add('d-none');
    });
    
    changeStatusModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('changeStatusForm').reset();
        document.getElementById('statusUserId').value = '';
        document.getElementById('statusUserName').textContent = '';
        document.getElementById('newStatusValue').value = '';
    });
});
</script>
<style>
    .table-responsive {
        overflow: visible;
    }
    </style>
</body>
</html>