<?php

require_once __DIR__ . '/../models/GaStaffReviewModel.php';

class GaStaffReviewService
{
    private GaStaffReviewModel $model;

    public function __construct(?GaStaffReviewModel $model = null)
    {
        $this->model = $model ?: new GaStaffReviewModel();
    }

    public function handlePost(array $post, array $currentUser): array
    {
        $token = (string) ($post['csrf_token'] ?? '');

        if (!csrf_validate($token)) {
            return ['flash' => 'Security check failed. Please refresh and try again.', 'flashType' => 'error'];
        }

        return ['flash' => 'GA Staff approval actions are disabled in the current workflow.', 'flashType' => 'error'];
    }

    public function getPendingList(?string $buildingFilter): array
    {
        return $this->model->getPendingReports($buildingFilter);
    }
}
