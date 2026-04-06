<?php
// ============================================================
// Cron automation — timeline warnings, deadline escalation,
// and post-escalation follow-up.
//
// Intended to run every minute.
// Linux cron example:  * * * * * /usr/bin/php /path/to/cron_check_timelines.php
// ============================================================

require_once __DIR__ . '/../includes/config.php';

$startedAt = microtime(true);
$isCli = PHP_SAPI === 'cli';

$argv = $isCli ? $_SERVER['argv'] ?? [] : [];
$dryRun = (!$isCli && ($_GET['dry_run'] ?? '') === '1') || ($isCli && in_array('--dry-run', $argv, true));

$limit = 250;
if ($isCli) {
    foreach ($argv as $a) {
        if (substr($a, 0, strlen('--limit=')) === '--limit=') {
            $limit = (int) substr($a, strlen('--limit='));
        }
    }
} else {
    if (isset($_GET['limit'])) {
        $limit = (int) $_GET['limit'];
    }
}
$limit = max(1, min(1000, $limit));

$results = [
    'dry_run' => $dryRun,
    'limit' => $limit,
    'processed' => 0,
    'updated' => 0,
    'report_nos' => [],
    'errors' => [],
    'duration_ms' => 0,
];

// ============================================================
// Helper: individually notify each active department user for
// a report, with configurable dedup window (seconds).
// Returns the number of notifications inserted.
// ============================================================
function cron_notify_department_users(int $rid, int $deptId, string $message, int $dedupSeconds): int
{
    if ($deptId <= 0) {
        return 0;
    }
    $deptUsers = db_fetch_all(
        "SELECT employee_no FROM users WHERE role = 'department' AND account_status = 'active' AND department_id = ?",
        'i',
        [$deptId],
    );
    $sent = 0;
    foreach ($deptUsers as $u) {
        $uid = (string) ($u['employee_no'] ?? '');
        if ($uid === '') {
            continue;
        }
        notify_user($uid, $rid, $message, $dedupSeconds);
        $sent++;
    }
    return $sent;
}

