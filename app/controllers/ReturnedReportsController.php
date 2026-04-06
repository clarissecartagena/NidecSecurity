<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/ReturnedReportsService.php';

class ReturnedReportsController extends BaseController
{
    private \ReturnedReportsService $service;

    public function __construct(?\ReturnedReportsService $service = null)
    {
        $this->service = $service ?: new \ReturnedReportsService();
    }

    public function index(): void
    {
        $pageTitle = 'Returned Reports';
        $requiredRole = 'ga_staff';
        $currentPage = 'returned-reports.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'ga_staff') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $uid = (string) ($currentUser['employee_no'] ?? '');

        $flash = null;
        $flashType = 'success';

        $departmentsDb = fetch_departments();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $this->service->handlePost($_POST, $uid);
            $flash = $res['flash'];
            $flashType = $res['flashType'];
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $buildingFilter = get_effective_building_filter();
        $selectedBuilding = $buildingFilter ?? 'all';

        $returned = $this->service->getReturned($buildingFilter);

        require __DIR__ . '/../../views/reports/returned_reports.php';
    }
}
