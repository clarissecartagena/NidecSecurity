<?php

require_once __DIR__ . '/../models/DepartmentHistoryModel.php';

class DepartmentHistoryService
{
    private DepartmentHistoryModel $model;

    public function __construct(?DepartmentHistoryModel $model = null)
    {
        $this->model = $model ?: new DepartmentHistoryModel();
    }

    public function getRows(int $departmentId, ?string $buildingFilter): array
    {
        return $this->model->getDepartmentReportHistory($departmentId, $buildingFilter);
    }
}
