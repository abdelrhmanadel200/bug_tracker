<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_mode = $project_id > 0;

// Initialize variables
$name = '';
$description = '';
$status = 'active';
$errors = [];

// If in edit mode, get project data
if ($edit_mode) {
    $project_query = "SELECT * FROM projects WHERE id = ?";
    $project_stmt = $conn->prepare($project_query);
    $project_stmt->bind_param("i", $project_id);
    $project_stmt->execute();
    $project_result = $project_stmt->get_result();

    if ($project_result->num_rows === 0) {
        header('Location: projects.php');
        exit();
    }

    $project = $project_result->fetch_assoc();
    $name = $project['name'];
    $description = $project['description'];
    $status = $project['status'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];

    if (empty($name)) {
        $errors[] = "Project name is required.";
    }

    if (empty($errors)) {
        if ($edit_mode) {
            // Update existing project
            $update_query = "UPDATE projects SET name = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssi", $name, $description, $status, $project_id);
            
            if ($update_stmt->execute()) {
                // Log the activity
                $user_id = $_SESSION['user_id'];
                $action = "Updated project: $name (ID: $project_id)";
                // log_activity($conn, $user_id, null, $action);
                
                header('Location: projects.php?success=updated');
                exit();
            } else {
                $errors[] = "Error updating project: " . $conn->error;
            }
        } else {
            // Create new project
            $user_id = $_SESSION['user_id'];
            $insert_query = "INSERT INTO projects (name, description, status, created_by, created_at) VALUES (?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sssi", $name, $description, $status, $user_id);
            
            if ($insert_stmt->execute()) {
                $new_project_id = $conn->insert_id;
                
                // Log the activity
                $action = "Created new project: $name (ID: $new_project_id)";
                // log_activity($conn, $user_id, null, $action);
                
                header('Location: projects.php?success=created');
                exit();
            } else {
                $errors[] = "Error creating project: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit Project' : 'Add New Project'; ?> - BugTracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo $edit_mode ? 'Edit Project' : 'Add New Project'; ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($description); ?></textarea>
                                <div class="form-text">Provide a detailed description of the project.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="projects.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $edit_mode ? 'Update Project' : 'Create Project'; ?>
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