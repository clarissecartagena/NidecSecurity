<?php

class DepartmentHistoryModel
{
    public function getDepartmentReportHistory(int $departmentId, ?string $buildingFilter): array
    {
        $sql = "SELECT r.report_no, r.subject, r.severity, r.location, r.status,
                       COALESCE(ass.assigned_at, r.submitted_at) AS assigned_at,
                       da.action_type, da.timeline_due
                FROM reports r
                LEFT JOIN department_actions da ON da.report_id = r.id
                LEFT JOIN (
                  SELECT report_id, MAX(changed_at) AS assigned_at
                  FROM report_status_history
                  WHERE status = 'sent_to_department'
                  GROUP BY report_id
                ) ass ON ass.report_id = r.id
                WHERE r.responsible_department_id = ?
                  AND r.status IN ('sent_to_department','under_department_fix','returned_to_department','for_security_final_check','resolved')";

        $params = [$departmentId];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }

        $sql .= ' ORDER BY COALESCE(ass.assigned_at, r.submitted_at) DESC';

        return db_fetch_all($sql, '', $params);
    }
}
