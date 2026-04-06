<?php

require_once __DIR__ . '/../models/FinalCheckingModel.php';

class FinalCheckingService
{
    private FinalCheckingModel $model;

    public function __construct(?FinalCheckingModel $model = null)
    {
        $this->model = $model ?: new FinalCheckingModel();
    }

    public function handlePost(array $post, string $userId): array
    {
        $token = (string) ($post['csrf_token'] ?? '');
        $action = trim((string) ($post['action'] ?? ''));
        $reportNo = trim((string) ($post['report_no'] ?? ''));
        $remarks = trim((string) ($post['final_remarks'] ?? ''));

        if (!csrf_validate($token)) {
            return ['flash' => 'Security check failed. Please refresh and try again.', 'flashType' => 'error'];
        }

        if (!in_array($action, ['confirm_resolved', 'not_resolved'], true) || $reportNo === '') {
            return ['flash' => 'Invalid request.', 'flashType' => 'error'];
        }

        if ($remarks === '') {
            return ['flash' => 'Remarks are required.', 'flashType' => 'error'];
        }

        $reportRow = $this->model->findReportForFinalChecking($reportNo);
        if (!$reportRow) {
            return ['flash' => 'Report not found.', 'flashType' => 'error'];
        }

        if ((string) ($reportRow['submitted_by'] ?? '') !== $userId) {
            http_response_code(403);
            die('Access denied.');
        }

        if (($reportRow['status'] ?? '') !== 'for_security_final_check') {
            return ['flash' => 'This report is not available for final checking.', 'flashType' => 'error'];
        }

        $reportId = (int) ($reportRow['id'] ?? 0);
        $deptId = (int) ($reportRow['responsible_department_id'] ?? 0);

        $conn = db();
        $conn->beginTransaction();

        try {
            if ($action === 'confirm_resolved') {
                $this->model->confirmResolved($reportId, $userId, $remarks);

                if ($deptId > 0) {
                    notify_role('department', $reportId, 'Report Fully Resolved', $deptId);
                }
                notify_role('ga_staff', $reportId, 'Report Fully Resolved');
                notify_role('ga_manager', $reportId, 'Report Fully Resolved');

                $conn->commit();
                return ['flash' => 'Report marked as resolved.', 'flashType' => 'success'];
            }

            $this->model->markNotResolved($reportId, $userId, $remarks);

            // After the update, reopen_count in DB is now (old + 1).
            // We use the pre-increment value from the fetched row.
            $newReopenCount = (int) ($reportRow['reopen_count'] ?? 0) + 1;

            if ($deptId > 0) {
                notify_role(
                    'department',
                    $reportId,
                    'Report Returned. Issue Not Resolved (Return #' . $newReopenCount . ')',
                    $deptId,
                );
            }

            // Standard GA visibility notification
            notify_role('ga_staff', $reportId, 'Report Not Resolved (Returned to Department)');
            notify_role('ga_manager', $reportId, 'Report Not Resolved (Returned to Department)');

            // --- Loop escalation gates ---
            // After 2nd failed fix: alert GA Staff they need to intervene
            if ($newReopenCount >= 2) {
                notify_role(
                    'ga_staff',
                    $reportId,
                    '[ESCALATION] Report returned to Department ' .
                        $newReopenCount .
                        ' times. GA Staff review required.',
                );
            }

            // After 3rd failed fix: escalate directly to GA Manager for override
            if ($newReopenCount >= 3) {
                notify_role(
                    'ga_manager',
                    $reportId,
                    '[ESCALATION] Report returned to Department ' .
                        $newReopenCount .
                        ' times. GA Manager override required.',
                );
            }

            $conn->commit();
            return ['flash' => 'Report returned to Department for further action.', 'flashType' => 'success'];
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['flash' => 'Failed to update report. Please try again.', 'flashType' => 'error'];
        }
    }

    public function getReportsForUser(string $userId): array
    {
        return $this->model->getReportsAwaitingFinalCheckingForUser($userId);
    }
}
