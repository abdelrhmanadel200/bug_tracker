    <?php
    require_once '../config/config.php';
    require_once '../config/database.php';
    require_once '../includes/functions.php';

    // Check if admin is logged in
    // if (!is_logged_in() || !is_admin()) {
    //     redirect('../login.php');
    // }

    // Helper function to safely get values from arrays
    function safe_get($array, $key, $default = '') {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    $user_id = $_SESSION['user_id'];
    $action = safe_get($_GET, 'action', '');
    $bug_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $edit_mode = ($action === 'edit' && $bug_id > 0);
    $new_mode = ($action === 'new');

    // Initialize variables
    $title = '';
    $description = '';
    $project_id = '';
    $reported_by = '';
    $assigned_to = '';
    $priority = 'medium';
    $status = 'new';
    $environment = '';
    $screenshot = '';
    $ticket_number = '';
    $errors = [];
    $success_message = '';
    $bug = []; // Initialize bug array

    // Get all projects for dropdown
    $projects_query = "SELECT id, name FROM projects WHERE status = 'active' ORDER BY name";
    $projects_result = $conn->query($projects_query);
    $projects = $projects_result->fetch_all(MYSQLI_ASSOC);

    // Get all staff members for assignment dropdown
    $staff_query = "SELECT id, fullname FROM users WHERE role IN ('admin', 'staff') AND status = 'active' ORDER BY fullname";
    $staff_result = $conn->query($staff_query);
    $staff = $staff_result->fetch_all(MYSQLI_ASSOC);

    // Get all customers for reported by dropdown (only needed for admin creating a bug)
    $customers_query = "SELECT id, fullname FROM users WHERE role = 'customer' AND status = 'active' ORDER BY fullname";
    $customers_result = $conn->query($customers_query);
    $customers = $customers_result->fetch_all(MYSQLI_ASSOC);

    // If viewing or editing an existing bug
    if ($bug_id > 0) {
        $bug_query = "SELECT b.*, 
                    p.name as project_name,
                    u1.fullname as reported_by_name,
                    u2.fullname as assigned_to_name
                    FROM bugs b
                    LEFT JOIN projects p ON b.project_id = p.id
                    LEFT JOIN users u1 ON b.reported_by = u1.id
                    LEFT JOIN users u2 ON b.assigned_to = u2.id
                    WHERE b.id = ?";
        $bug_stmt = $conn->prepare($bug_query);
        $bug_stmt->bind_param("i", $bug_id);
        $bug_stmt->execute();
        $bug_result = $bug_stmt->get_result();
        
        if ($bug_result->num_rows === 0) {
            header('Location: bugs.php');
            exit();
        }
        
        $bug = $bug_result->fetch_assoc();
        $title = safe_get($bug, 'title', '');
        $description = safe_get($bug, 'description', '');
        $project_id = safe_get($bug, 'project_id', '');
        $reported_by = safe_get($bug, 'reported_by', '');
        $assigned_to = safe_get($bug, 'assigned_to', '');
        $priority = safe_get($bug, 'priority', 'medium');
        $status = safe_get($bug, 'status', 'new');
        $environment = safe_get($bug, 'environment', '');
        $screenshot = safe_get($bug, 'screenshot', '');
        $ticket_number = safe_get($bug, 'ticket_number', '');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if it's a status update from quick actions
        if (isset($_POST['status']) && $bug_id > 0 && !isset($_POST['title'])) {
            $new_status = $_POST['status'];
            $update_query = "UPDATE bugs SET status = ?, updated_at = NOW()";
            $params = [$new_status];
            
            // Add resolved_at timestamp if status changed to resolved
            if ($new_status === 'resolved' && $status !== 'resolved') {
                $update_query .= ", resolved_at = NOW()";
            } elseif ($new_status !== 'resolved' && $status === 'resolved') {
                $update_query .= ", resolved_at = NULL";
            }
            
            // Add closed_at timestamp if status changed to closed
            if ($new_status === 'closed' && $status !== 'closed') {
                $update_query .= ", closed_at = NOW()";
            } elseif ($new_status !== 'closed' && $status === 'closed') {
                $update_query .= ", closed_at = NULL";
            }
            
            $update_query .= " WHERE id = ?";
            $params[] = $bug_id;
            
            $update_stmt = $conn->prepare($update_query);
            $types = str_repeat('s', count($params) - 1) . 'i';
            $update_stmt->bind_param($types, ...$params);
            
            if ($update_stmt->execute()) {
                // Log the activity
                $action_text = "Updated bug #$ticket_number status to " . ucfirst(str_replace('_', ' ', $new_status));
                // log_action($conn, $user_id, $bug_id, $action_text);
                
                // Add to bug history
                $history_action = "Changed status from " . ucfirst(str_replace('_', ' ', $status)) . " to " . ucfirst(str_replace('_', ' ', $new_status));
                // add_bug_history($conn, $bug_id, $user_id, $history_action);
                
                // Redirect to refresh the page
                header('Location: view_bug.php?id=' . $bug_id);
                exit();
            }
        } else {
            // Regular form submission for create/edit
            $title = safe_get($_POST, 'title', '');
            $description = safe_get($_POST, 'description', '');
            $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
            $priority = safe_get($_POST, 'priority', 'medium');
            $status = safe_get($_POST, 'status', 'new');
            $environment = safe_get($_POST, 'environment', '');
            
            if ($new_mode || $edit_mode) {
                $reported_by = isset($_POST['reported_by']) ? (int)$_POST['reported_by'] : 0;
                $assigned_to = isset($_POST['assigned_to']) && !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            }
            
            // Validation
            if (empty($title)) {
                $errors[] = "Bug title is required.";
            }
            
            if (empty($description)) {
                $errors[] = "Bug description is required.";
            }
            
            if ($project_id <= 0) {
                $errors[] = "Please select a project.";
            }
            
            if ($new_mode && $reported_by <= 0) {
                $errors[] = "Please select who reported the bug.";
            }
            
            // Handle screenshot upload
            $new_screenshot = '';
            if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['screenshot']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
                } else {
                    $upload_dir = '../uploads/screenshots/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_name = time() . '_' . basename($_FILES['screenshot']['name']);
                    $upload_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $upload_path)) {
                        $new_screenshot = $file_name;
                    } else {
                        $errors[] = "Failed to upload screenshot.";
                    }
                }
            }
            
            if (empty($errors)) {
                if ($edit_mode) {
                    // Update existing bug
                    $update_query = "UPDATE bugs SET 
                                title = ?, 
                                description = ?, 
                                project_id = ?, 
                                assigned_to = ?, 
                                priority = ?, 
                                status = ?, 
                                environment = ?, 
                                updated_at = NOW()";
                    
                    $params = [$title, $description, $project_id, $assigned_to, $priority, $status, $environment];
                    
                    // Add screenshot to update if a new one was uploaded
                    if (!empty($new_screenshot)) {
                        $update_query .= ", screenshot = ?";
                        $params[] = $new_screenshot;
                    }
                    
                    // Add resolved_at timestamp if status changed to resolved
                    if ($status === 'resolved' && safe_get($bug, 'status') !== 'resolved') {
                        $update_query .= ", resolved_at = NOW()";
                    } elseif ($status !== 'resolved' && safe_get($bug, 'status') === 'resolved') {
                        $update_query .= ", resolved_at = NULL";
                    }
                    
                    // Add closed_at timestamp if status changed to closed
                    if ($status === 'closed' && safe_get($bug, 'status') !== 'closed') {
                        $update_query .= ", closed_at = NOW()";
                    } elseif ($status !== 'closed' && safe_get($bug, 'status') === 'closed') {
                        $update_query .= ", closed_at = NULL";
                    }
                    
                    $update_query .= " WHERE id = ?";
                    $params[] = $bug_id;
                    
                    $update_stmt = $conn->prepare($update_query);
                    $types = str_repeat('s', count($params) - 1) . 'i'; // All strings except the last one (bug_id) which is an integer
                    $update_stmt->bind_param($types, ...$params);
                    
                    if ($update_stmt->execute()) {
                        // Log the activity
                        $action_text = "Updated bug #$ticket_number: $title";
                        // log_action($conn, $user_id, $bug_id, $action_text);
                        
                        // Add to bug history
                        $history_action = "Updated bug details";
                        // add_bug_history($conn, $bug_id, $user_id, $history_action);
                        
                        // If status changed, add to bug history
                        if ($status !== safe_get($bug, 'status')) {
                            $history_action = "Changed status from " . ucfirst(str_replace('_', ' ', safe_get($bug, 'status'))) . " to " . ucfirst(str_replace('_', ' ', $status));
                            // add_bug_history($conn, $bug_id, $user_id, $history_action);
                        }
                        
                        // If assigned_to changed, add to bug history
                        if ($assigned_to !== safe_get($bug, 'assigned_to')) {
                            if (empty(safe_get($bug, 'assigned_to'))) {
                                $staff_name_query = "SELECT fullname FROM users WHERE id = ?";
                                $staff_name_stmt = $conn->prepare($staff_name_query);
                                $staff_name_stmt->bind_param("i", $assigned_to);
                                $staff_name_stmt->execute();
                                $staff_name_result = $staff_name_stmt->get_result();
                                $staff_name_row = $staff_name_result->fetch_assoc();
                                $staff_name = safe_get($staff_name_row, 'fullname', 'Unknown');
                                
                                $history_action = "Assigned bug to $staff_name";
                            } elseif (empty($assigned_to)) {
                                $history_action = "Unassigned bug";
                            } else {
                                $staff_name_query = "SELECT fullname FROM users WHERE id = ?";
                                $staff_name_stmt = $conn->prepare($staff_name_query);
                                $staff_name_stmt->bind_param("i", $assigned_to);
                                $staff_name_stmt->execute();
                                $staff_name_result = $staff_name_stmt->get_result();
                                $staff_name_row = $staff_name_result->fetch_assoc();
                                $staff_name = safe_get($staff_name_row, 'fullname', 'Unknown');
                                
                                $history_action = "Reassigned bug to $staff_name";
                            }
                            // add_bug_history($conn, $bug_id, $user_id, $history_action);
                        }
                        
                        $success_message = "Bug updated successfully.";
                        
                        // Refresh bug data
                        $bug_stmt->execute();
                        $bug_result = $bug_stmt->get_result();
                        $bug = $bug_result->fetch_assoc();
                        $title = safe_get($bug, 'title', '');
                        $description = safe_get($bug, 'description', '');
                        $project_id = safe_get($bug, 'project_id', '');
                        $reported_by = safe_get($bug, 'reported_by', '');
                        $assigned_to = safe_get($bug, 'assigned_to', '');
                        $priority = safe_get($bug, 'priority', 'medium');
                        $status = safe_get($bug, 'status', 'new');
                        $environment = safe_get($bug, 'environment', '');
                        $screenshot = safe_get($bug, 'screenshot', '');
                        $ticket_number = safe_get($bug, 'ticket_number', '');
                    } else {
                        $errors[] = "Error updating bug: " . $conn->error;
                    }
                } elseif ($new_mode) {
                    // Generate ticket number
                    $ticket_number = 'BUG-' . date('Ymd') . '-' . strtoupper(generate_random_string(6));
                    
                    // Create new bug
                    $insert_query = "INSERT INTO bugs (
                                project_id, 
                                reported_by, 
                                assigned_to, 
                                title, 
                                description, 
                                priority, 
                                status, 
                                environment, 
                                screenshot, 
                                ticket_number, 
                                created_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("iiisssssss", 
                                        $project_id, 
                                        $reported_by, 
                                        $assigned_to, 
                                        $title, 
                                        $description, 
                                        $priority, 
                                        $status, 
                                        $environment, 
                                        $new_screenshot, 
                                        $ticket_number);
                    
                    if ($insert_stmt->execute()) {
                        $new_bug_id = $conn->insert_id;
                        
                        // Log the activity
                        $action_text = "Created new bug #$ticket_number: $title";
                        // log_action($conn, $user_id, $new_bug_id, $action_text);
                        
                        // Add to bug history
                        $history_action = "Created bug";
                        // add_bug_history($conn, $new_bug_id, $user_id, $history_action);
                        
                        // If assigned to someone, add to bug history
                        if (!empty($assigned_to)) {
                            $staff_name_query = "SELECT fullname FROM users WHERE id = ?";
                            $staff_name_stmt = $conn->prepare($staff_name_query);
                            $staff_name_stmt->bind_param("i", $assigned_to);
                            $staff_name_stmt->execute();
                            $staff_name_result = $staff_name_stmt->get_result();
                            $staff_name_row = $staff_name_result->fetch_assoc();
                            $staff_name = safe_get($staff_name_row, 'fullname', 'Unknown');
                            
                            $history_action = "Assigned bug to $staff_name";
                            // add_bug_history($conn, $new_bug_id, $user_id, $history_action);
                        }
                        
                        header('Location: view_bug.php?id=' . $new_bug_id . '&success=created');
                        exit();
                    } else {
                        $errors[] = "Error creating bug: " . $conn->error;
                    }
                }
            }
        }
    }

    // Get bug comments if viewing an existing bug
    $comments = [];
    $history = [];
    if ($bug_id > 0 && !$new_mode) {
        $comments_query = "SELECT c.*, u.fullname, u.role, u.profile_image 
                        FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.bug_id = ? 
                        ORDER BY c.created_at ASC";
        $comments_stmt = $conn->prepare($comments_query);
        $comments_stmt->bind_param("i", $bug_id);
        $comments_stmt->execute();
        $comments_result = $comments_stmt->get_result();
        $comments = $comments_result->fetch_all(MYSQLI_ASSOC);
        
        // Get bug history
        $history_query = "SELECT h.*, u.fullname, u.role 
                        FROM bug_history h 
                        JOIN users u ON h.user_id = u.id 
                        WHERE h.bug_id = ? 
                        ORDER BY h.created_at ASC";
        $history_stmt = $conn->prepare($history_query);
        $history_stmt->bind_param("i", $bug_id);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        $history = $history_result->fetch_all(MYSQLI_ASSOC);
    }

    // Handle comment submission
    if (isset($_POST['add_comment']) && $bug_id > 0) {
        $comment_text = safe_get($_POST, 'comment', '');
        
        if (empty($comment_text)) {
            $comment_error = "Comment cannot be empty.";
        } else {
            $comment_query = "INSERT INTO comments (bug_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
            $comment_stmt = $conn->prepare($comment_query);
            $comment_stmt->bind_param("iis", $bug_id, $user_id, $comment_text);
            
            if ($comment_stmt->execute()) {
                // Log the activity
                $action_text = "Added comment to bug #$ticket_number";
                // log_action($conn, $user_id, $bug_id, $action_text);
                
                // Add to bug history
                $history_action = "Added a comment";
                // add_bug_history($conn, $bug_id, $user_id, $history_action);
                
                // Refresh the page to show the new comment
                header('Location: view_bug.php?id=' . $bug_id . '#comments');
                exit();
            } else {
                $comment_error = "Error adding comment: " . $conn->error;
            }
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $new_mode ? 'Create New Bug' : ($edit_mode ? 'Edit Bug #' . htmlspecialchars($ticket_number) : 'Bug #' . htmlspecialchars($ticket_number)); ?> - BugTracker</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../assets/css/style.css">
    </head>
    <body>
        <?php include '../includes/admin_header.php'; ?>

        <div class="container-fluid py-4">
            <?php if ($new_mode): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Create New Bug</h1>
                    <a href="bugs.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Bugs
                    </a>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Bug Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Bug Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                    <select class="form-select" id="project_id" name="project_id" required>
                                        <option value="">Select Project</option>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo $project['id']; ?>" <?php echo $project_id == $project['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars(safe_get($project, 'name', '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reported_by" class="form-label">Reported By <span class="text-danger">*</span></label>
                                    <select class="form-select" id="reported_by" name="reported_by" required>
                                        <option value="">Select User</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?php echo $customer['id']; ?>" <?php echo $reported_by == $customer['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars(safe_get($customer, 'fullname', '')); ?> (Customer)
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach ($staff as $staff_member): ?>
                                            <option value="<?php echo $staff_member['id']; ?>" <?php echo $reported_by == $staff_member['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars(safe_get($staff_member, 'fullname', '')); ?> (Staff)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="assigned_to" class="form-label">Assign To</label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($staff as $staff_member): ?>
                                            <option value="<?php echo $staff_member['id']; ?>" <?php echo $assigned_to == $staff_member['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars(safe_get($staff_member, 'fullname', '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="critical" <?php echo $priority === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option>
                                        <option value="assigned" <?php echo $status === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="environment" class="form-label">Environment</label>
                                <input type="text" class="form-control" id="environment" name="environment" value="<?php echo htmlspecialchars($environment); ?>" placeholder="e.g., Browser, OS, Device">
                                <div class="form-text">Specify the environment where the bug occurs (e.g., Chrome 90, Windows 10, iPhone 12).</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="screenshot" class="form-label">Screenshot</label>
                                <input type="file" class="form-control" id="screenshot" name="screenshot">
                                <div class="form-text">Upload a screenshot of the bug (JPG, PNG, or GIF only).</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="bugs.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Create Bug</button>
                            </div>
                        </form>
                    </div>
                </div>
                
            <?php elseif ($edit_mode): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Edit Bug #<?php echo htmlspecialchars($ticket_number); ?></h1>
                    <div>
                        <a href="view_bug.php?id=<?php echo $bug_id; ?>" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> View Bug
                        </a>
                        <a href="bugs.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Bugs
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Edit Bug Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Bug Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                    <select class="form-select" id="project_id" name="project_id" required>
                                        <option value="">Select Project</option>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?php echo $project['id']; ?>" <?php echo $project_id == $project['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars(safe_get($project, 'name', '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reported_by_display" class="form-label">Reported By</label>
                                    <input type="text" class="form-control" id="reported_by_display" value="<?php echo htmlspecialchars(safe_get($bug, 'reported_by_name', '')); ?>" readonly>
                                    <input type="hidden" name="reported_by" value="<?php echo $reported_by; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="assigned_to" class="form-label">Assign To</label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($staff as $staff_member): ?>
                                            <option value="<?php echo $staff_member['id']; ?>" <?php echo $assigned_to == $staff_member['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars(safe_get($staff_member, 'fullname', '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="critical" <?php echo $priority === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option>
                                        <option value="assigned" <?php echo $status === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        <option value="reopened" <?php echo $status === 'reopened' ? 'selected' : ''; ?>>Reopened</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="environment" class="form-label">Environment</label>
                                <input type="text" class="form-control" id="environment" name="environment" value="<?php echo htmlspecialchars($environment); ?>" placeholder="e.g., Browser, OS, Device">
                            </div>
                            
                            <div class="mb-3">
                                <label for="screenshot" class="form-label">Screenshot</label>
                                <?php if (!empty($screenshot)): ?>
                                    <div class="mb-2">
                                        <a href="../uploads/screenshots/<?php echo htmlspecialchars($screenshot); ?>" target="_blank" class="d-inline-block">
                                            <img src="../uploads/screenshots/<?php echo htmlspecialchars($screenshot); ?>" alt="Bug Screenshot" class="img-thumbnail" style="max-height: 150px;">
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="screenshot" name="screenshot">
                                <div class="form-text">Upload a new screenshot to replace the existing one (JPG, PNG, or GIF only).</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="view_bug.php?id=<?php echo $bug_id; ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Bug</button>
                            </div>
                        </form>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- View Bug Mode -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Bug #<?php echo htmlspecialchars($ticket_number); ?></h1>
                    <div>
                        <a href="view_bug.php?id=<?php echo $bug_id; ?>&action=edit" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Edit Bug
                        </a>
                        <a href="bugs.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Bugs
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_GET['success']) && $_GET['success'] === 'created'): ?>
                    <div class="alert alert-success">
                        Bug created successfully.
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <!-- Bug Details -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Bug Details</h5>
                                <span class="badge bg-<?php 
                                    switch ($status) {
                                        case 'new': echo 'info'; break;
                                        case 'assigned': echo 'primary'; break;
                                        case 'in_progress': echo 'warning'; break;
                                        case 'resolved': echo 'success'; break;
                                        case 'closed': echo 'secondary'; break;
                                        case 'reopened': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h4><?php echo htmlspecialchars($title); ?></h4>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <p><strong>Project:</strong> <?php echo htmlspecialchars(safe_get($bug, 'project_name', '')); ?></p>
                                        <p><strong>Reported By:</strong> <?php echo htmlspecialchars(safe_get($bug, 'reported_by_name', '')); ?></p>
                                        <p><strong>Assigned To:</strong> <?php echo safe_get($bug, 'assigned_to_name') ? htmlspecialchars(safe_get($bug, 'assigned_to_name', '')) : '<span class="text-muted">Unassigned</span>'; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Priority:</strong> 
                                            <span class="badge bg-<?php 
                                                switch ($priority) {
                                                    case 'low': echo 'success'; break;
                                                    case 'medium': echo 'info'; break;
                                                    case 'high': echo 'warning'; break;
                                                    case 'critical': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($priority); ?>
                                            </span>
                                        </p>
                                        <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime(safe_get($bug, 'created_at', 'now'))); ?></p>
                                        <?php if (isset($bug['resolved_at']) && $bug['resolved_at']): ?>
                                            <p><strong>Resolved:</strong> <?php echo date('M d, Y H:i', strtotime($bug['resolved_at'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h5>Description</h5>
                                    <div class="p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($description)); ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($environment)): ?>
                                    <div class="mt-4">
                                        <h5>Environment</h5>
                                        <p><?php echo htmlspecialchars($environment); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($screenshot)): ?>
                                    <div class="mt-4">
                                        <h5>Screenshot</h5>
                                        <a href="../uploads/screenshots/<?php echo htmlspecialchars($screenshot); ?>" target="_blank" class="d-inline-block">
                                            <img src="../uploads/screenshots/<?php echo htmlspecialchars($screenshot); ?>" alt="Bug Screenshot" class="img-thumbnail" style="max-width: 100%; max-height: 300px;">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Comments Section -->
                        <div class="card mb-4" id="comments">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Comments (<?php echo count($comments); ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($comments)): ?>
                                    <p class="text-muted">No comments yet.</p>
                                <?php else: ?>
                                    <div class="comments-list">
                                        <?php foreach ($comments as $comment): ?>
                                            <div class="comment mb-4">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0">
                                                        <?php if (!empty(safe_get($comment, 'profile_image', ''))): ?>
                                                            <img src="../uploads/profile/<?php echo htmlspecialchars(safe_get($comment, 'profile_image', '')); ?>" alt="Profile" class="rounded-circle" width="40" height="40">
                                                        <?php else: ?>
                                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <?php echo strtoupper(substr(safe_get($comment, 'fullname', 'U'), 0, 1)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars(safe_get($comment, 'fullname', '')); ?></h6>
                                                                <small class="text-muted">
                                                                    <?php echo ucfirst(safe_get($comment, 'role', '')); ?> • 
                                                                    <?php echo date('M d, Y H:i', strtotime(safe_get($comment, 'created_at', 'now'))); ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2">
                                                            <?php echo nl2br(htmlspecialchars(safe_get($comment, 'comment', ''))); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Add Comment Form -->
                                <div class="mt-4">
                                    <h6>Add a Comment</h6>
                                    <?php if (isset($comment_error)): ?>
                                        <div class="alert alert-danger">
                                            <?php echo htmlspecialchars($comment_error); ?>
                                        </div>
                                    <?php endif; ?>
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <textarea class="form-control" name="comment" rows="3" placeholder="Write your comment here..." required></textarea>
                                        </div>
                                        <button type="submit" name="add_comment" class="btn btn-primary">Submit Comment</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Bug History -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Bug History</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if (empty($history)): ?>
                                        <li class="list-group-item px-0">No history available.</li>
                                    <?php else: ?>
                                        <?php foreach ($history as $entry): ?>
                                            <li class="list-group-item px-0">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars(safe_get($entry, 'action', '')); ?></strong>
                                                        <div class="text-muted small">
                                                            by <?php echo htmlspecialchars(safe_get($entry, 'fullname', '')); ?> (<?php echo ucfirst(safe_get($entry, 'role', '')); ?>)
                                                        </div>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <?php echo date('M d, Y H:i', strtotime(safe_get($entry, 'created_at', 'now'))); ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="view_bug.php?id=<?php echo $bug_id; ?>&action=edit" class="btn btn-warning">
                                        <i class="fas fa-edit me-1"></i> Edit Bug
                                    </a>
                                    
                                    <?php if ($status === 'new' || $status === 'assigned'): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="status" value="in_progress">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-play me-1"></i> Start Working
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($status === 'in_progress'): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="status" value="resolved">
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fas fa-check me-1"></i> Mark as Resolved
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($status === 'resolved'): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="status" value="closed">
                                            <button type="submit" class="btn btn-secondary w-100">
                                                <i class="fas fa-lock me-1"></i> Close Bug
                                            </button>
                                        </form>
                                        
                                        <form method="POST" action="">
                                            <input type="hidden" name="status" value="reopened">
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fas fa-redo me-1"></i> Reopen Bug
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($status === 'closed'): ?>
                                        <form method="POST" action="">
                                            <input type="hidden" name="status" value="reopened">
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fas fa-redo me-1"></i> Reopen Bug
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php include '../includes/footer.php'; ?>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/js/main.js"></script>
    </body>
    </html>