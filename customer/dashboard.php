<?php
// Include configuration
require_once '../config/config.php';

// Check if user is logged in and is a customer
if (!is_logged_in() || !is_customer()) {
    redirect('../login.php');
}

// Get user's bugs
$user_id = $_SESSION['user_id'];
$sql = "SELECT b.*, p.name as project_name FROM bugs b 
        JOIN projects p ON b.project_id = p.id 
        WHERE b.reported_by = ? 
        ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bugs = $stmt->get_result();

// Get available projects
$sql = "SELECT id, name FROM projects WHERE status = 'active'";
$result = $conn->query($sql);
$projects = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/customer_header.php'; ?>

    <main class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-tachometer-alt text-primary me-2"></i>Customer Dashboard</h1>
                    <a href="submit_bug.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Report New Bug
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Bugs</h5>
                        <p class="card-text display-4"><?php echo $bugs->num_rows; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Open Bugs</h5>
                        <?php
                        $open_bugs = 0;
                        $bugs->data_seek(0);
                        while ($bug = $bugs->fetch_assoc()) {
                            if ($bug['status'] !== STATUS_CLOSED && $bug['status'] !== STATUS_RESOLVED) {
                                $open_bugs++;
                            }
                        }
                        ?>
                        <p class="card-text display-4"><?php echo $open_bugs; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Resolved Bugs</h5>
                        <?php
                        $resolved_bugs = 0;
                        $bugs->data_seek(0);
                        while ($bug = $bugs->fetch_assoc()) {
                            if ($bug['status'] === STATUS_RESOLVED) {
                                $resolved_bugs++;
                            }
                        }
                        ?>
                        <p class="card-text display-4"><?php echo $resolved_bugs; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">Closed Bugs</h5>
                        <?php
                        $closed_bugs = 0;
                        $bugs->data_seek(0);
                        while ($bug = $bugs->fetch_assoc()) {
                            if ($bug['status'] === STATUS_CLOSED) {
                                $closed_bugs++;
                            }
                        }
                        ?>
                        <p class="card-text display-4"><?php echo $closed_bugs; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bug me-2"></i>My Reported Bugs</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($bugs->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Project</th>
                                            <th>Status</th>
                                            <th>Priority</th>
                                            <th>Reported On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $bugs->data_seek(0);
                                        while ($bug = $bugs->fetch_assoc()):
                                        ?>
                                        <tr class="bug-priority-<?php echo $bug['priority']; ?>">
                                            <td>#<?php echo $bug['id']; ?></td>
                                            <td><?php echo htmlspecialchars($bug['title']); ?></td>
                                            <td><?php echo htmlspecialchars($bug['project_name']); ?></td>
                                            <td><?php echo get_status_badge($bug['status']); ?></td>
                                            <td><?php echo get_priority_badge($bug['priority']); ?></td>
                                            <td><?php echo format_datetime($bug['created_at']); ?></td>
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
                                <i class="fas fa-info-circle me-2"></i>You haven't reported any bugs yet.
                                <a href="submit_bug.php" class="alert-link">Report a bug now</a>.
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