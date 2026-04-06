<?php

class AssignedReportsModel
{
    public function findReportForDepartment(string $reportNo, int $departmentId): ?array
    {
        $row = db_fetch_one(
            "SELECT id, status
             FROM reports
             WHERE report_no = ?
               AND responsible_department_id = ?
             LIMIT 1",
            'si',
            [$reportNo, $departmentId],
        );

        return $row ?: null;
    }

    public function getAssignedReports(int $departmentId, ?string $buildingFilter): array
    {
        $sql = "SELECT r.report_no, r.subject, r.severity, r.location, r.status,
                       COALESCE(ass.assigned_at, r.submitted_at) AS assigned_at
                FROM reports r
                LEFT JOIN (
                  SELECT report_id, MAX(changed_at) AS assigned_at
                  FROM report_status_history
                  WHERE status = 'sent_to_department'
                  GROUP BY report_id
                ) ass ON ass.report_id = r.id
                WHERE r.responsible_department_id = ?
                  AND r.status IN ('sent_to_department','returned_to_department')";
        $params = [$departmentId];

        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }

        $sql .= ' ORDER BY COALESCE(ass.assigned_at, r.submitted_at) DESC';

        return db_fetch_all($sql, '', $params);
    }

    public function setFixTimeline(int $reportId, string $userId, int $days, string $due): void
    {
        // Optimistic lock: only proceed if the report is still in a department-owned state.
        $affected = db_execute(
            "UPDATE reports
             SET status = 'under_department_fix', current_reviewer = 'department', fix_due_date = ?
             WHERE id = ? AND status IN ('sent_to_department','returned_to_department','under_department_fix')",
            'si',
            [$due, $reportId],
        );
        if ($affected === 0) {
            throw new RuntimeException('Report status changed by another process. Please refresh and try again.');
        }

        db_execute(
            "INSERT INTO department_actions (report_id, action_type, timeline_days, timeline_start, timeline_due, remarks, acted_by, acted_at)
             VALUES (?, 'timeline', ?, NOW(), ?, NULL, ?, NOW())
             ON DUPLICATE KEY UPDATE
               action_type=VALUES(action_type),
               timeline_days=VALUES(timeline_days),
               timeline_start=VALUES(timeline_start),
               timeline_due=VALUES(timeline_due),
               acted_by=VALUES(acted_by),
               acted_at=VALUES(acted_at)",
            'iiss',
            [$reportId, $days, $due, $userId],
        );

        db_execute(
            'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, NOW())',
            'isss',
            [$reportId, 'under_department_fix', $userId, 'Fix timeline set: ' . $days . ' day(s)'],
        );
    }

    public function markDone(int $reportId, string $userId): void
    {
        // Optimistic lock: prevent double-submission racing with cron auto-escalation.
        $affected = db_execute(
            "UPDATE reports
             SET status = 'for_security_final_check', current_reviewer = 'security', fix_due_date = NULL
             WHERE id = ? AND status IN ('sent_to_department','returned_to_department','under_department_fix')",
            'i',
            [$reportId],
        );
        if ($affected === 0) {
            throw new RuntimeException('Report has already been escalated or its status changed. Please refresh.');
        }

        db_execute(
            "INSERT INTO department_actions (report_id, action_type, timeline_days, timeline_start, timeline_due, remarks, acted_by, acted_at)
             VALUES (?, 'done', NULL, NULL, NULL, NULL, ?, NOW())
             ON DUPLICATE KEY UPDATE
               action_type=VALUES(action_type),
               timeline_days=NULL,
               timeline_start=NULL,
               timeline_due=NULL,
               acted_by=VALUES(acted_by),
               acted_at=VALUES(acted_at)",
            'is',
            [$reportId, $userId],
        );

        db_execute(
            'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, NOW())',
            'isss',
            [$reportId, 'for_security_final_check', $userId, 'Marked as DONE by Department'],
        );
    }
}
