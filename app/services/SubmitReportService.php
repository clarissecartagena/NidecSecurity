<?php

require_once __DIR__ . '/../models/SubmitReportModel.php';

class SubmitReportService
{
    private SubmitReportModel $model;

    public function __construct(?SubmitReportModel $model = null)
    {
        $this->model = $model ?: new SubmitReportModel();
    }

    public function handlePost(array $post, array $files, string $userId, string $publicDirFs): array
    {
        $token = (string) ($post['csrf_token'] ?? '');

        $subjectId = (int) ($post['subject_id'] ?? 0);
        $categoryId = (int) ($post['category_id'] ?? 0);
        $subCategoryId = (int) ($post['sub_category_id'] ?? 0);
        $location = trim((string) ($post['location'] ?? ''));
        $severity = trim((string) ($post['severity'] ?? ''));
        $departmentId = (int) ($post['department_id'] ?? 0);
        $details = trim((string) ($post['details'] ?? ''));
        $actionsTaken = trim((string) ($post['actions_taken'] ?? ''));
        $remarks = trim((string) ($post['remarks'] ?? ''));
        $assessment = trim((string) ($post['assessment'] ?? ''));
        $recommendations = trim((string) ($post['recommendations'] ?? ''));
        $securityType = trim((string) ($post['security_type'] ?? ''));
        $building = normalize_building($post['building'] ?? null);

        $allowedSev = ['low', 'medium', 'high', 'critical'];

        if (!csrf_validate($token)) {
            return [
                'flash' => 'Security check failed. Please refresh and try again.',
                'flashType' => 'error',
                'successReportNo' => null,
            ];
        }

        if (
            $subjectId <= 0 ||
            $categoryId <= 0 ||
            $subCategoryId <= 0 ||
            $location === '' ||
            $details === '' ||
            $assessment === '' ||
            $recommendations === '' ||
            $departmentId <= 0
        ) {
            return [
                'flash' => 'Please fill in all required fields.',
                'flashType' => 'error',
                'successReportNo' => null,
            ];
        }

        $classification = $this->model->getClassificationNames($subjectId, $categoryId, $subCategoryId);
        if (!$classification) {
            return [
                'flash' => 'Invalid subject/category/subcategory relationship.',
                'flashType' => 'error',
                'successReportNo' => null,
            ];
        }

        if (!in_array($severity, $allowedSev, true)) {
            return ['flash' => 'Invalid severity level.', 'flashType' => 'error', 'successReportNo' => null];
        }
        if (!in_array($securityType, ['internal', 'external'], true)) {
            return [
                'flash' => 'Please select a valid report type (Internal or External).',
                'flashType' => 'error',
                'successReportNo' => null,
            ];
        }
        if (!$building) {
            return [
                'flash' => 'Please select a valid entity (NCFL or NPFL).',
                'flashType' => 'error',
                'successReportNo' => null,
            ];
        }

        if (!$this->model->isActiveDepartment($departmentId)) {
            return ['flash' => 'Invalid department selected.', 'flashType' => 'error', 'successReportNo' => null];
        }

        $conn = db();
        $conn->beginTransaction();
        $movedPaths = [];

        try {
            $reportNo = null;
            $reportId = null;

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $reportNo = $this->model->generateSecurityReportNo();
                try {
                    $reportId = $this->model->insertReport([
                        'report_no' => $reportNo,
                        'subject_id' => $subjectId,
                        'category_id' => $categoryId,
                        'sub_category_id' => $subCategoryId,
                        'subject_name' => (string) $classification['subject_name'],
                        'category_name' => (string) $classification['category_name'],
                        'location' => $location,
                        'severity' => $severity,
                        'building' => $building,
                        'security_type' => $securityType,
                        'department_id' => $departmentId,
                        'details' => $details,
                        'actions_taken' => $actionsTaken === '' ? null : $actionsTaken,
                        'remarks' => $remarks === '' ? null : $remarks,
                        'assessment' => $assessment === '' ? null : $assessment,
                        'recommendations' => $recommendations === '' ? null : $recommendations,
                        'submitted_by' => $userId,
                    ]);
                    break;
                } catch (Throwable $e) {
                    $msg = strtolower($e->getMessage());
                    if (strpos($msg, 'duplicate') !== false || strpos($msg, 'uq_reports_report_no') !== false) {
                        continue;
                    }
                    throw $e;
                }
            }

            if (!$reportId) {
                throw new RuntimeException('Unable to generate a new report number.');
            }

            $this->model->insertStatusHistory($reportId, 'submitted_to_ga_manager', $userId, 'Submitted by Security');

            notify_role('ga_manager', $reportId, 'New Report Submitted and Waiting for Final GA Approval');

            $this->handleEvidenceUploads($files, $publicDirFs, $reportId, $reportNo, $userId, $movedPaths);

            $conn->commit();

            return ['flash' => null, 'flashType' => 'success', 'successReportNo' => $reportNo];
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            foreach ($movedPaths as $p) {
                if (is_file($p)) {
                    @unlink($p);
                }
            }

            $msg = $e instanceof RuntimeException ? $e->getMessage() : 'Failed to submit report. Please try again.';
            return ['flash' => $msg, 'flashType' => 'error', 'successReportNo' => null];
        }
    }

    private function handleEvidenceUploads(
        array $files,
        string $publicDirFs,
        int $reportId,
        string $reportNo,
        string $userId,
        array &$movedPaths,
    ): void {
        if (empty($files['evidence']) || !is_array($files['evidence']['name'] ?? null)) {
            return;
        }

        $names = $files['evidence']['name'];
        $tmpNames = $files['evidence']['tmp_name'];
        $errors = $files['evidence']['error'];
        $sizes = $files['evidence']['size'];

        $uploadDirFs =
            rtrim($publicDirFs, '\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'report_evidence';
        if (!is_dir($uploadDirFs)) {
            @mkdir($uploadDirFs, 0755, true);
        }
        if (!is_dir($uploadDirFs) || !is_writable($uploadDirFs)) {
            throw new RuntimeException(
                'Uploads folder is not writable. Please ask an admin to grant write permissions to public/uploads/report_evidence.',
            );
        }

        $finfo = null;
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
        }

        $firstPath = null;

        for ($i = 0; $i < count($names); $i++) {
            if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $err = (int) ($errors[$i] ?? UPLOAD_ERR_OK);
            if ($err !== UPLOAD_ERR_OK) {
                $msg = 'Upload failed for one of the files.';
                if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                    $msg =
                        'One of the files is too large. Please upload an image that fits within the server upload limit.';
                } elseif ($err === UPLOAD_ERR_PARTIAL) {
                    $msg = 'One of the files was only partially uploaded. Please try again.';
                } elseif ($err === UPLOAD_ERR_NO_TMP_DIR) {
                    $msg = 'Server is missing a temporary upload folder (PHP configuration).';
                } elseif ($err === UPLOAD_ERR_CANT_WRITE) {
                    $msg = 'Server failed to write the uploaded file to disk.';
                } elseif ($err === UPLOAD_ERR_EXTENSION) {
                    $msg = 'Upload was stopped by a PHP extension.';
                }
                throw new RuntimeException($msg);
            }

            $size = (int) ($sizes[$i] ?? 0);
            if ($size <= 0 || $size > 10 * 1024 * 1024) {
                throw new RuntimeException('Each file must be 10MB or less.');
            }

            $tmp = (string) ($tmpNames[$i] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                throw new RuntimeException('Invalid uploaded file.');
            }

            $mime = '';
            if ($finfo) {
                $mime = $finfo->file($tmp) ?: '';
            } elseif (function_exists('mime_content_type')) {
                $mime = mime_content_type($tmp) ?: '';
            }

            $ext = '';
            if ($mime === 'image/jpeg') {
                $ext = 'jpg';
            } elseif ($mime === 'image/png') {
                $ext = 'png';
            } else {
                throw new RuntimeException('Only JPG and PNG images are allowed.');
            }

            $original = (string) ($names[$i] ?? 'evidence.' . $ext);
            $safeOriginal = preg_replace('/[^A-Za-z0-9._-]+/', '_', $original);
            if ($safeOriginal === '' || $safeOriginal === null) {
                $safeOriginal = 'evidence.' . $ext;
            }

            $storedName = $reportNo . '_' . date('YmdHis') . '_' . ($i + 1) . '.' . $ext;
            $destFs = $uploadDirFs . DIRECTORY_SEPARATOR . $storedName;
            $destRel = 'uploads/report_evidence/' . $storedName;

            if (!move_uploaded_file($tmp, $destFs)) {
                throw new RuntimeException('Failed to save uploaded file.');
            }

            $movedPaths[] = $destFs;
            if ($firstPath === null) {
                $firstPath = $destRel;
            }

            $this->model->insertAttachment($reportId, $safeOriginal, $destRel, $mime, $size, $userId);
        }

        if ($firstPath !== null) {
            $this->model->updateEvidenceImagePath($reportId, $firstPath);
        }
    }
}
