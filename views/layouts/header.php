<?php
// Main layout header
require_once __DIR__ . '/../../includes/config.php';

// Set default page if not provided (supports subfolders like ga_staff/*)
if (!isset($currentPage)) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    $scriptName = str_replace('\\', '/', $scriptName);
    $parts = explode('/', trim($scriptName, '/'));
    // __DIR__ = <root>/views/layouts; project root is 2 levels up
    $projectDir = basename(dirname(__DIR__, 2));
    if (!empty($parts) && $parts[0] === $projectDir) {
        array_shift($parts);
    }
    // If the web entrypoints are served from /public/*, strip it so allowlists match
    if (!empty($parts) && $parts[0] === 'public') {
        array_shift($parts);
    }
    $currentPage = implode('/', $parts);
    if ($currentPage === '') {
        $currentPage = basename($_SERVER['PHP_SELF']);
    }
}

// Check authentication for protected pages
$publicPages = ['login.php', 'index.php'];
if (!in_array($currentPage, $publicPages) && !isAuthenticated()) {
    header('Location: ' . app_url('login.php'));
    exit();
}

// If on login page and already logged in, redirect to dashboard
if ($currentPage === 'login.php' && isAuthenticated()) {
    $u = getUser();
    header('Location: ' . role_landing_page($u['role'] ?? null));
    exit();
}

$user = getUser();

// For this phase: GA Manager account should have only the required module pages
if ($user && ($user['role'] ?? '') === 'ga_manager') {
    $gaManagerAllowed = [
        'dashboard.php',
        'notifications.php',
        'ga-manager-approval.php',
        'reports.php',
        'users.php',
        'statistics.php',
        'profile.php',
        'logout.php',
    ];

    if (!in_array($currentPage, $publicPages, true) && !in_array($currentPage, $gaManagerAllowed, true)) {
        http_response_code(404);
        die('Page not available.');
    }
}

// GA Staff account should have only the required module pages
if ($user && ($user['role'] ?? '') === 'ga_staff') {
    $gaStaffAllowed = [
        'dashboard.php',
        'notifications.php',
        'ga-staff-review.php',
        'reports.php',
        'returned-reports.php',
        'ga_staff/user_management.php',
        'ga_staff/statistics.php',
        'profile.php',
        'logout.php',
    ];

    if (!in_array($currentPage, $publicPages, true) && !in_array($currentPage, $gaStaffAllowed, true)) {
        http_response_code(404);
        die('Page not available.');
    }
}

// Security account should have only the required module pages
if ($user && ($user['role'] ?? '') === 'security') {
    $securityAllowed = [
        'security-dashboard.php',
        'notifications.php',
        'submit-report.php',
        'final-checking.php',
        'security-reports.php',
        'security-statistics.php',
        'profile.php',
        'logout.php',
    ];

    if (!in_array($currentPage, $publicPages, true) && !in_array($currentPage, $securityAllowed, true)) {
        http_response_code(404);
        die('Page not available.');
    }
}

// Department account should have only the required module pages
if ($user && ($user['role'] ?? '') === 'department') {
    $departmentAllowed = [
        'department-dashboard.php',
        'notifications.php',
        'assigned-reports.php',
        'department-history.php',
        'department-statistics.php',
        'profile.php',
        'logout.php',
    ];

    if (!in_array($currentPage, $publicPages, true) && !in_array($currentPage, $departmentAllowed, true)) {
        http_response_code(404);
        die('Page not available.');
    }
}

// Enforce required role if page defines it
if (isset($requiredRole)) {
    $role = (string) ($user['role'] ?? '');
    $allowed = is_array($requiredRole) ? $requiredRole : [$requiredRole];
    if ($role === '' || !in_array($role, $allowed, true)) {
        http_response_code(403);
        die('Access denied.');
    }
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>" />
    <title><?php echo htmlspecialchars($pageTitle ?? 'Nidec Security'); ?></title>
        <script>
            // Expose PHP-resolved base URL to vanilla JS (fixes /public subfolder deployments)
            window.APP_BASE_URL = <?php echo json_encode(APP_BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
            // Use live DB-backed API for modals/notifications instead of mock Data.
            window.NIDEC_SERVER_MODE = true;
            // Apply persisted sidebar state before first paint to avoid expanded->collapsed flicker.
            (function () {
                try {
                    var isDesktop = window.matchMedia('(min-width: 768px)').matches;
                    var collapsed = localStorage.getItem('nidec_sidebar_collapsed') === '1';
                    if (isDesktop && collapsed) {
                        document.documentElement.classList.add('sidebar-collapsed');
                    }
                } catch (e) {
                    // ignore
                }
            })();
        </script>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars(
        app_url('assets/images/security_icon.png'),
    ); ?>?v=1">

        <!-- Google Sans (Google Fonts CDN) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap 5 (CDN) -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons (CDN) -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/js/bootstrap.bundle.min.js" defer></script>

        <!-- Keep existing project CSS (loads AFTER Bootstrap to preserve current look) -->
        <link rel="stylesheet" href="<?php echo htmlspecialchars(
            app_url('assets/css/style.css'),
        ); ?>?v=<?php echo time(); ?>" />

        <!-- Apply Google Sans globally -->
        <style>
            :root {
                --bs-font-sans-serif: "Google Sans", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            }
            body {
                font-family: var(--bs-font-sans-serif);
            }
        </style>
</head>
<body class="bg-background text-foreground">
<script>
    (function () {
        try {
            if (document.documentElement.classList.contains('sidebar-collapsed')) {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        } catch (e) {
            // ignore
        }
    })();
</script>