try {
    $conn = db();
    $conn->beginTransaction();

    // ----------------------------------------------------------
    // BLOCK A — Pre-deadline warnings for under_department_fix
    //
    // Each bucket fires once within its dedup window; the cron
    // runs every minute but notifications are suppressed until
    // the window expires.
    //
    //  Bucket   | Fires when remaining ∈      | Dedup window
    //  ---------|-----------------------------|--------------
    //  7-day    | (6 days, 7 days]            | 12 hours
    //  3-day    | (2 days, 3 days]            | 6 hours
    //  24-hour  | (0,      1 day]             | 6 hours
    // ----------------------------------------------------------

    // A1 — 7-day warning
    $sevenDay = db_fetch_all(
        "SELECT id, report_no, fix_due_date, responsible_department_id
         FROM reports
         WHERE status = 'under_department_fix'
           AND fix_due_date IS NOT NULL
           AND fix_due_date > DATE_ADD(NOW(), INTERVAL 6 DAY)
           AND fix_due_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
         ORDER BY fix_due_date ASC
         LIMIT {$limit}",
    );
    foreach ($sevenDay as $r) {
        $results['processed']++;
        if ($dryRun) {
            $results['report_nos'][] = $r['report_no'];
            continue;
        }
        $sent = cron_notify_department_users(
            (int) $r['id'],
            (int) ($r['responsible_department_id'] ?? 0),
            'Fix Timeline Due in 7 Days',
            43200, // 12-hour dedup
        );
        $results['updated'] += $sent;
    }

    // A2 — 3-day warning
    $threeDay = db_fetch_all(
        "SELECT id, report_no, fix_due_date, responsible_department_id
         FROM reports
         WHERE status = 'under_department_fix'
           AND fix_due_date IS NOT NULL
           AND fix_due_date > DATE_ADD(NOW(), INTERVAL 2 DAY)
           AND fix_due_date <= DATE_ADD(NOW(), INTERVAL 3 DAY)
         ORDER BY fix_due_date ASC
         LIMIT {$limit}",
    );
    foreach ($threeDay as $r) {
        $results['processed']++;
        if ($dryRun) {
            $results['report_nos'][] = $r['report_no'];
            continue;
        }
        $sent = cron_notify_department_users(
            (int) $r['id'],
            (int) ($r['responsible_department_id'] ?? 0),
            'Fix Timeline Due in 3 Days',
            21600, // 6-hour dedup
        );
        $results['updated'] += $sent;
    }

    // A3 — 24-hour warning
    $dueSoon = db_fetch_all(
        "SELECT id, report_no, fix_due_date, responsible_department_id
         FROM reports
         WHERE status = 'under_department_fix'
           AND fix_due_date IS NOT NULL
           AND fix_due_date > NOW()
           AND fix_due_date <= DATE_ADD(NOW(), INTERVAL 1 DAY)
         ORDER BY fix_due_date ASC
         LIMIT {$limit}",
    );
    foreach ($dueSoon as $r) {
        $results['processed']++;
        if ($dryRun) {
            $results['report_nos'][] = $r['report_no'];
            continue;
        }
        $sent = cron_notify_department_users(
            (int) $r['id'],
            (int) ($r['responsible_department_id'] ?? 0),
            'Fix Timeline Due Soon (within 24 hours)',
            21600, // 6-hour dedup
        );
        $results['updated'] += $sent;
    }

    // ----------------------------------------------------------
    // BLOCK B — Deadline reached: auto-escalate to Security
    //
    // Optimistic lock (WHERE status = 'under_department_fix')
    // ensures exactly one cron invocation transitions each report.
    // Concurrent runs skip reports already escalated.
    // ----------------------------------------------------------
    $overdue = db_fetch_all(
        "SELECT id, report_no, fix_due_date
         FROM reports
         WHERE status = 'under_department_fix'
           AND fix_due_date IS NOT NULL
           AND fix_due_date <= NOW()
         ORDER BY fix_due_date ASC
         LIMIT {$limit}",
    );

    foreach ($overdue as $r) {
        $results['processed']++;
        $rid = (int) $r['id'];
        $reportNo = (string) $r['report_no'];

        if ($dryRun) {
            $results['report_nos'][] = $reportNo;
            continue;
        }

        $updated = db_execute(
            "UPDATE reports
             SET status = 'for_security_final_check',
                 current_reviewer = 'security',
                 fix_due_date = NULL
             WHERE id = ?
               AND status = 'under_department_fix'",
            'i',
            [$rid],
        );

        if ($updated > 0) {
            $results['updated']++;
            $results['report_nos'][] = $reportNo;

            db_execute(
                "INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at)
                 VALUES (?, 'for_security_final_check', NULL, 'Fix timeline reached (auto-escalated to Security final check)', NOW())",
                'i',
                [$rid],
            );

            // One-time events per transition — no dedup needed.
            notify_role('security', $rid, 'Fix Timeline Reached. Please Perform Final Check');
            notify_role('ga_staff', $rid, 'Fix Timeline Reached (Auto-escalated to Security Final Check)');
            notify_role('ga_manager', $rid, 'Fix Timeline Reached (Auto-escalated to Security Final Check)');
        }
    }

    // ----------------------------------------------------------
    // BLOCK C — Post-escalation follow-up
    //
    // Reports stuck in for_security_final_check emit periodic
    // reminders until Security acts (status leaves this value).
    //
    //  Delay | Recipients                  | Dedup  | Repeats?
    //  ------|-----------------------------|---------|---------
    //  +2d   | Security                    | 23 h   | daily until resolved
    //  +5d   | GA Staff + GA President     | 23 h   | daily until resolved
    //
    // Stop condition: both chains stop automatically when the
    // report transitions to 'resolved' or 'returned_to_department'
    // because those statuses are excluded from the WHERE clause.
    // ----------------------------------------------------------

    // C1 — +2 days: Security daily reminder
    $plus2 = db_fetch_all(
        "SELECT id, report_no
         FROM reports
         WHERE status = 'for_security_final_check'
           AND updated_at IS NOT NULL
           AND updated_at <= DATE_SUB(NOW(), INTERVAL 2 DAY)
         ORDER BY updated_at ASC
         LIMIT {$limit}",
    );
    foreach ($plus2 as $r) {
        $results['processed']++;
        if ($dryRun) {
            $results['report_nos'][] = $r['report_no'];
            continue;
        }
        notify_role('security', (int) $r['id'], 'Reminder: Report Awaiting Your Final Check (Overdue)', null, 82800); // 23-hour dedup ≈ once per day
        $results['updated']++;
    }

    // C2 — +5 days: GA-level escalation daily alert
    $plus5 = db_fetch_all(
        "SELECT id, report_no
         FROM reports
         WHERE status = 'for_security_final_check'
           AND updated_at IS NOT NULL
           AND updated_at <= DATE_SUB(NOW(), INTERVAL 5 DAY)
         ORDER BY updated_at ASC
         LIMIT {$limit}",
    );
    foreach ($plus5 as $r) {
        $results['processed']++;
        if ($dryRun) {
            $results['report_nos'][] = $r['report_no'];
            continue;
        }
        $rid = (int) $r['id'];
        notify_role(
            'ga_staff',
            $rid,
            '[ESCALATION] Security Final Check Overdue by 5+ Days. GA Staff review required.',
            null,
            82800,
        );
        notify_role('ga_manager', $rid, '[ESCALATION] Security Final Check Overdue by 5+ Days.', null, 82800);
        $results['updated']++;
    }

    $conn->commit();
} catch (Throwable $e) {
    $results['errors'][] = $e->getMessage();
    try {
        $c = db();
        if ($c->inTransaction()) {
            $c->rollBack();
        }
    } catch (Throwable $ignored) {
    }
}

