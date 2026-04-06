<?php

namespace App\Controllers;

class DepartmentActionController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'Department Action';
        $currentPage = 'department-action.php';

        require_once __DIR__ . '/../../includes/config.php';
        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/department/department_action.php';

        require_once __DIR__ . '/../../includes/footer.php';
    }
}
