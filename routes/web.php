<?php
/**
 * Application route definitions.
 *
 * Expected variables in scope (set by public/index.php):
 *   $router – Router instance
 *
 * Routes now use Controller@method notation for cleaner, more maintainable code.
 * The Router will automatically resolve controllers via the DI container.
 */

use App\Core\Request;
use App\Core\Response;

// ─────────────────────────────────────────────
// Root  /
// ─────────────────────────────────────────────
$router->get('/', function (Request $req, Response $res): void {
    if (isset($_SESSION['user'])) {
        $res->redirect(role_landing_page($_SESSION['user']['role'] ?? null));
    }
    $res->redirect('login.php');
});

// ─────────────────────────────────────────────
// Authentication
// ─────────────────────────────────────────────
$router->get('/login.php', 'AuthController@login');
$router->post('/login.php', 'AuthController@login');
$router->get('/logout.php', 'LogoutController@index');

// Legacy clean-URL aliases (kept for any direct links that omit .php)
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'LogoutController@index');

// ─────────────────────────────────────────────
// Dashboards
// ─────────────────────────────────────────────
$router->get('/dashboard.php', 'DashboardController@gaDashboard');
$router->get('/security-dashboard.php', 'DashboardController@securityDashboard');
$router->get('/department-dashboard.php', 'DashboardController@departmentDashboard');

// ─────────────────────────────────────────────
// Reports – GA / shared
// ─────────────────────────────────────────────
$router->get('/reports.php', 'ReportsController@index');
$router->get('/returned-reports.php', 'ReturnedReportsController@index');
$router->post('/returned-reports.php', 'ReturnedReportsController@index');
$router->get('/ga-staff-review.php', 'GaStaffReviewController@index');
$router->post('/ga-staff-review.php', 'GaStaffReviewController@index');
$router->get('/ga-manager-approval.php', 'GaManagerApprovalController@index');
$router->post('/ga-manager-approval.php', 'GaManagerApprovalController@index');

// ─────────────────────────────────────────────
// Reports – Security
// ─────────────────────────────────────────────
$router->get('/submit-report.php', 'SubmitReportController@index');
$router->post('/submit-report.php', 'SubmitReportController@index');
$router->get('/final-checking.php', 'FinalCheckingController@index');
$router->post('/final-checking.php', 'FinalCheckingController@index');
$router->get('/security-reports.php', 'SecurityReportsController@index');

// ─────────────────────────────────────────────
// Reports – Department
// ─────────────────────────────────────────────
$router->get('/assigned-reports.php', 'AssignedReportsController@index');
$router->post('/assigned-reports.php', 'AssignedReportsController@index');
$router->get('/department-action.php', 'DepartmentActionController@index');
$router->post('/department-action.php', 'DepartmentActionController@index');
$router->get('/department-history.php', 'DepartmentHistoryController@index');

// ─────────────────────────────────────────────
// History / Print
// ─────────────────────────────────────────────
$router->get('/history.php', 'HistoryController@index');
$router->get('/print_report.php', 'PrintReportController@show');
$router->get('/view-report.php', 'ReportViewController@show');
$router->get('/print_report_by_no.php', 'PrintReportByNoController@handleRedirect');

// ─────────────────────────────────────────────
// Statistics
// ─────────────────────────────────────────────
$router->get('/statistics.php', 'StatisticsController@index');
$router->get('/security-statistics.php', 'SecurityStatisticsController@index');
$router->get('/department-statistics.php', 'DepartmentStatisticsController@index');

// ─────────────────────────────────────────────
// GA Staff sub-pages  (ga_staff/*)
// ─────────────────────────────────────────────
$router->get('/ga_staff/statistics.php', 'GaStaffStatisticsController@index');
$router->get('/ga_staff/user_management.php', 'GaStaffUserManagementController@index');
$router->post('/ga_staff/user_management.php', 'GaStaffUserManagementController@index');

// ─────────────────────────────────────────────
// Users & Notifications
// ─────────────────────────────────────────────
$router->get('/users.php', 'UsersController@index');
$router->post('/users.php', 'UsersController@index');
$router->get('/notifications.php', 'NotificationsController@index');
$router->post('/notifications.php', 'NotificationsController@index');

// ─────────────────────────────────────────────
// Profile
// ─────────────────────────────────────────────
$router->get('/profile.php', 'ProfileController@index');
$router->post('/profile.php', 'ProfileController@index');

// ─────────────────────────────────────────────
// Download
// ─────────────────────────────────────────────
$router->get('/download.php', 'DownloadController@index');
$router->post('/download.php', 'DownloadController@index');

// ─────────────────────────────────────────────
// 404  (direct access to legacy 404.php URL)
// ─────────────────────────────────────────────
$router->get('/404.php', function (Request $req, Response $res): void {
    http_response_code(404);
    require_once __DIR__ . '/../app/controllers/NotFoundController.php';
    (new NotFoundController())->index();
});
