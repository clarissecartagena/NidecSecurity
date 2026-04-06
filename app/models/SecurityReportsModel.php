<?php

class SecurityReportsModel
{
    public function getReportsBySubmitter(string $userId): array
    {
        return db_fetch_all(
            "SELECT r.report_no, r.subject, r.category, r.severity, r.status, r.submitted_at, d.name AS department_name
             FROM reports r
             JOIN departments d ON d.id = r.responsible_department_id
             WHERE r.submitted_by = ?
             ORDER BY r.submitted_at DESC",
            's',
            [$userId],
        );
    }
}
