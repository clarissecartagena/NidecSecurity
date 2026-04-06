<?php

require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardService
{
    private DashboardModel $model;

    public function __construct(?DashboardModel $model = null)
    {
        $this->model = $model ?: new DashboardModel();
    }

    public function getGaManagerDashboardData(?string $buildingFilter): array
    {
        $row = $this->model->getGaManagerStats($buildingFilter);
        $stats = [
            'pending_ga' => (int) ($row['pending_ga'] ?? 0),
            'in_progress' => (int) ($row['in_progress'] ?? 0),
            'critical' => (int) ($row['critical'] ?? 0),
            'overdue' => (int) ($row['overdue'] ?? 0),
        ];

        return [
            'stats' => $stats,
            'recent' => $this->model->getGaManagerRecentReports($buildingFilter, 5),
            'pending' => $this->model->getGaManagerPendingApprovalReports($buildingFilter, 5),
        ];
    }

    public function getGaStaffDashboardData(?string $buildingFilter): array
    {
        $row = $this->model->getGaStaffCounts($buildingFilter);
        $counts = [
            'waiting' => (int) ($row['waiting'] ?? 0),
            'forwarded' => (int) ($row['forwarded'] ?? 0),
            'returned' => (int) ($row['returned'] ?? 0),
        ];

        return [
            'counts' => $counts,
            'waiting' => $this->model->getGaStaffWaitingReports($buildingFilter, 5),
            'returned' => $this->model->getGaStaffReturnedReports($buildingFilter, 5),
        ];
    }

    public function getSecurityDashboardData(string $userId): array
    {
        $row = $this->model->getSecurityStatsForUser($userId);
        $stats = [
            'submitted_today' => (int) ($row['submitted_today'] ?? 0),
            'waiting_ga_review' => (int) ($row['waiting_ga_review'] ?? 0),
            'waiting_final_check' => (int) ($row['waiting_final_check'] ?? 0),
            'resolved' => (int) ($row['resolved'] ?? 0),
        ];

        return [
            'stats' => $stats,
            'recent' => $this->model->getSecurityRecentReportsForUser($userId, 5),
            'final_checks' => $this->model->getSecurityFinalCheckReportsForUser($userId, 5),
        ];
    }

    public function getDepartmentDashboardData(int $departmentId, ?string $buildingFilter): array
    {
        $row = $this->model->getDepartmentStats($departmentId, $buildingFilter);
        $stats = [
            'pending_assigned' => (int) ($row['pending_assigned'] ?? 0),
            'under_timeline' => (int) ($row['under_timeline'] ?? 0),
            'marked_done' => (int) ($row['marked_done'] ?? 0),
            'waiting_final_check' => (int) ($row['waiting_final_check'] ?? 0),
        ];

        return [
            'stats' => $stats,
            'recent' => $this->model->getDepartmentRecentAssignedReports($departmentId, $buildingFilter, 5),
            'needs_action' => $this->model->getDepartmentNeedsActionReports($departmentId, $buildingFilter, 5),
        ];
    }
}
