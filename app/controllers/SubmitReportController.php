<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/SubmitReportService.php';
require_once __DIR__ . '/../models/SubmitReportModel.php';

class SubmitReportController extends BaseController
{
    private \SubmitReportService $service;
    private \SubmitReportModel $model;

    public function __construct(?\SubmitReportService $service = null, ?\SubmitReportModel $model = null)
    {
        $this->service = $service ?: new \SubmitReportService();
        $this->model = $model ?: new \SubmitReportModel();
    }

    public function index(): void
    {
        $pageTitle = 'Submit Security Report';
        $requiredRole = 'security';
        $currentPage = 'submit-report.php';

        require_once __DIR__ . '/../../includes/config.php';

        $flash = null;
        $flashType = 'success';
        $successReportNo = (string) ($_SESSION['submit_report_success_no'] ?? '');
        if ($successReportNo !== '') {
            unset($_SESSION['submit_report_success_no']);
        }

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'security') {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $uid = (string) ($currentUser['employee_no'] ?? '');

        if (isset($_GET['action']) && $_GET['action'] === 'get_children') {
            $parentId = (int) ($_GET['parent_id'] ?? 0);
            $targetType = (string) ($_GET['target_type'] ?? '');

            if ($parentId <= 0) {
                $this->json([]);
            }

            if ($targetType === 'category') {
                $this->json($this->model->getCategoriesBySubject($parentId));
            }
            if ($targetType === 'sub_category') {
                $this->json($this->model->getSubcategoriesByCategory($parentId));
            }

            $this->json([]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'quick_add') {
            if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
                $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 422);
            }

            $name = trim((string) ($_POST['name'] ?? ''));
            $type = (string) ($_POST['type'] ?? '');
            $parentId = (int) ($_POST['parent_id'] ?? 0);

            try {
                if ($type === 'subject') {
                    $newId = $this->model->addSubject($name);
                } elseif ($type === 'category') {
                    $newId = $this->model->addCategory($name, $parentId);
                } elseif ($type === 'sub_category') {
                    $newId = $this->model->addSubcategory($name, $parentId);
                } else {
                    $this->json(['success' => false, 'message' => 'Invalid type.'], 422);
                    return;
                }

                $this->json(['success' => true, 'id' => $newId, 'name' => $name]);
            } catch (\Throwable $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
        }

        $departmentsDb = fetch_departments();
        $initialSubjects = $this->model->getSubjects();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $publicDirFs = realpath(__DIR__ . '/../../public') ?: __DIR__ . '/../../public';
            $res = $this->service->handlePost($_POST, $_FILES, $uid, $publicDirFs);

            if (
                array_key_exists('successReportNo', $res) &&
                $res['successReportNo'] !== null &&
                (string) $res['successReportNo'] !== ''
            ) {
                $_SESSION['submit_report_success_no'] = (string) $res['successReportNo'];
                header('Location: ' . app_url('submit-report.php'));
                exit();
            }

            $flash = $res['flash'];
            $flashType = $res['flashType'];
            $successReportNo = $res['successReportNo'];
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/reports/submit_report.php';
    }
}
