<?php
/**
 * Front Controller — single HTTP entry point.
 *
 * Apache .htaccess rewrites all page requests here.
 * Static assets, uploads, and API files are served directly (bypassed in .htaccess).
 */

// Start session — use a project-local storage directory so Apache has
// guaranteed write access regardless of C:\xampp\tmp permissions.
$_sessionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
if (!is_dir($_sessionPath)) {
    mkdir($_sessionPath, 0755, true);
}
session_save_path($_sessionPath);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload dependencies via Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Load legacy configuration and helpers (for backward compatibility)
require_once __DIR__ . '/../includes/config.php';

// Create the Application instance
use App\Core\Application;

$app = new Application();

// Get router from application
$router = $app->getRouter();

// Load routes
require __DIR__ . '/../routes/web.php';

// Run the application
$app->run();
