<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
session_start();
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
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
    $title = $bug['title'];
    $description = $bug['description'];
    $project_id = $bug['project_id'];
    $reported_by = $bug['reported_by'];
    $assigned_to = $bug['assigned_to'];
    $priority = $bug['priority'];
    $status = $bug['status'];
    $environment = $bug['environment'];
    $screenshot = $bug['screenshot'];
    $ticket_number = $bug['ticket_number'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $project_id = (int)$_POST['project_id'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $environment = trim($_POST['environment']);
    
    if ($new_mode || $edit_mode) {
        $reported_by = (int)$_POST['reported_by'];
        $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
    }
    
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
            if ($status === 'resolved' && $bug['status'] !== 'resolved') {
                $update_query .= ", resolved_at = NOW()";
            } elseif ($status !== 'resolved' && $bug['status'] === 'resolved') {
                $update_query .= ", resolved_at = NULL";
            }
            
            // Add closed_at timestamp if status changed to closed
            if ($status === 'closed' && $bug['status'] !== 'closed') {
                $update_query .= ", closed_at = NOW()";
            } elseif ($status !== 'closed' && $bug['status'] === 'closed') {
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
                log_activity($conn, $user_id, $bug_id, $action_text);
                
                // Add to bug history
                $history_action = "Updated bug details";
                add_bug_history($conn, $bug_id, $user_id, $history_action);
                
                // If status changed, add to bug history
                if ($status !== $bug['status']) {
                    $history_action = "Changed status from " . ucfirst(str_replace('_', ' ', $bug['status'])) . " to " . ucfirst(str_replace('_', ' ', $status));
                    add_bug_history($conn, $bug_id, $user_id, $history_action);
                }
                
                // If assigned_to changed, add to bug history
                if ($assigned_to !== $bug['assigned_to']) {
                    if (empty($bug['assigned_to'])) {
                        $staff_name_query = "SELECT fullname FROM users WHERE id = ?";
                        $staff_name_stmt = $conn->prepare($staff_name_query);
                        $staff_name_stmt->bind_param("i", $assigned_to);
                        $staff_name_stmt->execute();
                        $staff_name_result = $staff_name_stmt->get_result();
                        $staff_name = $staff_name_result->fetch_assoc()['fullname'];
                        
                        $history_action = "Assigned bug to $staff_name";
                    } elseif (empty($assigned_to)) {
                        $history_action = "Unassigned bug";
                    } else {
                        $staff_name_query = "SELECT fullname FROM users WHERE id = ?";
                        $staff_name_stmt = $conn->prepare($staff_name_query);
                        $staff_name_stmt->bind_param("i", $assigned_to);
                        $staff_name_stmt->execute();
                        $staff_name_result = $staff_name_stmt->get_result();
                        $staff_name = $staff_name_result->fetch_assoc()['fullname'];
                        
                        $history_action = "Reassigned bug to $staff_name";
                    }
                    add_bug_history($conn, $bug_id, $user_id, $history_action);
                }
                
                $success_message = "Bug updated successfully.";
                
                // Refresh bug data
                $bug_stmt->execute();
                $bug_result = $bug_stmt->get_result();
                $bug = $bug_result->fetch_assoc();
                $title = $bug['title'];
                $description = $bug['description'];
                $project_id = $bug['project_id'];
                $reported_by = $bug['reported_by'];
                $assigned_to = $bug['assigned_to'];
                $priority = $bug['priority'];
                $status = $bug['status'];
                $environment = $bug['environment'];
                $screenshot = $bug['screenshot'];
                $ticket_number = $bug['ticket_number'];
            } else {
                $errors[] = "Error updating bug: " . $conn->error;
            }
        } elseif ($new_mode) {
            // Generate ticket number
            $ticket_number = generate_ticket_number($conn);
            
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
            $insert_stmt->bind_param("iiissssss", 
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
                log_activity($conn, $user_id, $new_bug_id, $action_text);
                
                // Add to bug history
                $history_action = "Created bug";
                add_bug_history($conn, $new_bug_id, $user_id, $history_action);
                
                // If assigned to someone, add to bug history
                if (!empty($assigned_to)) {
                    $staff_name_query = "SELECT fullname FROM users WHERE id = ?";
                    $staff_name_stmt = $conn->prepare($staff_name_query);
                    $staff_name_stmt->bind_param("i", $assigned_to);
                    $staff_name_stmt->execute();
                    $staff_name_result = $staff_name_stmt->get_result();
                    $staff_name = $staff_name_result->fetch_assoc()['fullname'];
                    
                    $history_action = "Assigned bug to $staff_name";
                    add_bug_history($conn, $new_bug_id, $user_id, $history_action);
                }
                
                header('Location: view_bug.php?id=' . $new_bug_id . '&success=created');
                exit();
            } else {
                $errors[] = "Error creating bug: " . $conn->error;
            }
        }
    }
}

// Get bug comments if viewing an existing bug
$comments = [];
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
    $comment_text = trim($_POST['comment']);
    
    if (empty($comment_text)) {
        $comment_error = "Comment cannot be empty.";
    } else {
        $comment_query = "INSERT INTO comments (bug_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
        $comment_stmt = $conn->prepare($comment_query);
        $comment_stmt->bind_param("iis", $bug_id, $user_id, $comment_text);
        
        if ($comment_stmt->execute()) {
            // Log the activity
            $action_text = "Added comment to bug #$ticket_number";
            log_activity($conn, $user_id, $bug_id, $action_text);
            
            // Add to bug history
            $history_action = "Added a comment";
            add_bug_history($conn, $bug_id, $user_id, $history_action);
            
            // Refresh the page to show the new comment
            header('Location: view_bug.php?id=' . $bug_id . '#comments');
            exit();
        } else {
            $comment_error = "Error adding comment: " . $conn->error;
        }
    }
}

