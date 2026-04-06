<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/GaStaffReviewService.php';

class GaStaffReviewController extends BaseController
{
    private \GaStaffReviewService $service;

    public function __construct(?\GaStaffReviewService $service = null)
    {
        $this->service = $service ?: new \GaStaffReviewService();
    }

    public function index(): void
    {
        $pageTitle = 'GA Pending Reports';
        $requiredRole = 'ga_staff';
        $currentPage = 'ga-staff-review.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated()) {
            header('Location: ' . app_url('login.php'));
            exit();
        }
        if (($currentUser['role'] ?? '') !== 'ga_staff') {
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

        require __DIR__ . '/../../views/reports/ga_staff_review.php';
    }
}
