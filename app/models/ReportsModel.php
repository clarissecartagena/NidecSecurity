<?php

class ReportsModel
{
    public function countAllReports(?string $buildingFilter): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM reports r';
        $params = [];
        if ($buildingFilter) {
            $sql .= ' WHERE r.building = ?';
            $params[] = $buildingFilter;
        }

        $row = db_fetch_one($sql, '', $params) ?: [];
        return (int) ($row['total'] ?? 0);
    }

    public function getReportsPage(?string $buildingFilter, int $limit, int $offset): array
    {
        $sql = "SELECT r.report_no, r.subject, r.category, r.building, r.severity, r.status, r.submitted_at, d.name AS department_name
                FROM reports r
                JOIN departments d ON d.id = r.responsible_department_id";
        $params = [];
        if ($buildingFilter) {
            $sql .= ' WHERE r.building = ?';
            $params[] = $buildingFilter;
        }

        $sql .=
            " ORDER BY r.submitted_at DESC
                  LIMIT " .
            (int) $limit .
            ' OFFSET ' .
            (int) $offset;

        return db_fetch_all($sql, '', $params);
    }
}
