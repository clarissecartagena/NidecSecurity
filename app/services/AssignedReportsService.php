<?php

require_once __DIR__ . '/../models/AssignedReportsModel.php';

class AssignedReportsService
{
    private AssignedReportsModel $model;

    public function __construct(?AssignedReportsModel $model = null)
    {
        $this->model = $model ?: new AssignedReportsModel();
    }

    public function handlePost(array $post, string $userId, int $departmentId): array
    {
        $token = (string) ($post['csrf_token'] ?? '');
        $action = trim((string) ($post['action'] ?? ''));
        $reportNo = trim((string) ($post['report_no'] ?? ''));

        if (!csrf_validate($token)) {
            return ['flash' => 'Security check failed. Please refresh and try again.', 'flashType' => 'error'];
        }

        if (!in_array($action, ['set_timeline', 'mark_done'], true) || $reportNo === '') {
            return ['flash' => 'Invalid request.', 'flashType' => 'error'];
        }

        $report = $this->model->findReportForDepartment($reportNo, $departmentId);
        if (!$report) {
            return ['flash' => 'Report not found or not assigned to your department.', 'flashType' => 'error'];
        }

        $reportId = (int) ($report['id'] ?? 0);
        $currentStatus = (string) ($report['status'] ?? '');

        $conn = db();
        $conn->beginTransaction();

        try {
            if ($action === 'set_timeline') {
                $days = (int) ($post['timeline_days'] ?? 0);
                if ($days <= 0 || $days > 365) {
                    throw new RuntimeException('Please enter a valid number of days (1-365).');
                }

                // Allow timeline from initial assignment or after Security return
                if (
                    !in_array(
                        $currentStatus,
                        ['sent_to_department', 'returned_to_department', 'under_department_fix'],
                        true,
                    )
                ) {
                    throw new RuntimeException('This report cannot be updated right now.');
                }

                $due = date('Y-m-d H:i:s', time() + $days * 86400);

                $this->model->setFixTimeline($reportId, $userId, $days, $due);

                // Dedup window of 3 600 s (1 hour) prevents spam when Department
                // adjusts the timeline multiple times in quick succession.
                notify_role(
                    'security',
                    $reportId,
                    'Department Set Fix Timeline. Due: ' . date('M d, Y', strtotime($due)),
                    null,
                    3600,
                );

                $conn->commit();
                return ['flash' => 'Fix timeline set successfully.', 'flashType' => 'success'];
            }

            // Mark DONE anytime
            if (
                !in_array(
                    $currentStatus,
                    ['sent_to_department', 'returned_to_department', 'under_department_fix'],
                    true,
                )
            ) {
                throw new RuntimeException('This report cannot be marked as done right now.');
            }

            $this->model->markDone($reportId, $userId);

            notify_role('security', $reportId, 'Department Marked Report as Fixed. Please Verify');

            $conn->commit();
            return [
                'flash' => 'Report marked as DONE and sent to Security for final checking.',
                'flashType' => 'success',
            ];
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $msg = $e instanceof RuntimeException ? $e->getMessage() : 'Failed to update report. Please try again.';
            return ['flash' => $msg, 'flashType' => 'error'];
        }
    }

    public function getAssigned(int $departmentId, ?string $buildingFilter): array
    {
        return $this->model->getAssignedReports($departmentId, $buildingFilter);
    }
}
