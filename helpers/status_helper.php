<?php

function canonical_departments(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $constantsPath = __DIR__ . '/../config/constants.php';
    $departments = [];

    if (is_file($constantsPath)) {
        require $constantsPath;
        if (isset($departments) && is_array($departments)) {
            $departments = array_values(
                array_filter(array_map(static fn($d) => trim((string) $d), $departments), static fn($d) => $d !== ''),
            );
        } else {
            $departments = [];
        }
    }

    $cached = $departments;
    return $cached;
}

function fetch_departments(): array
{
    $canonical = canonical_departments();

    if (empty($canonical)) {
        return db_fetch_all('SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name');
    }

    foreach ($canonical as $departmentName) {
        db_execute(
            'INSERT INTO departments (name, is_active, created_at) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE is_active = 1',
            '',
            [$departmentName],
        );
    }

    $placeholders = implode(',', array_fill(0, count($canonical), '?'));
    $orderByField = implode(',', array_fill(0, count($canonical), '?'));
    $params = array_merge($canonical, $canonical);

    return db_fetch_all(
        "SELECT id, name
         FROM departments
         WHERE is_active = 1
           AND name IN ($placeholders)
         ORDER BY FIELD(name, $orderByField)",
        '',
        $params,
    );
}

// Centralized status mapping (DB enum + display label + expected reviewer)
$REPORT_STATUS_MAP = [
    'submitted_to_ga_manager' => ['label' => 'Submitted to GA Manager', 'reviewer' => 'ga_manager'],
    'sent_to_department' => ['label' => 'Sent to Department', 'reviewer' => 'department'],
    'under_department_fix' => ['label' => 'Under Department Fix', 'reviewer' => 'department'],
    'for_security_final_check' => ['label' => 'For Security Final Check', 'reviewer' => 'security'],
    'returned_to_department' => ['label' => 'Returned to Department', 'reviewer' => 'department'],
    'resolved' => ['label' => 'Resolved', 'reviewer' => null],

    // Legacy aliases (for pages that may still reference old values before migration)
    'pending_ga_manager_approval' => ['label' => 'Submitted to GA Manager', 'reviewer' => 'ga_manager'],
    'department_action' => ['label' => 'Under Department Fix', 'reviewer' => 'department'],
    'ga_manager_returned' => ['label' => 'Returned by GA Manager', 'reviewer' => 'security'],
    'ga_staff_returned' => ['label' => 'Returned to Security', 'reviewer' => 'security'],
    'closed' => ['label' => 'Resolved', 'reviewer' => null],
];

function report_status_label(string $status): string
{
    global $REPORT_STATUS_MAP;
    return $REPORT_STATUS_MAP[$status]['label'] ?? $status;
}

function report_status_default_reviewer(?string $status): ?string
{
    if ($status === null) {
        return null;
    }
    global $REPORT_STATUS_MAP;
    return $REPORT_STATUS_MAP[$status]['reviewer'] ?? null;
}

function severity_label(string $sev): string
{
    $labels = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];
    return $labels[$sev] ?? $sev;
}

function role_landing_page(?string $role): string
{
    switch ($role) {
        case 'security':
            return 'security-dashboard.php';
        case 'ga_staff':
        case 'ga_manager':
            return 'dashboard.php';
        case 'department':
            return 'department-dashboard.php';
        default:
            return 'login.php';
    }
}

/**
 * Auto-trigger: if a department fix timeline is due, move the report to Security final check.
 * Runs on normal page requests (no cron/external services), using existing schema.
 */
function auto_trigger_overdue_department_timelines(): void
{
    try {
        $conn = db();
        $conn->beginTransaction();

        $overdue = db_fetch_all(
            "SELECT r.id
             FROM reports r
             WHERE r.status = 'under_department_fix'
               AND r.fix_due_date IS NOT NULL
               AND r.fix_due_date <= NOW()",
        );

        foreach ($overdue as $o) {
            $rid = (int) $o['id'];
            // Keep this helper non-notifying; cron_check_timelines.php is the canonical automation.
            db_execute(
                "UPDATE reports SET status = 'for_security_final_check', current_reviewer = 'security' WHERE id = ?",
                'i',
                [$rid],
            );
        }

        $conn->commit();
    } catch (Throwable $e) {
        try {
            $c = db();
            if ($c->inTransaction()) {
                $c->rollBack();
            }
        } catch (Throwable $ignored) {
        }
        // Intentionally swallow errors to avoid breaking page rendering.
    }
}
