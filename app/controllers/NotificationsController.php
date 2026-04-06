<?php

namespace App\Controllers;

class NotificationsController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'Notifications';
        $requiredRole = ['security', 'ga_staff', 'ga_manager', 'department'];
        $currentPage = 'notifications.php';

        require_once __DIR__ . '/../../includes/config.php';

        $filter = strtolower(trim((string) ($_GET['filter'] ?? 'unread')));
        if (!in_array($filter, ['unread', 'today', 'week', 'month', 'all'], true)) {
            $filter = 'unread';
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/notifications/notifications.php';
    }
}
