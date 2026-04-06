<?php

namespace App\Controllers;

class DownloadController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'Download Reports';
        $currentPage = 'download.php';

        require_once __DIR__ . '/../../includes/config.php';

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $reports = [
            ['label' => 'Daily Report', 'desc' => "Today's security summary", 'date' => '2024-12-15'],
            ['label' => 'Weekly Report', 'desc' => "This week's overview", 'date' => '2024-12-09 to 2024-12-15'],
            ['label' => 'Monthly Report', 'desc' => 'December 2024 report', 'date' => 'December 2024'],
        ];

        require __DIR__ . '/../../views/download/download.php';
    }
}
