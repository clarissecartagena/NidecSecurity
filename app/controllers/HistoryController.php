<?php

namespace App\Controllers;

class HistoryController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'Report History';
        $currentPage = 'history.php';

        require_once __DIR__ . '/../../includes/config.php';

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        // Legacy page referenced $mockReports but never defined it.
        // Keep the same UI with an empty dataset.
        $mockReports = [];

        require __DIR__ . '/../../views/reports/history.php';
    }
}
