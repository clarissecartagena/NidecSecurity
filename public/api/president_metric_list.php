<?php

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!hasRole('ga_manager')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

$type = strtolower(trim($_GET['type'] ?? ''));
$allowed = ['pending', 'critical', 'in_progress', 'overdue'];
if (!in_array($type, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type']);
    exit();
}

$limit = (int) ($_GET['limit'] ?? 50);
if ($limit < 1) {
    $limit = 1;
}
if ($limit > 200) {
    $limit = 200;
}

$buildingFilter = get_effective_building_filter();
$whereBuilding = '';
$buildingParams = [];
if ($buildingFilter) {
    $whereBuilding = ' AND r.building = ?';
    $buildingParams[] = $buildingFilter;
}

$titleMap = [
    'pending' => 'Pending GA Approval',
    'critical' => 'Critical Severity',
    'in_progress' => 'Reports In Progress',
    'overdue' => 'Overdue Tasks',
];

$items = [];

switch ($type) {
    case 'pending':
        $items = db_fetch_all(
            "SELECT r.report_no, r.subject, r.severity, r.status, r.submitted_at, d.name AS department_name
             FROM reports r
             JOIN departments d ON d.id = r.responsible_department_id
                         WHERE r.status = 'submitted_to_ga_manager'
                             AND r.current_reviewer = 'ga_manager'
                         {$whereBuilding}
             ORDER BY r.submitted_at DESC
             LIMIT {$limit}",
            '',
            $buildingParams,
        );
        break;

    case 'critical':
        $items = db_fetch_all(
            "SELECT r.report_no, r.subject, r.severity, r.status, r.submitted_at, d.name AS department_name
             FROM reports r
             JOIN departments d ON d.id = r.responsible_department_id
             WHERE r.severity = 'critical'
             {$whereBuilding}
             ORDER BY r.submitted_at DESC
             LIMIT {$limit}",
            '',
            $buildingParams,
        );
        break;

    case 'in_progress':
        $items = db_fetch_all(
            "SELECT r.report_no, r.subject, r.severity, r.status, r.submitted_at, d.name AS department_name
             FROM reports r
             JOIN departments d ON d.id = r.responsible_department_id
             WHERE r.status IN ('under_department_fix','returned_to_department','for_security_final_check')
             {$whereBuilding}
             ORDER BY r.submitted_at DESC
             LIMIT {$limit}",
            '',
            $buildingParams,
        );
        break;

    case 'overdue':
        $items = db_fetch_all(
            "SELECT r.report_no, r.subject, r.severity, r.status, r.fix_due_date, r.submitted_at, d.name AS department_name,
                    DATEDIFF(NOW(), r.fix_due_date) AS days_overdue
             FROM reports r
             JOIN departments d ON d.id = r.responsible_department_id
             WHERE r.status = 'under_department_fix'
               AND r.fix_due_date IS NOT NULL
               AND NOW() > r.fix_due_date
             {$whereBuilding}
             ORDER BY r.fix_due_date ASC
             LIMIT {$limit}",
            '',
            $buildingParams,
        );
        break;
}

$outItems = [];
foreach ($items as $r) {
    $outItems[] = [
        'report_no' => $r['report_no'] ?? '',
        'subject' => $r['subject'] ?? '',
        'department' => $r['department_name'] ?? '',
        'severity' => $r['severity'] ?? '',
        'status' => $r['status'] ?? '',
        'submitted_at' => $r['submitted_at'] ?? null,
        'fix_due_date' => $r['fix_due_date'] ?? null,
        'days_overdue' => isset($r['days_overdue']) ? (int) $r['days_overdue'] : null,
    ];
}

echo json_encode([
    'type' => $type,
    'title' => $titleMap[$type] ?? 'Metric',
    'items' => $outItems,
]);
