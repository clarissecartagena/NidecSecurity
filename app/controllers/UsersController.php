<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/UsersService.php';

class UsersController extends BaseController
{
    private \UsersService $service;

    public function __construct(?\UsersService $service = null)
    {
        $this->service = $service ?: new \UsersService();
    }

    public function index(): void
    {
        $pageTitle = 'User Management';
        $requiredRole = 'ga_manager';
        $currentPage = 'users.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'ga_manager') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $flash = null;
        $flashType = 'success';

        $departmentsDb = fetch_departments();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $res = $this->service->handlePost($_POST, (string) ($currentUser['employee_no'] ?? ''));
            $flash = $res['flash'];
            $flashType = $res['flashType'];
        }

        $users = $this->service->getAllUsers();

        $totalUsers = count($users);
        $activeUsers = 0;
        $securityUsers = 0;
        foreach ($users as $u) {
            if (($u['account_status'] ?? '') === 'active') {
                $activeUsers++;
            }
            if (($u['role'] ?? '') === 'security') {
                $securityUsers++;
            }
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/users/users.php';
    }
}
