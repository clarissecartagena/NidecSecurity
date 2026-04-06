<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/DashboardService.php';

class DashboardController extends BaseController
{
    private \DashboardService $service;

    public function __construct(?\DashboardService $service = null)
    {
        parent::__construct();
        $this->service = $service ?: new \DashboardService();
    }

    public function gaDashboard(): void
    {
        $pageTitle = 'Dashboard';
        $requiredRole = ['ga_manager', 'ga_staff'];
        $currentPage = 'dashboard.php';

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $user = getUser();
        $userRole = (string) ($user['role'] ?? '');

        $buildingFilter = get_effective_building_filter();
        $selectedBuilding = $buildingFilter ?? 'all';

        $managerStats = null;
        $managerRecent = [];
        $managerPending = [];

        $gaStaffCounts = null;
        $gaStaffWaiting = [];
        $gaStaffReturned = [];

        if ($userRole === 'ga_manager') {
            $data = $this->service->getGaManagerDashboardData($buildingFilter);
            $managerStats = $data['stats'];
            $managerRecent = $data['recent'];
            $managerPending = $data['pending'];
        } elseif ($userRole === 'ga_staff') {
            $data = $this->service->getGaStaffDashboardData($buildingFilter);
            $gaStaffCounts = $data['counts'];
            $gaStaffWaiting = $data['waiting'];
            $gaStaffReturned = $data['returned'];
        }

        // Backward compatibility for existing view variable names.
        $presidentStats = $managerStats;
        $presidentRecent = $managerRecent;
        $presidentPending = $managerPending;

        require __DIR__ . '/../../views/dashboard/ga_dashboard.php';
    }

    public function securityDashboard(): void
    {
        $pageTitle = 'Security Dashboard';
        $requiredRole = 'security';
        $currentPage = 'security-dashboard.php';

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $currentUser = getUser();
        $uid = (string) ($currentUser['employee_no'] ?? '');

        $data = $this->service->getSecurityDashboardData($uid);
        $stats = $data['stats'];
        $recent = $data['recent'];
        $finalChecks = $data['final_checks'];

        require __DIR__ . '/../../views/dashboard/security_dashboard.php';
    }

    public function departmentDashboard(): void
    {
        $pageTitle = 'Department Dashboard';
        $requiredRole = 'department';
        $currentPage = 'department-dashboard.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'department') {
            $this->redirect('login.php');
        }

        $deptId = (int) ($currentUser['department_id'] ?? 0);

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        if ($deptId <= 0) {
            http_response_code(500);
            die('Department account is missing a department assignment.');
        }

        $buildingFilter = get_effective_building_filter();
        $selectedBuilding = $buildingFilter ?? 'all';

        $data = $this->service->getDepartmentDashboardData($deptId, $buildingFilter);
        $stats = $data['stats'];
        $recent = $data['recent'];
        $needsAction = $data['needs_action'];

        require __DIR__ . '/../../views/dashboard/department_dashboard.php';
    }
}
