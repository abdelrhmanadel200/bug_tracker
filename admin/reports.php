<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

// Get report type
$report_type = isset($_GET['type']) ? $_GET['type'] : 'bugs';

// Date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Additional filters
$project_filter = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Get all projects for filter dropdown
$projects_query = "SELECT id, name FROM projects ORDER BY name";
$projects_result = $conn->query($projects_query);
$projects = $projects_result->fetch_all(MYSQLI_ASSOC);

// Get all users for filter dropdown
$users_query = "SELECT id, fullname, role FROM users ORDER BY fullname";
$users_result = $conn->query($users_query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Generate report based on type
switch ($report_type) {
    case 'bugs':
        // Bug status distribution
        $bug_status_query = "SELECT status, COUNT(*) as count FROM bugs 
                            WHERE created_at BETWEEN ? AND ? ";
        $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        if ($project_filter > 0) {
            $bug_status_query .= " AND project_id = ? ";
            $params[] = $project_filter;
        }
        
        if ($user_filter > 0) {
            $bug_status_query .= " AND (reported_by = ? OR assigned_to = ?) ";
            $params[] = $user_filter;
            $params[] = $user_filter;
        }
        
        $bug_status_query .= " GROUP BY status";
        
        $bug_status_stmt = $conn->prepare($bug_status_query);
        $bug_status_stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $bug_status_stmt->execute();
        $bug_status_result = $bug_status_stmt->get_result();
        $bug_status_data = $bug_status_result->fetch_all(MYSQLI_ASSOC);
        
        // Bug priority distribution
        $bug_priority_query = "SELECT priority, COUNT(*) as count FROM bugs 
                              WHERE created_at BETWEEN ? AND ? ";
        
        if ($project_filter > 0) {
            $bug_priority_query .= " AND project_id = ? ";
        }
        
        if ($user_filter > 0) {
            $bug_priority_query .= " AND (reported_by = ? OR assigned_to = ?) ";
        }
        
        $bug_priority_query .= " GROUP BY priority";
        
        $bug_priority_stmt = $conn->prepare($bug_priority_query);
        $bug_priority_stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $bug_priority_stmt->execute();
        $bug_priority_result = $bug_priority_stmt->get_result();
        $bug_priority_data = $bug_priority_result->fetch_all(MYSQLI_ASSOC);
        
        // Bugs by project
        $bugs_by_project_query = "SELECT p.name, COUNT(b.id) as count FROM bugs b 
                                 JOIN projects p ON b.project_id = p.id 
                                 WHERE b.created_at BETWEEN ? AND ? ";
        
        $project_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        if ($project_filter > 0) {
            $bugs_by_project_query .= " AND b.project_id = ? ";
            $project_params[] = $project_filter;
        }
        
        if ($user_filter > 0) {
            $bugs_by_project_query .= " AND (b.reported_by = ? OR b.assigned_to = ?) ";
            $project_params[] = $user_filter;
            $project_params[] = $user_filter;
        }
        
        $bugs_by_project_query .= " GROUP BY p.name ORDER BY count DESC";
        
        $bugs_by_project_stmt = $conn->prepare($bugs_by_project_query);
        $bugs_by_project_stmt->bind_param(str_repeat('s', count($project_params)), ...$project_params);
        $bugs_by_project_stmt->execute();
        $bugs_by_project_result = $bugs_by_project_stmt->get_result();
        $bugs_by_project_data = $bugs_by_project_result->fetch_all(MYSQLI_ASSOC);
        
        // Resolution time statistics
        $resolution_time_query = "SELECT 
                                 AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution_time,
                                 MIN(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as min_resolution_time,
                                 MAX(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as max_resolution_time
                                 FROM bugs 
                                 WHERE resolved_at IS NOT NULL 
                                 AND created_at BETWEEN ? AND ? ";
        
        $resolution_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        if ($project_filter > 0) {
            $resolution_time_query .= " AND project_id = ? ";
            $resolution_params[] = $project_filter;
        }
        
        if ($user_filter > 0) {
            $resolution_time_query .= " AND (reported_by = ? OR assigned_to = ?) ";
            $resolution_params[] = $user_filter;
            $resolution_params[] = $user_filter;
        }
        
        $resolution_time_stmt = $conn->prepare($resolution_time_query);
        $resolution_time_stmt->bind_param(str_repeat('s', count($resolution_params)), ...$resolution_params);
        $resolution_time_stmt->execute();
        $resolution_time_result = $resolution_time_stmt->get_result();
        $resolution_time_data = $resolution_time_result->fetch_assoc();
        
        break;
        
    case 'users':
        // User registration over time
        $user_reg_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                          FROM users 
                          WHERE created_at BETWEEN ? AND ? 
                          GROUP BY DATE(created_at) 
                          ORDER BY date";
        
        $user_reg_stmt = $conn->prepare($user_reg_query);
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        $user_reg_stmt->bind_param("ss", $start_datetime, $end_datetime);
        $user_reg_stmt->execute();
        $user_reg_result = $user_reg_stmt->get_result();
        $user_reg_data = $user_reg_result->fetch_all(MYSQLI_ASSOC);
        
        // User role distribution
        $user_role_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
        $user_role_result = $conn->query($user_role_query);
        $user_role_data = $user_role_result->fetch_all(MYSQLI_ASSOC);
        
        // User status distribution
        $user_status_query = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
        $user_status_result = $conn->query($user_status_query);
        $user_status_data = $user_status_result->fetch_all(MYSQLI_ASSOC);
        
        // Most active users (by bug reports or comments)
        $active_users_query = "SELECT u.id, u.fullname, u.role,
                              (SELECT COUNT(*) FROM bugs WHERE reported_by = u.id) as reported_bugs,
                              (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comments,
                              ((SELECT COUNT(*) FROM bugs WHERE reported_by = u.id) + 
                               (SELECT COUNT(*) FROM comments WHERE user_id = u.id)) as total_activity
                              FROM users u
                              ORDER BY total_activity DESC
                              LIMIT 10";
        
        $active_users_result = $conn->query($active_users_query);
        $active_users_data = $active_users_result->fetch_all(MYSQLI_ASSOC);
        
        break;
        
    case 'projects':
        // Project bug distribution
        $project_bugs_query = "SELECT p.name, 
                              COUNT(b.id) as total_bugs,
                              SUM(CASE WHEN b.status = 'resolved' OR b.status = 'closed' THEN 1 ELSE 0 END) as resolved_bugs,
                              SUM(CASE WHEN b.status = 'new' OR b.status = 'assigned' OR b.status = 'in_progress' OR b.status = 'reopened' THEN 1 ELSE 0 END) as open_bugs
                              FROM projects p
                              LEFT JOIN bugs b ON p.id = b.project_id
                              WHERE p.created_at BETWEEN ? AND ? ";
        
        $project_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        if ($project_filter > 0) {
            $project_bugs_query .= " AND p.id = ? ";
            $project_params[] = $project_filter;
        }
        
        $project_bugs_query .= " GROUP BY p.name ORDER BY total_bugs DESC";
        
        $project_bugs_stmt = $conn->prepare($project_bugs_query);
        $project_bugs_stmt->bind_param(str_repeat('s', count($project_params)), ...$project_params);
        $project_bugs_stmt->execute();
        $project_bugs_result = $project_bugs_stmt->get_result();
        $project_bugs_data = $project_bugs_result->fetch_all(MYSQLI_ASSOC);
        
        // Project status distribution
        $project_status_query = "SELECT status, COUNT(*) as count FROM projects GROUP BY status";
        $project_status_result = $conn->query($project_status_query);
        $project_status_data = $project_status_result->fetch_all(MYSQLI_ASSOC);
        
        // Average resolution time by project
        $avg_resolution_query = "SELECT p.name, 
                                AVG(TIMESTAMPDIFF(HOUR, b.created_at, b.resolved_at)) as avg_resolution_time
                                FROM projects p
                                JOIN bugs b ON p.id = b.project_id
                                WHERE b.resolved_at IS NOT NULL
                                AND b.created_at BETWEEN ? AND ? ";
        
        $resolution_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        if ($project_filter > 0) {
            $avg_resolution_query .= " AND p.id = ? ";
            $resolution_params[] = $project_filter;
        }
        
        $avg_resolution_query .= " GROUP BY p.name ORDER BY avg_resolution_time";
        
        $avg_resolution_stmt = $conn->prepare($avg_resolution_query);
        $avg_resolution_stmt->bind_param(str_repeat('s', count($resolution_params)), ...$resolution_params);
        $avg_resolution_stmt->execute();
        $avg_resolution_result = $avg_resolution_stmt->get_result();
        $avg_resolution_data = $avg_resolution_result->fetch_all(MYSQLI_ASSOC);
        
        break;
        
    default:
        // Default to bugs report
        header('Location: reports.php?type=bugs');
        exit();
}

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
        <h1 class="h3">Reports & Analytics</h1>
        <div class="btn-group">
            <a href="reports.php?type=bugs&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&project_id=<?php echo $project_filter; ?>&user_id=<?php echo $user_filter; ?>" class="btn btn-outline-primary <?php echo $report_type === 'bugs' ? 'active' : ''; ?>">Bug Reports</a>
            <a href="reports.php?type=users&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-outline-primary <?php echo $report_type === 'users' ? 'active' : ''; ?>">User Reports</a>
            <a href="reports.php?type=projects&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&project_id=<?php echo $project_filter; ?>" class="btn btn-outline-primary <?php echo $report_type === 'projects' ? 'active' : ''; ?>">Project Reports</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <?php if ($report_type === 'bugs' || $report_type === 'projects'): ?>
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
                <?php endif; ?>
                
                <?php if ($report_type === 'bugs'): ?>
                    <div class="col-md-3">
                        <label for="user_id" class="form-label">User</label>
                        <select name="user_id" id="user_id" class="form-select">
                            <option value="0">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $user_filter === $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['fullname']); ?> (<?php echo ucfirst($user['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <a href="reports.php?type=<?php echo $report_type; ?>" class="btn btn-secondary">Reset Filters</a>
                    <button type="button" class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Content -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <?php 
                switch ($report_type) {
                    case 'bugs':
                        echo 'Bug Report';
                        break;
                    case 'users':
                        echo 'User Report';
                        break;
                    case 'projects':
                        echo 'Project Report';
                        break;
                }
                ?>
                (<?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>)
            </h5>
        </div>
        <div class="card-body">
            <?php if ($report_type === 'bugs'): ?>
                <!-- Bug Reports -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Bug Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="bugStatusChart"></canvas>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_bugs = 0;
                                            foreach ($bug_status_data as $status) {
                                                $total_bugs += $status['count'];
                                            }
                                            
                                            foreach ($bug_status_data as $status): 
                                                $percentage = $total_bugs > 0 ? round(($status['count'] / $total_bugs) * 100, 1) : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo ucfirst(str_replace('_', ' ', $status['status'])); ?></td>
                                                    <td><?php echo $status['count']; ?></td>
                                                    <td><?php echo $percentage; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Bug Priority Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="bugPriorityChart"></canvas>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Priority</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_priority = 0;
                                            foreach ($bug_priority_data as $priority) {
                                                $total_priority += $priority['count'];
                                            }
                                            
                                            foreach ($bug_priority_data as $priority): 
                                                $percentage = $total_priority > 0 ? round(($priority['count'] / $total_priority) * 100, 1) : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo ucfirst($priority['priority']); ?></td>
                                                    <td><?php echo $priority['count']; ?></td>
                                                    <td><?php echo $percentage; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Bugs by Project</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="bugsByProjectChart"></canvas>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Project</th>
                                                <th>Bug Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bugs_by_project_data as $project): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($project['name']); ?></td>
                                                    <td><?php echo $project['count']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Resolution Time Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-2 text-muted">Average Resolution Time</h6>
                                                <h3 class="card-title">
                                                    <?php 
                                                    if ($resolution_time_data['avg_resolution_time']) {
                                                        $avg_hours = round($resolution_time_data['avg_resolution_time'], 1);
                                                        if ($avg_hours >= 24) {
                                                            echo round($avg_hours / 24, 1) . ' days';
                                                        } else {
                                                            echo $avg_hours . ' hours';
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-2 text-muted">Minimum Resolution Time</h6>
                                                <h3 class="card-title">
                                                    <?php 
                                                    if ($resolution_time_data['min_resolution_time']) {
                                                        $min_hours = round($resolution_time_data['min_resolution_time'], 1);
                                                        if ($min_hours >= 24) {
                                                            echo round($min_hours / 24, 1) . ' days';
                                                        } else {
                                                            echo $min_hours . ' hours';
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-2 text-muted">Maximum Resolution Time</h6>
                                                <h3 class="card-title">
                                                    <?php 
                                                    if ($resolution_time_data['max_resolution_time']) {
                                                        $max_hours = round($resolution_time_data['max_resolution_time'], 1);
                                                        if ($max_hours >= 24) {
                                                            echo round($max_hours / 24, 1) . ' days';
                                                        } else {
                                                            echo $max_hours . ' hours';
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> Resolution time is calculated from bug creation to resolution for bugs that have been resolved.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($report_type === 'users'): ?>
                <!-- User Reports -->
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">User Registration Over Time</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="userRegistrationChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">User Role Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="userRoleChart"></canvas>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Role</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_users = 0;
                                            foreach ($user_role_data as $role) {
                                                $total_users += $role['count'];
                                            }
                                            
                                            foreach ($user_role_data as $role): 
                                                $percentage = $total_users > 0 ? round(($role['count'] / $total_users) * 100, 1) : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo ucfirst($role['role']); ?></td>
                                                    <td><?php echo $role['count']; ?></td>
                                                    <td><?php echo $percentage; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">User Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="userStatusChart"></canvas>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_status = 0;
                                            foreach ($user_status_data as $status) {
                                                $total_status += $status['count'];
                                            }
                                            
                                            foreach ($user_status_data as $status): 
                                                $percentage = $total_status > 0 ? round(($status['count'] / $total_status) * 100, 1) : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo ucfirst($status['status']); ?></td>
                                                    <td><?php echo $status['count']; ?></td>
                                                    <td><?php echo $percentage; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Most Active Users</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Reported Bugs</th>
                                                <th>Comments</th>
                                                <th>Total Activity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($active_users_data as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['fullname']); ?></td>
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
                                                    <td><?php echo $user['reported_bugs']; ?></td>
                                                    <td><?php echo $user['comments']; ?></td>
                                                    <td><?php echo $user['total_activity']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($report_type === 'projects'): ?>
                <!-- Project Reports -->
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Project Bug Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="projectBugsChart" height="100"></canvas>
                                <div class="table-responsive mt-3">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Project</th>
                                                <th>Total Bugs</th>
                                                <th>Open Bugs</th>
                                                <th>Resolved Bugs</th>
                                                <th>Resolution Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($project_bugs_data as $project): 
                                                $resolution_rate = $project['total_bugs'] > 0 ? round(($project['resolved_bugs'] / $project['total_bugs']) * 100, 1) : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($project['name']); ?></td>
                                                    <td><?php echo $project['total_bugs']; ?></td>
                                                    <td><?php echo $project['open_bugs']; ?></td>
                                                    <td><?php echo $project['resolved_bugs']; ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $resolution_rate; ?>%;" aria-valuenow="<?php echo $resolution_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                                                                <?php echo $resolution_rate; ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Project Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="projectStatusChart"></canvas>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_projects = 0;
                                            foreach ($project_status_data as $status) {
                                                $total_projects += $status['count'];
                                            }
                                            
                                            foreach ($project_status_data as $status): 
                                                $percentage = $total_projects > 0 ? round(($status['count'] / $total_projects) * 100, 1) : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo ucfirst($status['status']); ?></td>
                                                    <td><?php echo $status['count']; ?></td>
                                                    <td><?php echo $percentage; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Average Resolution Time by Project</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="resolutionTimeChart"></canvas>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Project</th>
                                                <th>Average Resolution Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($avg_resolution_data as $project): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($project['name']); ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($project['avg_resolution_time']) {
                                                            $avg_hours = round($project['avg_resolution_time'], 1);
                                                            if ($avg_hours >= 24) {
                                                                echo round($avg_hours / 24, 1) . ' days';
                                                            } else {
                                                                echo $avg_hours . ' hours';
                                                            }
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Initialize charts based on report type
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($report_type === 'bugs'): ?>
            // Bug Status Chart
            const statussCtx = document.getElementById('bugStatusChart').getContext('2d');
            new Chart(statussCtx, {
                type: 'pie',
                data: {
                    labels: [
                        <?php 
                        foreach ($bug_status_data as $status) {
                            echo "'" . ucfirst(str_replace('_', ' ', $status['status'])) . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php 
                            foreach ($bug_status_data as $status) {
                                echo $status['count'] . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: [
                            '#36a2eb', '#ff6384', '#ffcd56', '#4bc0c0', '#9966ff', '#ff9f40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Bug Priority Chart
            const priorityCtx = document.getElementById('bugPriorityChart').getContext('2d');
            new Chart(priorityCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php 
                        foreach ($bug_priority_data as $priority) {
                            echo "'" . ucfirst($priority['priority']) . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php 
                            foreach ($bug_priority_data as $priority) {
                                echo $priority['count'] . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: [
                            '#4bc0c0', '#ffcd56', '#ff9f40', '#ff6384'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Bugs by Project Chart
            const projectCtx = document.getElementById('bugsByProjectChart').getContext('2d');
            new Chart(projectCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php 
                        foreach ($bugs_by_project_data as $project) {
                            echo "'" . htmlspecialchars($project['name']) . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Number of Bugs',
                        data: [
                            <?php 
                            foreach ($bugs_by_project_data as $project) {
                                echo $project['count'] . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: '#36a2eb'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
        <?php elseif ($report_type === 'users'): ?>
            // User Registration Chart
            const regCtx = document.getElementById('userRegistrationChart').getContext('2d');
            new Chart(regCtx, {
                type: 'line',
                data: {
                    labels: [
                        <?php 
                        foreach ($user_reg_data as $reg) {
                            echo "'" . date('M d', strtotime($reg['date'])) . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'New Users',
                        data: [
                            <?php 
                            foreach ($user_reg_data as $reg) {
                                echo $reg['count'] . ", ";
                            }
                            ?>
                        ],
                        borderColor: '#36a2eb',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // User Role Chart
            const roleCtx = document.getElementById('userRoleChart').getContext('2d');
            new Chart(roleCtx, {
                type: 'pie',
                data: {
                    labels: [
                        <?php 
                        foreach ($user_role_data as $role) {
                            echo "'" . ucfirst($role['role']) . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php 
                            foreach ($user_role_data as $role) {
                                echo $role['count'] . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: [
                            '#ff6384', '#36a2eb', '#ffcd56'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // User Status Chart
            const statulsCtx = document.getElementById('userStatusChart').getContext('2d');
            new Chart(statulsCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php 
                        foreach ($user_status_data as $status) {
                            echo "'" . ucfirst($status['status']) . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php 
                            foreach ($user_status_data as $status) {
                                echo $status['count'] . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: [
                            '#4bc0c0', '#ffcd56', '#ff6384'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
        <?php elseif ($report_type === 'projects'): ?>
            // Project Bugs Chart
            const bugsCtx = document.getElementById('projectBugsChart').getContext('2d');
            new Chart(bugsCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php 
                        foreach ($project_bugs_data as $project) {
                            echo "'" . htmlspecialchars($project['name']) . "', ";
                        }
                        ?>
                    ],
                    datasets: [
                        {
                            label: 'Open Bugs',
                            data: [
                                <?php 
                                foreach ($project_bugs_data as $project) {
                                    echo $project['open_bugs'] . ", ";
                                }
                                ?>
                            ],
                            backgroundColor: '#ff9f40'
                        },
                        {
                            label: 'Resolved Bugs',
                            data: [
                                <?php 
                                foreach ($project_bugs_data as $project) {
                                    echo $project['resolved_bugs'] . ", ";
                                }
                                ?>
                            ],
                            backgroundColor: '#4bc0c0'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Project Status Chart
            const statusCtx = document.getElementById('projectStatusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: [
                        <?php 
                        foreach ($project_status_data as $status) {
                            echo "'" . ucfirst($status['status']) . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php 
                            foreach ($project_status_data as $status) {
                                echo $status['count'] . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: [
                            '#4bc0c0', '#ffcd56', '#9966ff'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Resolution Time Chart
            const timeCtx = document.getElementById('resolutionTimeChart').getContext('2d');
            new Chart(timeCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php 
                        foreach ($avg_resolution_data as $project) {
                            echo "'" . htmlspecialchars($project['name']) . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Average Resolution Time (hours)',
                        data: [
                            <?php 
                            foreach ($avg_resolution_data as $project) {
                                echo $project['avg_resolution_time'] ? round($project['avg_resolution_time'], 1) : 0 . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: '#36a2eb'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>