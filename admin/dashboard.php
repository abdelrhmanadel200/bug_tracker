<?php
// Include configuration
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Get statistics
// Total bugs
$sql = "SELECT COUNT(*) as total FROM bugs";
$result = $conn->query($sql);
$total_bugs = $result->fetch_assoc()['total'];

// Open bugs
$sql = "SELECT COUNT(*) as open FROM bugs WHERE status NOT IN (?, ?)";
$stmt = $conn->prepare($sql);
$closed = STATUS_CLOSED;
$resolved = STATUS_RESOLVED;
$stmt->bind_param("ss", $closed, $resolved);
$stmt->execute();
$result = $stmt->get_result();
$open_bugs = $result->fetch_assoc()['open'];

// Resolved bugs
$sql = "SELECT COUNT(*) as resolved FROM bugs WHERE status = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $resolved);
$stmt->execute();
$result = $stmt->get_result();
$resolved_bugs = $result->fetch_assoc()['resolved'];

// Closed bugs
$sql = "SELECT COUNT(*) as closed FROM bugs WHERE status = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $closed);
$stmt->execute();
$result = $stmt->get_result();
$closed_bugs = $result->fetch_assoc()['closed'];

// Total users
$sql = "SELECT COUNT(*) as total FROM users";
$result = $conn->query($sql);
$total_users = $result->fetch_assoc()['total'];

// Total staff
$sql = "SELECT COUNT(*) as total FROM users WHERE role = ?";
$stmt = $conn->prepare($sql);
$staff = ROLE_STAFF;
$stmt->bind_param("s", $staff);
$stmt->execute();
$result = $stmt->get_result();
$total_staff = $result->fetch_assoc()['total'];

// Total customers
$sql = "SELECT COUNT(*) as total FROM users WHERE role = ?";
$stmt = $conn->prepare($sql);
$customer = ROLE_CUSTOMER;
$stmt->bind_param("s", $customer);
$stmt->execute();
$result = $stmt->get_result();
$total_customers = $result->fetch_assoc()['total'];

// Total projects
$sql = "SELECT COUNT(*) as total FROM projects";
$result = $conn->query($sql);
$total_projects = $result->fetch_assoc()['total'];

// Recent bugs
$sql = "SELECT b.*, p.name as project_name, u.fullname as reported_by_name 
        FROM bugs b 
        JOIN projects p ON b.project_id = p.id 
        JOIN users u ON b.reported_by = u.id 
        ORDER BY b.created_at DESC LIMIT 5";
$result = $conn->query($sql);
$recent_bugs = $result->fetch_all(MYSQLI_ASSOC);

// Recent users
$sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);
$recent_users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <main class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-tachometer-alt text-primary me-2"></i>Admin Dashboard</h1>
                    <div>
                        <a href="add_project.php" class="btn btn-primary me-2">
                            <i class="fas fa-plus me-2"></i>Add Project
                        </a>
                        <a href="add_staff.php" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>Add Staff
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Bugs</h5>
                        <p class="card-text display-4"><?php echo $total_bugs; ?></p>
                        <a href="bugs.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Open Bugs</h5>
                        <p class="card-text display-4"><?php echo $open_bugs; ?></p>
                        <a href="bugs.php?status=open" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text display-4"><?php echo $total_users; ?></p>
                        <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Projects</h5>
                        <p class="card-text display-4"><?php echo $total_projects; ?></p>
                        <a href="projects.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bug me-2"></i>Recent Bugs</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_bugs) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Reported By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bugs as $bug): ?>
                                        <tr>
                                            <td>#<?php echo $bug['id']; ?></td>
                                            <td><?php echo htmlspecialchars($bug['title']); ?></td>
                                            <td><?php echo get_status_badge($bug['status']); ?></td>
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
                                <a href="bugs.php" class="btn btn-primary">View All Bugs</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No bugs reported yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Recent Users</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_users) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td>#<?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><span class="badge bg-<?php echo $user['role'] === ROLE_ADMIN ? 'danger' : ($user['role'] === ROLE_STAFF ? 'primary' : 'secondary'); ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                            <td>
                                                <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <a href="users.php" class="btn btn-success">View All Users</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No users registered yet.
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
                                    <h5>Total Bugs</h5>
                                    <h2 class="text-primary"><?php echo $total_bugs; ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="p-3">
                                    <h5>Open Bugs</h5>
                                    <h2 class="text-warning"><?php echo $open_bugs; ?></h2>
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