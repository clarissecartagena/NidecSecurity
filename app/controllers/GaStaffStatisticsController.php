<?php

namespace App\Controllers;

class GaStaffStatisticsController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'Statistics';
        $requiredRole = 'ga_staff';
        $currentPage = 'ga_staff/statistics.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'ga_staff') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        require __DIR__ . '/../../views/ga_staff/statistics.php';
    }
}
