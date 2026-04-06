<?php

require_once __DIR__ . '/../models/ReportsModel.php';

class ReportsService
{
    private ReportsModel $model;

    public function __construct(?ReportsModel $model = null)
    {
        $this->model = $model ?: new ReportsModel();
    }

    public function getReportsListData(?string $buildingFilter, int $page, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $totalReports = $this->model->countAllReports($buildingFilter);
        $totalPages = (int) ceil($totalReports / $limit);

        return [
            'reports' => $this->model->getReportsPage($buildingFilter, $limit, $offset),
            'totalReports' => $totalReports,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
        ];
    }
}
