<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'BugTracker');
define('SITE_URL', 'http://localhost/bug_tracker');

// File upload configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_DIR', 'uploads/');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_STAFF', 'staff');
define('ROLE_CUSTOMER', 'customer');

// Bug status
define('STATUS_NEW', 'new');
define('STATUS_ASSIGNED', 'assigned');
define('STATUS_IN_PROGRESS', 'in_progress');
define('STATUS_RESOLVED', 'resolved');
define('STATUS_CLOSED', 'closed');
define('STATUS_REOPENED', 'reopened');

// Bug priority
define('PRIORITY_LOW', 'low');
define('PRIORITY_MEDIUM', 'medium');
define('PRIORITY_HIGH', 'high');
define('PRIORITY_CRITICAL', 'critical');

// Time zone
date_default_timezone_set('UTC');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'database.php';

// Include helper functions
// after
require_once __DIR__ . '/../includes/functions.php';
?>