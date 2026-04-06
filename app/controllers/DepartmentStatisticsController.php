<?php

namespace App\Controllers;

class DepartmentStatisticsController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'Statistics';
        $requiredRole = 'department';
        $currentPage = 'department-statistics.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'department') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $deptId = (int) ($currentUser['department_id'] ?? 0);
        if ($deptId <= 0) {
            require_once __DIR__ . '/../../includes/header.php';
            require_once __DIR__ . '/../../includes/sidebar.php';
            require_once __DIR__ . '/../../includes/topnav.php';

            echo '<main class="main-content"><div class="animate-fade-in"><div class="alert alert-error">Department account is missing a department assignment.</div></div></main>';
            require_once __DIR__ . '/../../includes/footer.php';
            return;
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/statistics/department_statistics.php';
    }
}
