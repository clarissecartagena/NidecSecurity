<?php

require_once __DIR__ . '/../models/SecurityReportsModel.php';

class SecurityReportsService
{
    private SecurityReportsModel $model;

    public function __construct(?SecurityReportsModel $model = null)
    {
        $this->model = $model ?: new SecurityReportsModel();
    }

    public function getReportsForUser(string $userId): array
    {
        return $this->model->getReportsBySubmitter($userId);
    }
}
