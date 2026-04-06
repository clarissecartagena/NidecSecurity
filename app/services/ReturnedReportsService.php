<?php

require_once __DIR__ . '/../models/ReturnedReportsModel.php';

class ReturnedReportsService
{
    private ReturnedReportsModel $model;

    public function __construct(?ReturnedReportsModel $model = null)
    {
        $this->model = $model ?: new ReturnedReportsModel();
    }

    public function handlePost(array $post, string $userId): array
    {
        $action = (string) ($post['action'] ?? '');
        $reportNo = trim((string) ($post['report_no'] ?? ''));
        $token = (string) ($post['csrf_token'] ?? '');

        if (!csrf_validate($token)) {
            return ['flash' => 'Security check failed. Please refresh and try again.', 'flashType' => 'error'];
        }

        if ($action !== 'resubmit' || $reportNo === '') {
            return ['flash' => 'Invalid request.', 'flashType' => 'error'];
        }

        $subject = trim((string) ($post['subject'] ?? ''));
        $category = trim((string) ($post['category'] ?? ''));
        $location = trim((string) ($post['location'] ?? ''));
        $severity = trim((string) ($post['severity'] ?? ''));
        $deptId = (int) ($post['responsible_department_id'] ?? 0);
        $details = trim((string) ($post['details'] ?? ''));
        $actionsTaken = trim((string) ($post['actions_taken'] ?? ''));
        $remarks = trim((string) ($post['remarks'] ?? ''));
        $notes = trim((string) ($post['notes'] ?? ''));

        if ($subject === '' || $category === '' || $location === '' || $details === '' || $deptId <= 0) {
            return ['flash' => 'Please complete all required fields.', 'flashType' => 'error'];
        }

        $reportRow = $this->model->findReportForResubmit($reportNo);
        if (!$reportRow) {
            return ['flash' => 'Report not found.', 'flashType' => 'error'];
        }

        if (
            (string) ($reportRow['status'] ?? '') !== 'submitted_to_ga_manager' ||
            (string) ($reportRow['current_reviewer'] ?? '') !== 'ga_staff'
        ) {
            return ['flash' => 'This report is not currently returned by GA Manager.', 'flashType' => 'error'];
        }

        $reportId = (int) $reportRow['id'];
        $conn = db();
        $conn->beginTransaction();

        try {
            $this->model->resubmitReport(
                $reportId,
                $subject,
                $category,
                $location,
                $severity,
                $deptId,
                $details,
                $actionsTaken,
                $remarks,
            );

            $this->model->upsertGaStaffReview($reportId, $userId, $notes);

            $this->model->insertStatusHistory($reportId, $userId, $notes === '' ? 'Resubmitted to GA Manager' : $notes);

            notify_role('ga_manager', $reportId, 'Report Waiting for Final GA Approval');

            $conn->commit();
            return ['flash' => 'Report updated and resubmitted to GA Manager.', 'flashType' => 'success'];
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['flash' => 'Failed to resubmit report. Please try again.', 'flashType' => 'error'];
        }
    }

    public function getReturned(?string $buildingFilter): array
    {
        return $this->model->getReturnedReports($buildingFilter);
    }
}
