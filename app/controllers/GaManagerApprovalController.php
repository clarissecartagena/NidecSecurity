<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/GaManagerApprovalService.php';

class GaManagerApprovalController extends BaseController
{
    private \GaManagerApprovalService $service;

    public function __construct(?\GaManagerApprovalService $service = null)
    {
        $this->service = $service ?: new \GaManagerApprovalService();
    }

    public function index(): void
    {
        $pageTitle = 'GA Pending Reports';
        $requiredRole = 'ga_manager';
        $currentPage = 'ga-manager-approval.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated()) {
            header('Location: ' . app_url('login.php'));
            exit();
        }
        if (($currentUser['role'] ?? '') !== 'ga_manager') {
            http_response_code(403);
            die('Access denied.');
        }

        $flash = null;
        $flashType = 'success';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $this->service->handlePost($_POST, $currentUser);
            $flash = $res['flash'];
            $flashType = $res['flashType'];
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $buildingFilter = get_effective_building_filter();
        $selectedBuilding = $buildingFilter ?? 'all';

        $pending = $this->service->getPendingList($buildingFilter);
        $departments = function_exists('fetch_departments') ? fetch_departments() : [];

        require __DIR__ . '/../../views/reports/ga_manager_approval.php';
    }
}
