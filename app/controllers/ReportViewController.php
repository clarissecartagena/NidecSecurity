<?php

namespace App\Controllers;

class ReportViewController extends BaseController
{
    public function show(): void
    {
        require_once __DIR__ . '/../../includes/config.php';

        if (!isAuthenticated()) {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $user = getUser();
        $role = (string) ($user['role'] ?? '');

        // Validate the report_no param – only alphanumeric, dash, dot, underscore
        $reportNo = trim($_GET['id'] ?? '');
        if ($reportNo === '' || !preg_match('/^[A-Za-z0-9\-_.]+$/', $reportNo) || strlen($reportNo) > 50) {
            http_response_code(400);
            die('Invalid report id');
        }

        // Role-based access control mirroring report_pdf.php
        $allowedRoles = ['ga_manager', 'ga_staff', 'security', 'department', 'pic'];
        if (!in_array($role, $allowedRoles, true)) {
            http_response_code(403);
            die('Forbidden');
        }

        // Verify the report exists (respecting role-scoped visibility)
        $whereExtra = '';
        $params = [$reportNo];

        if ($role === 'security') {
        } elseif ($role === 'department') {
            $deptId = (int) ($user['department_id'] ?? 0);
            if ($deptId <= 0) {
                http_response_code(403);
                die('Account has no assigned department');
            }
            $whereExtra = ' AND r.responsible_department_id = ?';
            $params[] = $deptId;
        }

        $row = db_fetch_one('SELECT id FROM reports r WHERE r.report_no = ?' . $whereExtra . ' LIMIT 1', '', $params);

        if (!$row) {
            http_response_code(404);
            die('Report not found');
        }

        // Forward to the unified PDF endpoint with inline disposition
        $pdfUrl = app_url('api/report_pdf.php?id=' . urlencode($reportNo) . '&preview=1');
        header('Location: ' . $pdfUrl);
        exit();
    }
}
