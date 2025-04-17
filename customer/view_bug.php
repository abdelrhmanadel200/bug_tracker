<?php
// Include configuration
require_once '../config/config.php';

// Check if user is logged in and is a customer
if (!is_logged_in() || !is_customer()) {
    redirect('../login.php');
}

// Check if bug ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php?error=Invalid bug ID');
}

$bug_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get bug details
$sql = "SELECT b.*, p.name as project_name, u.fullname as reported_by_name, 
        s.fullname as assigned_to_name 
        FROM bugs b 
        JOIN projects p ON b.project_id = p.id 
        JOIN users u ON b.reported_by = u.id 
        LEFT JOIN users s ON b.assigned_to = s.id 
        WHERE b.id = ? AND b.reported_by = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bug_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('dashboard.php?error=Bug not found or you do not have permission to view it');
}

$bug = $result->fetch_assoc();

// Get bug comments
$sql = "SELECT c.*, u.fullname, u.role 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.bug_id = ? 
        ORDER BY c.created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bug_id);
$stmt->execute();
$comments = $stmt->get_result();

// Get bug history
$sql = "SELECT h.*, u.fullname 
        FROM bug_history h 
        JOIN users u ON h.user_id = u.id 
        WHERE h.bug_id = ? 
        ORDER BY h.created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bug_id);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bug #<?php echo $bug_id; ?> - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/customer_header.php'; ?>

    <main class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Bug #<?php echo $bug_id; ?></li>
                    </ol>
                </nav>
                
                <?php
                // Display success message if any
                if (isset($_GET['success'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
                }
                ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-bug me-2"></i>Bug Details</h5>
                        <span class="badge bg-light text-dark">Ticket: <?php echo $bug['ticket_number']; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4><?php echo htmlspecialchars($bug['title']); ?></h4>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-secondary">Project: <?php echo htmlspecialchars($bug['project_name']); ?></span>
                                    <?php echo get_status_badge($bug['status']); ?>
                                    <?php echo get_priority_badge($bug['priority']); ?>
                                    <?php if (!empty($bug['environment'])): ?>
                                        <span class="badge bg-info text-dark">Environment: <?php echo htmlspecialchars($bug['environment']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="bug-info mb-4">
                                    <h5>Description</h5>
                                    <p><?php echo nl2br(htmlspecialchars($bug['description'])); ?></p>
                                </div>
                                
                                <?php if ($bug['screenshot']): ?>
                                    <div class="mb-4">
                                        <h5>Screenshot</h5>
                                        <img src="<?php echo $bug['screenshot']; ?>" alt="Bug Screenshot" class="img-fluid img-thumbnail" style="max-width: 100%; max-height: 400px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Bug Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>Reported By</span>
                                                <span><?php echo htmlspecialchars($bug['reported_by_name']); ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>Reported On</span>
                                                <span><?php echo format_datetime($bug['created_at']); ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>Assigned To</span>
                                                <span><?php echo $bug['assigned_to_name'] ? htmlspecialchars($bug['assigned_to_name']) : 'Not Assigned'; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>Last Updated</span>
                                                <span><?php echo $bug['updated_at'] ? format_datetime($bug['updated_at']) : 'Not Updated'; ?></span>
                                            </li>
                                            <?php if ($bug['resolved_at']): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span>Resolved On</span>
                                                    <span><?php echo format_datetime($bug['resolved_at']); ?></span>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ($bug['closed_at']): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span>Closed On</span>
                                                    <span><?php echo format_datetime($bug['closed_at']); ?></span>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Comments Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Comments</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($comments->num_rows > 0): ?>
                            <?php while ($comment = $comments->fetch_assoc()): ?>
                                <div class="comment-box mb-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <span class="comment-author"><?php echo htmlspecialchars($comment['fullname']); ?></span>
                                            <span class="badge bg-secondary"><?php echo ucfirst($comment['role']); ?></span>
                                        </div>
                                        <small class="text-muted"><?php echo format_datetime($comment['created_at']); ?></small>
                                    </div>
                                    <hr class="my-2">
                                    <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No comments yet.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Add Comment Form -->
                        <form action="add_comment.php" method="post">
                            <input type="hidden" name="bug_id" value="<?php echo $bug_id; ?>">
                            <div class="mb-3">
                                <label for="comment" class="form-label">Add a Comment</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Post Comment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Bug History Section -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Bug History</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($history->num_rows > 0): ?>
                            <div class="timeline">
                                <?php while ($entry = $history->fetch_assoc()): ?>
                                    <div class="timeline-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($entry['fullname']); ?></strong>
                                                <span><?php echo htmlspecialchars($entry['action']); ?></span>
                                            </div>
                                            <small class="text-muted"><?php echo format_datetime($entry['created_at']); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No history available.
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