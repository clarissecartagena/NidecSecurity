<?php

class ReturnedReportsModel
{
    public function getReturnedReports(?string $buildingFilter): array
    {
        $sql = "SELECT
                    r.report_no, r.subject, r.severity, r.submitted_at,
                    d.name AS department_name,
                    gapa.notes AS president_notes,
                    gapa.notes AS return_reason
                FROM reports r
                JOIN departments d ON d.id = r.responsible_department_id
                                LEFT JOIN ga_manager_approvals gapa ON gapa.report_id = r.id
                                WHERE r.status = 'submitted_to_ga_manager'
                  AND r.current_reviewer = 'ga_staff'";

        $params = [];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }

        $sql .= ' ORDER BY r.submitted_at DESC';

        return db_fetch_all($sql, '', $params);
    }

    public function findReportForResubmit(string $reportNo): ?array
    {
        $row = db_fetch_one('SELECT id, status, current_reviewer FROM reports WHERE report_no = ? LIMIT 1', 's', [
            $reportNo,
        ]);

        return $row ?: null;
    }

    public function resubmitReport(
        int $reportId,
        string $subject,
        string $category,
        string $location,
        string $severity,
        int $departmentId,
        string $details,
        string $actionsTaken,
        string $remarks,
    ): void {
        db_execute(
            "UPDATE reports
             SET subject = ?, category = ?, location = ?, severity = ?, responsible_department_id = ?,
                 details = ?, actions_taken = ?, remarks = ?, status = 'submitted_to_ga_manager', current_reviewer = 'ga_manager'
             WHERE id = ?",
            'ssssisssi',
            [$subject, $category, $location, $severity, $departmentId, $details, $actionsTaken, $remarks, $reportId],
        );
    }

    public function upsertGaStaffReview(int $reportId, string $userId, string $notes): void
    {
        db_execute(
            "INSERT INTO ga_staff_reviews (report_id, reviewed_by, decision, notes, reviewed_at)
             VALUES (?, ?, 'forwarded', ?, NOW())
             ON DUPLICATE KEY UPDATE reviewed_by=VALUES(reviewed_by), decision=VALUES(decision), notes=VALUES(notes), reviewed_at=VALUES(reviewed_at)",
            'iss',
            [$reportId, $userId, $notes],
        );
    }

    public function insertStatusHistory(int $reportId, string $userId, string $notes): void
    {
        db_execute(
            'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, NOW())',
            'isss',
            [$reportId, 'submitted_to_ga_manager', $userId, $notes],
        );
    }
}
