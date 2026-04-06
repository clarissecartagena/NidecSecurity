<?php

class FinalCheckingModel
{
    public function findReportForFinalChecking(string $reportNo): ?array
    {
        $row = db_fetch_one(
            'SELECT id, submitted_by, status, responsible_department_id, reopen_count FROM reports WHERE report_no = ? LIMIT 1',
            's',
            [$reportNo],
        );

        return $row ?: null;
    }

    public function getReportsAwaitingFinalCheckingForUser(string $userId): array
    {
        return db_fetch_all(
            "SELECT r.report_no, r.subject, r.severity, r.submitted_at,
                    r.category, r.location, r.details,
                    DATEDIFF(NOW(), r.submitted_at) AS days_pending,
                    d.name AS department_name
             FROM reports r
             JOIN departments d ON d.id = r.responsible_department_id
             WHERE r.submitted_by = ? AND r.status = 'for_security_final_check'
             ORDER BY r.submitted_at DESC",
            's',
            [$userId],
        );
    }

    public function confirmResolved(int $reportId, string $userId, string $remarks): void
    {
        db_execute(
            "UPDATE reports
             SET status = 'resolved', current_reviewer = NULL,
                 resolved_by_security = ?, resolved_at = NOW(), security_remarks = ?
             WHERE id = ?",
            'ssi',
            [$userId, $remarks, $reportId],
        );

        db_execute(
            "INSERT INTO security_final_checks (report_id, decision, remarks, checked_by, checked_at, closed_at)
             VALUES (?, 'confirmed', ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE decision=VALUES(decision), remarks=VALUES(remarks), checked_by=VALUES(checked_by), checked_at=VALUES(checked_at), closed_at=VALUES(closed_at)",
            'iss',
            [$reportId, $remarks, $userId],
        );

        db_execute(
            'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, NOW())',
            'isss',
            [$reportId, 'resolved', $userId, $remarks],
        );
    }

    public function markNotResolved(int $reportId, string $userId, string $remarks): void
    {
        // Increment reopen_count atomically in the same UPDATE.
        db_execute(
            "UPDATE reports
             SET status = 'returned_to_department', current_reviewer = 'department',
                 returned_by_security = ?, returned_at = NOW(), security_remarks = ?,
                 reopen_count = reopen_count + 1
             WHERE id = ?",
            'ssi',
            [$userId, $remarks, $reportId],
        );

        db_execute(
            "INSERT INTO security_final_checks (report_id, decision, remarks, checked_by, checked_at, closed_at)
             VALUES (?, 'returned', ?, ?, NOW(), NULL)
             ON DUPLICATE KEY UPDATE decision=VALUES(decision), remarks=VALUES(remarks), checked_by=VALUES(checked_by), checked_at=VALUES(checked_at), closed_at=VALUES(closed_at)",
            'iss',
            [$reportId, $remarks, $userId],
        );

        db_execute(
            'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, NOW())',
            'isss',
            [$reportId, 'returned_to_department', $userId, $remarks],
        );
    }
}
