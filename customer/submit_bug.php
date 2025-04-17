<?php
// Include configuration
require_once '../config/config.php';

// Check if user is logged in and is a customer
if (!is_logged_in() || !is_customer()) {
    redirect('../login.php');
}

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
    <title>Submit Bug - BugTracker</title>
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
                        <li class="breadcrumb-item active" aria-current="page">Submit Bug</li>
                    </ol>
                </nav>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bug me-2"></i>Report a New Bug</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Display error message if any
                        if (isset($_GET['error'])) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
                        }
                        
                        // Display success message if any
                        if (isset($_GET['success'])) {
                            echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
                        }
                        ?>
                        
                        <form action="process_bug_submission.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="project_id" class="form-label">Project</label>
                                <select class="form-select" id="project_id" name="project_id" required>
                                    <option value="">Select Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Bug Title</label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="Brief description of the bug" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Bug Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Detailed description of the bug, steps to reproduce, expected behavior, etc." required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="priority" class="form-label">Priority</label>
                                        <select class="form-select" id="priority" name="priority" required>
                                            <option value="low">Low</option>
                                            <option value="medium" selected>Medium</option>
                                            <option value="high">High</option>
                                            <option value="critical">Critical</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="environment" class="form-label">Environment</label>
                                        <input type="text" class="form-control" id="environment" name="environment" placeholder="OS, Browser, Device, etc.">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="screenshot" class="form-label">Screenshot (Optional)</label>
                                <input type="file" class="form-control" id="screenshot" name="screenshot" accept="image/*">
                                <div class="form-text">Upload a screenshot of the bug (Max size: 5MB, Allowed formats: JPG, JPEG, PNG, GIF)</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-undo me-2"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Bug
                                </button>
                            </div>
                        </form>
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