include '../includes/admin_header.php';
?>

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
                        <li><?php echo $error; ?></li>
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
                                        <?php echo htmlspecialchars($project['name']); ?>
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
                                        <?php echo htmlspecialchars($customer['fullname']); ?> (Customer)
                                    </option>
                                <?php endforeach; ?>
                                <?php foreach ($staff as $staff_member): ?>
                                    <option value="<?php echo $staff_member['id']; ?>" <?php echo $reported_by == $staff_member['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($staff_member['fullname']); ?> (Staff)
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
                                        <?php echo htmlspecialchars($staff_member['fullname']); ?>
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
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
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
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reported_by_display" class="form-label">Reported By</label>
                            <input type="text" class="form-control" id="reported_by_display" value="<?php echo htmlspecialchars($bug['reported_by_name']); ?>" readonly>
                            <input type="hidden" name="reported_by" value="<?php echo $reported_by; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($staff as $staff_member): ?>
                                    <option value="<?php echo $staff_member['id']; ?>" <?php echo $assigned_to == $staff_member['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($staff_member['fullname']); ?>
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
                                <a href="../uploads/screenshots/<?php echo $screenshot; ?>" target="_blank" class="d-inline-block">
                                    <img src="../uploads/screenshots/<?php echo $screenshot; ?>" alt="Bug Screenshot" class="img-thumbnail" style="max-height: 150px;">
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
                                <p><strong>Project:</strong> <?php echo htmlspecialchars($bug['project_name']); ?></p>
                                <p><strong>Reported By:</strong> <?php echo htmlspecialchars($bug['reported_by_name']); ?></p>
                                <p><strong>Assigned To:</strong> <?php echo $bug['assigned_to_name'] ? htmlspecialchars($bug['assigned_to_name']) : '<span class="text-muted">Unassigned</span>'; ?></p>
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
                                <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($bug['created_at'])); ?></p>
                                <?php if ($bug['resolved_at']): ?>
    <p><strong>Resolved:</strong>
       <?php echo date('M d, Y H:i', strtotime($bug['resolved_at'])); ?>
    </p>
<?php endif; ?>
<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all bugs assigned to the admin with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter options
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$project_filter = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Build query based on filters
$query = "SELECT b.*, p.name as project_name, 
          u1.fullname as reported_by_name
          FROM bugs b 
          LEFT JOIN projects p ON b.project_id = p.id 
          LEFT JOIN users u1 ON b.reported_by = u1.id 
          WHERE b.assigned_to = ?";

$params = [$user_id];

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

$query .= " ORDER BY 
          CASE 
              WHEN b.status = 'new' THEN 1
              WHEN b.status = 'reopened' THEN 2
              WHEN b.status = 'assigned' THEN 3
              WHEN b.status = 'in_progress' THEN 4
              WHEN b.status = 'resolved' THEN 5
              WHEN b.status = 'closed' THEN 6
              ELSE 7
          END,
          CASE 
              WHEN b.priority = 'critical' THEN 1
              WHEN b.priority = 'high' THEN 2
              WHEN b.priority = 'medium' THEN 3
              WHEN b.priority = 'low' THEN 4
              ELSE 5
          END,
          b.created_at DESC
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($query);

// Bind parameters dynamically
$types = 'i' . str_repeat('s', count($params) - 3) . 'ii'; // integer (user_id) + string types + 2 integers for limit and offset
$stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();
$bugs = $result->fetch_all(MYSQLI_ASSOC);

// Get total bugs count for pagination
$count_query = "SELECT COUNT(*) as total FROM bugs WHERE assigned_to = ?";
$count_params = [$user_id];

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
$count_types = 'i' . str_repeat('s', count($count_params) - 1); // integer (user_id) + string types
$count_stmt->bind_param($count_types, ...$count_params);

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_bugs = $count_row['total'];
$total_pages = ceil($total_bugs / $limit);

// Get all projects for filter dropdown
$projects_query = "SELECT id, name FROM projects ORDER BY name";
$projects_result = $conn->query($projects_query);
$projects = $projects_result->fetch_all(MYSQLI_ASSOC);

// Get bug statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_bugs,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_bugs,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_bugs,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_bugs,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_bugs,
                SUM(CASE WHEN status = 'reopened' THEN 1 ELSE 0 END) as reopened_bugs,
                SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical_bugs,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_bugs
                FROM bugs
                WHERE assigned_to = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">My Assigned Bugs</h1>
        <a href="bugs.php" class="btn btn-primary">
            <i class="fas fa-list"></i> All Bugs
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Assigned</h6>
                            <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                        </div>
                        <i class="fas fa-bug fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Pending Action</h6>
                            <h2 class="mb-0"><?php echo $stats['new_bugs'] + $stats['assigned_bugs'] + $stats['reopened_bugs']; ?></h2>
                        </div>
                        <i class="fas fa-exclamation-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">In Progress</h6>
                            <h2 class="mb-0"><?php echo $stats['in_progress_bugs']; ?></h2>
                        </div>
                        <i class="fas fa-spinner fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Critical/High</h6>
                            <h2 class="mb-0"><?php echo $stats['critical_bugs'] + $stats['high_bugs']; ?></h2>
                        </div>
                        <i class="fas fa-fire fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
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
                    <a href="my_bugs.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bugs Table -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">My Assigned Bugs (<?php echo $total_bugs; ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($bugs)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> You don't have any bugs assigned to you that match the current filters.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Title</th>
                                <th>Project</th>
                                <th>Reported By</th>
                                <th>Status</th>
                                <th>Priority</th>
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
                                    <td><?php echo htmlspecialchars($bug['reported_by_name']); ?></td>
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
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>

<?php include '../includes/footer.php'; ?>
