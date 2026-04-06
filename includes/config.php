<?php

// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    // 1. Tell the browser the cookie is valid for the whole site
    ini_set('session.cookie_path', '/');

    // 2. Set the custom storage path (you already have this)
    $_cfgSessionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($_cfgSessionPath)) {
        mkdir($_cfgSessionPath, 0755, true);
    }
    session_save_path($_cfgSessionPath);

    // 3. Start the session
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/status_helper.php';

// Base URL helper (supports pages in subfolders like ga_staff/*)
// Also supports root-access mode: http://localhost/NidecSecurity/login.php
// (Apache root .htaccess routes internally to public/index.php)
if (!defined('APP_BASE_URL')) {
    $projectDir = basename(dirname(__DIR__));
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    // Use REQUEST_URI (the URL the browser actually sent) to decide whether
    // /public should appear in the base URL — not SCRIPT_NAME, which always
    // points to public/index.php regardless of the access pattern.
    $requestUri = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $baseUrl = '';
    if ($requestUri !== '') {
        if (preg_match('#^(.*?/public)(?:/|$)#', $requestUri, $m)) {
            // Browser explicitly requested a /public/... path.
            $baseUrl = rtrim($m[1], '/');
        } elseif (preg_match('#^((?:/[^/?#]+)*/' . preg_quote($projectDir, '#') . ')(?:/|$)#i', $requestUri, $m)) {
            // Root-access: /NidecSecurity/login.php routed via root .htaccess.
            // Assets are transparently rewritten by the same .htaccess.
            $baseUrl = rtrim($m[1], '/');
        }
        // else: app served at the web root — $baseUrl stays ''
    } else {
        // CLI / CRON — no REQUEST_URI available; fall back to SCRIPT_NAME.
        if (preg_match('#^(.*?)/public(?:/|$)#', $scriptName, $m)) {
            $baseUrl = rtrim($m[1], '/') . '/public';
        } elseif (preg_match('#^((?:/[^/]+)*/' . preg_quote($projectDir, '#') . ')(?:/|$)#i', $scriptName, $m)) {
            $baseUrl = rtrim($m[1], '/');
        }
    }
    define('APP_BASE_URL', $baseUrl);
}

function app_url(string $path = ''): string
{
    // Pass through absolute URLs
    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    if ($path === '') {
        return APP_BASE_URL . '/';
    }
    return APP_BASE_URL . '/' . $path;
}

// Helper function to check if user is authenticated
function isAuthenticated()
{
    return isset($_SESSION['user']);
}

// Helper function to get current user
function getUser(): ?array
{
    if (!isset($_SESSION['user'])) {
        return null;
    }

    // Auto-repair stale sessions: users who logged in before the DB schema
    // migration (employee_id → employee_no) have employee_no = '' in their
    // session.  Resolve it via username once and write it back so every
    // controller/service gets the correct value without needing individual fixes.
    if (empty($_SESSION['user']['employee_no']) && !empty($_SESSION['user']['username'])) {
        $row = db_fetch_one('SELECT employee_no FROM users WHERE username = ? LIMIT 1', 's', [
            $_SESSION['user']['username'],
        ]);
        if ($row && $row['employee_no'] !== '') {
            $_SESSION['user']['employee_no'] = $row['employee_no'];
        }
    }

    return $_SESSION['user'];
}

// Helper function to check user role
function hasRole($role)
{
    $user = getUser();
    return $user && $user['role'] === $role;
}

function normalize_building($building): ?string
{
    $b = strtoupper(trim((string) $building));
    if ($b === '' || $b === 'ALL') {
        return null;
    }
    if (in_array($b, ['NCFL', 'NPFL'], true)) {
        return $b;
    }
    return null;
}

/**
 * Returns the building filter that should be applied to data queries.
 * - Security: always forced to their assigned entity (stored in session as 'entity').
 * - Other roles: optional, derived from request param `building` (GET/POST).
 */
function get_effective_building_filter(): ?string
{
    $user = getUser();
    $role = (string) ($user['role'] ?? '');

    // Security users are always scoped to their assigned entity.
    if ($role === 'security') {
        return normalize_building($user['entity'] ?? null);
    }

    // Allow filters on both GET and POST for pages/APIs for non-security roles.
    $requested = $_GET['building'] ?? ($_POST['building'] ?? null);
    return normalize_building($requested);
}
