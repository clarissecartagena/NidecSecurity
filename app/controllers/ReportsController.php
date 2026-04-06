<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/ReportsService.php';

class ReportsController extends BaseController
{
    private \ReportsService $service;

    public function __construct(?\ReportsService $service = null)
    {
        $this->service = $service ?: new \ReportsService();
    }

    public function index(): void
    {
        $pageTitle = 'All Reports';
        $requiredRole = ['ga_manager', 'ga_staff'];
        $currentPage = 'reports.php';

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $limit = 10;
        $page = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;

        $buildingFilter = get_effective_building_filter();
        $selectedBuilding = $buildingFilter ?? 'all';

        $data = $this->service->getReportsListData($buildingFilter, $page, $limit);
        $reports = $data['reports'];
        $totalReports = $data['totalReports'];
        $totalPages = $data['totalPages'];
        $offset = $data['offset'];
        $departmentsDb = fetch_departments();

        require __DIR__ . '/../../views/reports/reports.php';
    }
}
