<?php

require_once __DIR__ . '/../models/GaManagerApprovalModel.php';

class GaManagerApprovalService
{
    private GaManagerApprovalModel $model;

    public function __construct(?GaManagerApprovalModel $model = null)
    {
        $this->model = $model ?: new GaManagerApprovalModel();
    }

    public function handlePost(array $post, array $currentUser): array
    {
        $action = (string) ($post['action'] ?? '');
        $reportNo = trim((string) ($post['report_no'] ?? ''));
        $notes = trim((string) ($post['notes'] ?? ''));
        $token = (string) ($post['csrf_token'] ?? '');

        if (!csrf_validate($token)) {
            return ['flash' => 'Security check failed. Please refresh and try again.', 'flashType' => 'error'];
        }

        if (!in_array($action, ['approve', 'reject', 'return'], true) || $reportNo === '') {
            return ['flash' => 'Invalid request.', 'flashType' => 'error'];
        }

        if (($action === 'reject' || $action === 'return') && $notes === '') {
            $msg = $action === 'return' ? 'Please provide a return reason.' : 'Please provide a rejection reason.';
            return ['flash' => $msg, 'flashType' => 'error'];
        }

        $reportRow = $this->model->findReportByNo($reportNo);
        if (!$reportRow) {
            return ['flash' => 'Report not found.', 'flashType' => 'error'];
        }

        if (
            ($reportRow['status'] ?? '') !== 'submitted_to_ga_manager' ||
            ($reportRow['current_reviewer'] ?? '') !== 'ga_manager'
        ) {
            return ['flash' => 'This report is no longer pending GA Manager approval.', 'flashType' => 'error'];
        }

        $reportId = (int) ($reportRow['id'] ?? 0);
        $deptId = (int) ($reportRow['responsible_department_id'] ?? 0);
        $decidedBy = (string) ($currentUser['employee_no'] ?? '');

        $conn = db();
        $conn->beginTransaction();

        try {
            if ($action === 'approve') {
                $this->model->approve($reportId, $decidedBy, $notes);
                if ($deptId > 0) {
                    notify_role('department', $reportId, 'New Report Assigned to Your Department', $deptId);
                }
                notify_role(
                    'security',
                    $reportId,
                    'Report Approved by GA Manager. Assigned to Department for Resolution',
                );
                $conn->commit();
                return ['flash' => 'Report approved and sent to Department.', 'flashType' => 'success'];
            }

            if ($action === 'return') {
                $this->model->returnToSecurity($reportId, $decidedBy, $notes);
                notify_role('security', $reportId, 'Report Returned by GA Manager (Action Required)');
                $conn->commit();
                return ['flash' => 'Report returned to Security for revision.', 'flashType' => 'success'];
            }

            $this->model->reject($reportId, $decidedBy, $notes);
            notify_role('ga_staff', $reportId, 'Report Rejected by GA Manager');
            notify_role('security', $reportId, 'Report Rejected by GA Manager');
            $conn->commit();
            return ['flash' => 'Report rejected.', 'flashType' => 'success'];
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['flash' => 'Failed to update report. Please try again.', 'flashType' => 'error'];
        }
    }

    public function getPendingList(?string $buildingFilter): array
    {
        return $this->model->getPendingReports($buildingFilter);
    }
}
