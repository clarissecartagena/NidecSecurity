<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/SecurityReportsService.php';

class SecurityReportsController extends BaseController
{
    private \SecurityReportsService $service;

    public function __construct(?\SecurityReportsService $service = null)
    {
        $this->service = $service ?: new \SecurityReportsService();
    }

    public function index(): void
    {
        $pageTitle = 'All Reports';
        $requiredRole = 'security';
        $currentPage = 'security-reports.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'security') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $uid = (string) ($currentUser['employee_no'] ?? '');

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $reports = $this->service->getReportsForUser($uid);

        require __DIR__ . '/../../views/reports/security_reports.php';
    }
}
