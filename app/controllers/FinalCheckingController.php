<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/FinalCheckingService.php';

class FinalCheckingController extends BaseController
{
    private \FinalCheckingService $service;

    public function __construct(?\FinalCheckingService $service = null)
    {
        $this->service = $service ?: new \FinalCheckingService();
    }

    public function index(): void
    {
        $pageTitle = 'Final Checking';
        $requiredRole = 'security';
        $currentPage = 'final-checking.php';

        require_once __DIR__ . '/../../includes/config.php';

        $flash = null;
        $flashType = 'success';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'security') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $uid = (string) ($currentUser['employee_no'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $this->service->handlePost($_POST, $uid);
            $flash = $res['flash'];
            $flashType = $res['flashType'];
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $reports = $this->service->getReportsForUser($uid);

        require __DIR__ . '/../../views/reports/final_checking.php';
    }
}
