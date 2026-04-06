<?php

namespace App\Controllers;

class PrintReportByNoController extends BaseController
{
    public function handleRedirect(): void
    {
        require_once __DIR__ . '/../../includes/config.php';

        if (!isAuthenticated()) {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $reportNo = trim((string) ($_GET['id'] ?? ''));
        if ($reportNo === '') {
            http_response_code(400);
            echo 'Missing id';
            exit();
        }

        $user = getUser();
        $role = (string) ($user['role'] ?? '');
        $userBuilding = normalize_building($user['entity'] ?? null);
        $userDepartmentId = (int) ($user['department_id'] ?? 0);

        $whereExtra = '';
        $params = [$reportNo];

        if ($role === 'security') {
            if (!$userBuilding) {
                http_response_code(403);
                echo 'Account is missing an assigned building';
                exit();
            }
            $whereExtra = ' AND building = ?';
            $params[] = $userBuilding;
        } elseif ($role === 'department') {
            if ($userDepartmentId <= 0) {
                http_response_code(403);
                echo 'Account is missing an assigned department';
                exit();
            }
            $whereExtra = ' AND responsible_department_id = ?';
            $params[] = $userDepartmentId;
        }

        // GA roles can view all; for other roles, scope by building/department.
        $row = db_fetch_one('SELECT id FROM reports WHERE report_no = ?' . $whereExtra . ' LIMIT 1', '', $params);
        if (!$row || empty($row['id'])) {
            http_response_code(404);
            echo 'Report not found';
            exit();
        }

        $rid = (int) $row['id'];
        header('Location: ' . app_url('print_report.php?report_id=' . urlencode((string) $rid)));
        exit();
    }
}
