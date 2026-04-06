<?php

class DashboardModel
{
    public function getGaManagerStats(?string $buildingFilter): array
    {
        $sql = "SELECT
                    SUM(CASE WHEN status = 'submitted_to_ga_manager' AND current_reviewer = 'ga_manager' THEN 1 ELSE 0 END) AS pending_ga,
                    SUM(CASE WHEN status IN ('under_department_fix','returned_to_department','for_security_final_check') THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) AS critical,
                    SUM(CASE WHEN status = 'under_department_fix' AND fix_due_date IS NOT NULL AND NOW() > fix_due_date THEN 1 ELSE 0 END) AS overdue
                FROM reports
                WHERE 1=1";
        $params = [];
        if ($buildingFilter) {
            $sql .= ' AND building = ?';
            $params[] = $buildingFilter;
        }

        return db_fetch_one($sql, '', $params) ?: [];
    }

    public function getGaManagerRecentReports(?string $buildingFilter, int $limit = 5): array
    {
        $sql = "SELECT r.report_no, r.subject, r.severity, r.status, r.submitted_at, d.name AS department_name,
                   u.name AS submitted_by_name
                FROM reports r
            JOIN departments d ON d.id = r.responsible_department_id
            LEFT JOIN users u ON u.employee_no = r.submitted_by";
        $params = [];
        if ($buildingFilter) {
            $sql .= ' WHERE r.building = ?';
            $params[] = $buildingFilter;
        }
        $sql .= ' ORDER BY r.submitted_at DESC LIMIT ' . (int) $limit;

        return db_fetch_all($sql, '', $params);
    }

    public function getGaStaffCounts(?string $buildingFilter): array
    {
        $sql = "SELECT
                    SUM(CASE WHEN status = 'submitted_to_ga_manager' AND current_reviewer = 'ga_manager' THEN 1 ELSE 0 END) AS waiting,
                    SUM(CASE WHEN status = 'sent_to_department' THEN 1 ELSE 0 END) AS forwarded,
                    SUM(CASE WHEN status = 'returned_to_department' THEN 1 ELSE 0 END) AS returned
                FROM reports
                WHERE 1=1";
        $params = [];
        if ($buildingFilter) {
            $sql .= ' AND building = ?';
            $params[] = $buildingFilter;
        }

        return db_fetch_one($sql, '', $params) ?: [];
    }

    public function getGaStaffWaitingReports(?string $buildingFilter, int $limit = 5): array
    {
        $sql = "SELECT r.report_no, r.subject, r.severity, r.submitted_at, d.name AS department_name, u.name AS submitted_by_name
                FROM reports r
                JOIN departments d ON d.id = r.responsible_department_id
                LEFT JOIN users u ON u.employee_no = r.submitted_by
                WHERE r.status = 'submitted_to_ga_manager' AND r.current_reviewer = 'ga_manager'";
        $params = [];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }
        $sql .= ' ORDER BY r.submitted_at DESC LIMIT ' . (int) $limit;