$results['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

if ($isCli) {
    echo json_encode($results, JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results, JSON_UNESCAPED_SLASHES);
}

$argv = $isCli ? $_SERVER['argv'] ?? [] : [];
$dryRun = (!$isCli && ($_GET['dry_run'] ?? '') === '1') || ($isCli && in_array('--dry-run', $argv, true));

$limit = 250;
if ($isCli) {
    foreach ($argv as $a) {
        if (substr($a, 0, strlen('--limit=')) === '--limit=') {
            $limit = (int) substr($a, strlen('--limit='));
        }
    }
} else {
    if (isset($_GET['limit'])) {
        $limit = (int) $_GET['limit'];
    }
}
$limit = max(1, min(1000, $limit));

$results = [
    'dry_run' => $dryRun,
    'limit' => $limit,
    'processed' => 0,
    'updated' => 0,
    'report_nos' => [],
    'errors' => [],
    'duration_ms' => 0,
];

try {
    $conn = db();
    $conn->beginTransaction();

    // 1) Due soon reminders (within next 24 hours): notify responsible Department (deduped for 24h)
    $dueSoon = db_fetch_all(
        "SELECT id, report_no, fix_due_date, responsible_department_id
         FROM reports
         WHERE status = 'under_department_fix'
           AND fix_due_date IS NOT NULL
           AND fix_due_date > NOW()
           AND fix_due_date <= DATE_ADD(NOW(), INTERVAL 1 DAY)
         ORDER BY fix_due_date ASC
         LIMIT {$limit}",
    );

    foreach ($dueSoon as $r) {
        $results['processed']++;
        $rid = (int) $r['id'];
        $deptId = (int) ($r['responsible_department_id'] ?? 0);
        $reportNo = (string) $r['report_no'];

        if ($dryRun) {
            $results['report_nos'][] = $reportNo;
            continue;
        }

        if ($deptId <= 0) {
            continue;
        }

        $message = 'Fix Timeline Due Soon (within 24 hours)';

        // Dedupe per-user for the same report/message in the last 24 hours
        $alreadyRows = db_fetch_all(
            'SELECT DISTINCT user_id FROM notifications WHERE report_id = ? AND message = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)',
            'is',
            [$rid, $message],
        );
        $already = [];
        foreach ($alreadyRows as $ar) {
            $already[(string) ($ar['user_id'] ?? '')] = true;
        }

        $deptUsers = db_fetch_all(
            "SELECT employee_no FROM users WHERE role = 'department' AND account_status = 'active' AND department_id = ?",
            'i',
            [$deptId],
        );

        foreach ($deptUsers as $u) {
            $uid = (string) ($u['employee_no'] ?? '');
            if ($uid === '') {
                continue;
            }
            if (isset($already[$uid])) {
                continue;
            }
            notify_user($uid, $rid, $message);
            $results['updated']++;
        }
    }

    $rows = db_fetch_all(
        "SELECT id, report_no, fix_due_date
         FROM reports
         WHERE status = 'under_department_fix'
           AND fix_due_date IS NOT NULL
           AND fix_due_date <= NOW()
         ORDER BY fix_due_date ASC
         LIMIT {$limit}",
    );

    foreach ($rows as $r) {
        $results['processed']++;
        $rid = (int) $r['id'];
        $reportNo = (string) $r['report_no'];

        if ($dryRun) {
            $results['report_nos'][] = $reportNo;
            continue;
        }

        $updated = db_execute(
            "UPDATE reports
             SET status = 'for_security_final_check',
                 current_reviewer = 'security',
                 fix_due_date = NULL
             WHERE id = ?
               AND status = 'under_department_fix'",
            'i',
            [$rid],
        );

        if ($updated > 0) {
            $results['updated']++;
            $results['report_nos'][] = $reportNo;

            db_execute(
                "INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at)
                 VALUES (?, 'for_security_final_check', NULL, 'Fix timeline reached (auto-escalated to Security final check)', NOW())",
                'i',
                [$rid],
            );

            notify_role('security', $rid, 'Fix Timeline Reached. Please Perform Final Check');

            // Notifications: Timeline reached -> notify GA roles (audit/visibility)
            notify_role('ga_staff', $rid, 'Fix Timeline Reached (Auto-escalated to Security Final Check)');
            notify_role('ga_manager', $rid, 'Fix Timeline Reached (Auto-escalated to Security Final Check)');
        }
    }

    $conn->commit();
} catch (Throwable $e) {
    $results['errors'][] = $e->getMessage();
    try {
        $c = db();
        if ($c->inTransaction()) {
            $c->rollBack();
        }
    } catch (Throwable $ignored) {
    }
}

$results['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

if ($isCli) {
    echo json_encode($results, JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results, JSON_UNESCAPED_SLASHES);
}
