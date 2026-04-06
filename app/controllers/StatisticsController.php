<?php

namespace App\Controllers;

class StatisticsController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'Statistics';
        $requiredRole = 'ga_manager';
        $currentPage = 'statistics.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'ga_manager') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/statistics/statistics.php';
    }
}