        return db_fetch_all($sql, '', $params);
    }

    public function getGaStaffReturnedReports(?string $buildingFilter, int $limit = 5): array
    {
        $sql = "SELECT r.report_no, r.subject, r.severity, r.submitted_at, d.name AS department_name,
                       gapa.decided_at AS returned_at, gapa.notes AS manager_notes
                FROM reports r
                JOIN departments d ON d.id = r.responsible_department_id
                LEFT JOIN ga_manager_approvals gapa ON gapa.report_id = r.id
                WHERE r.status = 'submitted_to_ga_manager' AND r.current_reviewer = 'ga_staff'";
        $params = [];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }
        $sql .= ' ORDER BY gapa.decided_at DESC, r.submitted_at DESC LIMIT ' . (int) $limit;

        return db_fetch_all($sql, '', $params);
    }

    public function getSecurityStatsForUser(string $userId): array
    {
        return db_fetch_one(
            "SELECT
                SUM(CASE WHEN DATE(submitted_at) = CURDATE() THEN 1 ELSE 0 END) AS submitted_today,
                SUM(CASE WHEN status = 'submitted_to_ga_manager' THEN 1 ELSE 0 END) AS waiting_ga_review,
                SUM(CASE WHEN status = 'for_security_final_check' AND (current_reviewer = 'security' OR current_reviewer IS NULL) THEN 1 ELSE 0 END) AS waiting_final_check,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved
             FROM reports
             WHERE submitted_by = ?",
            '',
            [$userId],
        ) ?:
            [];
    }

    public function getSecurityRecentReportsForUser(string $userId, int $limit = 5): array
    {
        return db_fetch_all(
            "SELECT r.report_no, r.subject, r.severity, r.status, r.submitted_at, d.name AS department_name
             FROM reports r
             JOIN departments d ON d.id = r.responsible_department_id
             WHERE r.submitted_by = ?
             ORDER BY r.submitted_at DESC
             LIMIT " . (int) $limit,
            '',
            [$userId],
        );
    }

    public function getSecurityFinalCheckReportsForUser(string $userId, int $limit = 5): array
    {
        return db_fetch_all(
            "SELECT r.report_no, r.subject, r.severity, r.submitted_at, d.name AS department_name
             FROM reports r
             JOIN departments d ON d.id = r.responsible_department_id
             WHERE r.submitted_by = ?
               AND r.status = 'for_security_final_check'
             ORDER BY r.submitted_at DESC
             LIMIT " . (int) $limit,
            '',
            [$userId],
        );
    }

    public function getDepartmentStats(int $departmentId, ?string $buildingFilter): array
    {
        $sql = "SELECT
              SUM(CASE WHEN r.status IN ('sent_to_department','returned_to_department') THEN 1 ELSE 0 END) AS pending_assigned,
              SUM(CASE WHEN r.status = 'under_department_fix' AND da.action_type = 'timeline' THEN 1 ELSE 0 END) AS under_timeline,
              SUM(CASE WHEN r.status = 'for_security_final_check' AND da.action_type = 'done' THEN 1 ELSE 0 END) AS marked_done,
              SUM(CASE WHEN r.status = 'for_security_final_check' THEN 1 ELSE 0 END) AS waiting_final_check
            FROM reports r
            LEFT JOIN department_actions da ON da.report_id = r.id
            WHERE r.responsible_department_id = ?";
        $params = [$departmentId];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }

        return db_fetch_one($sql, '', $params) ?: [];
    }

    public function getDepartmentRecentAssignedReports(
        int $departmentId,
        ?string $buildingFilter,
        int $limit = 5,
    ): array {
        $sql = "SELECT r.report_no, r.subject, r.severity, r.status,
                    COALESCE(ass.assigned_at, r.submitted_at) AS assigned_at,
              da.action_type, da.timeline_due, r.fix_due_date
             FROM reports r
             LEFT JOIN department_actions da ON da.report_id = r.id
             LEFT JOIN (
                SELECT report_id, MAX(changed_at) AS assigned_at
                FROM report_status_history
                WHERE status = 'sent_to_department'
                GROUP BY report_id
             ) ass ON ass.report_id = r.id
             WHERE r.responsible_department_id = ?";
        $params = [$departmentId];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }
        $sql .=
            " ORDER BY COALESCE(ass.assigned_at, r.submitted_at) DESC
             LIMIT " . (int) $limit;

        return db_fetch_all($sql, '', $params);
    }

    public function getGaManagerPendingApprovalReports(?string $buildingFilter, int $limit = 5): array
    {
        $sql = "SELECT r.report_no, r.subject, r.severity, r.submitted_at, d.name AS department_name
                FROM reports r
                JOIN departments d ON d.id = r.responsible_department_id
                WHERE r.status = 'submitted_to_ga_manager' AND r.current_reviewer = 'ga_manager'";
        $params = [];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }
        $sql .= ' ORDER BY r.submitted_at DESC LIMIT ' . (int) $limit;
        return db_fetch_all($sql, '', $params);
    }

    public function getDepartmentNeedsActionReports(int $departmentId, ?string $buildingFilter, int $limit = 5): array
    {
        $sql = "SELECT r.report_no, r.subject, r.severity, r.status,
                       COALESCE(ass.assigned_at, r.submitted_at) AS assigned_at
                FROM reports r
                LEFT JOIN department_actions da ON da.report_id = r.id
                LEFT JOIN (
                    SELECT report_id, MAX(changed_at) AS assigned_at
                    FROM report_status_history
                    WHERE status = 'sent_to_department'
                    GROUP BY report_id
                ) ass ON ass.report_id = r.id
                WHERE r.responsible_department_id = ?
                  AND r.status IN ('sent_to_department', 'returned_to_department')
                  AND (da.id IS NULL OR da.action_type IS NULL)";
        $params = [$departmentId];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }
        $sql .= ' ORDER BY COALESCE(ass.assigned_at, r.submitted_at) DESC LIMIT ' . (int) $limit;
        return db_fetch_all($sql, '', $params);
    }
}
