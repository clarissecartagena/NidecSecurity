<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/DepartmentHistoryService.php';

class DepartmentHistoryController extends BaseController
{
    private \DepartmentHistoryService $service;

    public function __construct(?\DepartmentHistoryService $service = null)
    {
        $this->service = $service ?: new \DepartmentHistoryService();
    }

    public function index(): void
    {
        $pageTitle = 'Report History';
        $requiredRole = 'department';
        $currentPage = 'department-history.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'department') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $deptId = (int) ($currentUser['department_id'] ?? 0);
        if ($deptId <= 0) {
            http_response_code(500);
            die('Department account is missing a department assignment.');
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $buildingFilter = get_effective_building_filter();
        $selectedBuilding = $buildingFilter ?? 'all';

        $rows = $this->service->getRows($deptId, $buildingFilter);

        require __DIR__ . '/../../views/reports/department_history.php';
    }
}
