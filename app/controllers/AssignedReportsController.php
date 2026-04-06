<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/AssignedReportsService.php';

class AssignedReportsController extends BaseController
{
    private \AssignedReportsService $service;

    public function __construct(?\AssignedReportsService $service = null)
    {
        $this->service = $service ?: new \AssignedReportsService();
    }

    public function index(): void
    {
        $pageTitle = 'Assigned Reports';
        $requiredRole = 'department';
        $currentPage = 'assigned-reports.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'department') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $uid = (string) ($currentUser['employee_no'] ?? '');
        $deptId = (int) ($currentUser['department_id'] ?? 0);

        if ($deptId <= 0) {
            http_response_code(500);
            die('Department account is missing a department assignment.');
        }

        $buildingFilter = get_effective_building_filter();
        $selectedBuilding = $buildingFilter ?? 'all';

        $flash = null;
        $flashType = 'success';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $this->service->handlePost($_POST, $uid, $deptId);
            $flash = $res['flash'];
            $flashType = $res['flashType'];
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $assigned = $this->service->getAssigned($deptId, $buildingFilter);

        require __DIR__ . '/../../views/reports/assigned_reports.php';
    }
}
