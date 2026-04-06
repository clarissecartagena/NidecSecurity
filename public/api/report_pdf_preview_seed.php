<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user = getUser();
$role = (string) ($user['role'] ?? '');
if ($role !== 'security') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

if (!csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

$securityType = strtolower(trim((string) ($_POST['security_type'] ?? 'internal')));
if ($securityType !== 'external') {
    $securityType = 'internal';
}

$building = strtoupper(trim((string) ($_POST['building'] ?? 'NCFL')));
if ($building !== 'NPFL') {
    $building = 'NCFL';
}

$subjectText = trim((string) ($_POST['subject_text'] ?? 'REPORT'));
$categoryText = trim((string) ($_POST['category_text'] ?? 'REPORT'));
$subCategoryText = trim((string) ($_POST['sub_category_text'] ?? 'Sub-Category'));
$departmentText = trim((string) ($_POST['department_text'] ?? 'DEPARTMENT'));

$details = trim((string) ($_POST['details'] ?? ''));
$assessment = trim((string) ($_POST['assessment'] ?? ''));
$recommendations = trim((string) ($_POST['recommendations'] ?? ''));
$actionsTaken = trim((string) ($_POST['actions_taken'] ?? ''));
$remarks = trim((string) ($_POST['remarks'] ?? ''));
$securityRemarks = trim((string) ($_POST['security_remarks'] ?? ''));

$officerName = trim((string) ($user['name'] ?? ''));
$officerSignature = trim((string) ($user['signature_path'] ?? ''));

if ($officerName === '' || $officerSignature === '') {
    $employeeNo = trim((string) ($user['employee_no'] ?? ''));
    if ($employeeNo !== '') {
        $row = db_fetch_one('SELECT name, signature_path FROM users WHERE employee_no = ? LIMIT 1', 's', [$employeeNo]);
        if ($officerName === '') {
            $officerName = trim((string) ($row['name'] ?? ''));
        }
        if ($officerSignature === '') {
            $officerSignature = trim((string) ($row['signature_path'] ?? ''));
        }
    }
}

if ($officerName === '') {
    $officerName = 'OFFICER NAME';
}

$report = [
    'id' => 0,
    'report_no' => 'PREVIEW-' . date('YmdHis'),
    'subject' => $subjectText !== '' ? $subjectText : 'REPORT',
    'category' => $categoryText !== '' ? $categoryText : 'REPORT',
    'sub_category_name' => $subCategoryText !== '' ? $subCategoryText : 'Sub-Category',
    'location' => trim((string) ($_POST['location'] ?? '')),
    'severity' => trim((string) ($_POST['severity'] ?? 'medium')),
    'building' => $building,
    'security_type' => $securityType,
    'status' => 'draft-preview',
    'submitted_at' => date('Y-m-d H:i:s'),
    'details' => $details,
    'assessment' => $assessment,
    'recommendations' => $recommendations,
    'actions_taken' => $actionsTaken,
    'remarks' => $remarks,
    'security_remarks' => $securityRemarks,
    'department_name' => $departmentText !== '' ? $departmentText : 'DEPARTMENT',
    'submitted_by_name' => $officerName,
    'submitted_by_signature' => $officerSignature,
    'decided_at' => null,
    'ga_manager_decision' => null,
    'ga_manager_notes' => null,
    'ga_manager_name' => '',
    'ga_manager_signature' => '',
    'ga_manager_job_level' => '',
    'action_type' => null,
    'timeline_days' => null,
    'timeline_start' => null,
    'timeline_due' => null,
    'dept_remarks' => null,
    'dept_acted_at' => null,
    'dept_acted_by' => '',
    'dept_signature' => '',
    'final_checked_at' => null,
    'final_decision' => null,
    'final_remarks' => null,
    'final_checked_by' => null,
    'closed_at' => null,
    'fix_due_date' => null,
];

if (!isset($_SESSION['submit_report_pdf_preview']) || !is_array($_SESSION['submit_report_pdf_preview'])) {
    $_SESSION['submit_report_pdf_preview'] = [];
}

$cleanupPreviewFiles = static function (array $entry): void {
    $files = $entry['temp_files'] ?? [];
    if (!is_array($files)) {
        return;
    }
    foreach ($files as $fsPath) {
        $fsPath = (string) $fsPath;
        if ($fsPath !== '' && is_file($fsPath)) {
            @unlink($fsPath);
        }
    }
};

$token = bin2hex(random_bytes(16));

$now = time();
foreach ($_SESSION['submit_report_pdf_preview'] as $k => $entry) {
    $entryTs = (int) ($entry['created_at'] ?? 0);
    if ($entryTs <= 0 || $now - $entryTs > 1200) {
        if (is_array($entry)) {
            $cleanupPreviewFiles($entry);
        }
        unset($_SESSION['submit_report_pdf_preview'][$k]);
    }
}

$previewEvidence = [];
$previewTempFiles = [];
if (!empty($_FILES['evidence']) && is_array($_FILES['evidence']['name'] ?? null)) {
    $uploadRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
    $previewUploadDirFs =
        rtrim($uploadRoot, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'report_preview';

    if (!is_dir($previewUploadDirFs)) {
        @mkdir($previewUploadDirFs, 0755, true);
    }

    if (!is_dir($previewUploadDirFs) || !is_writable($previewUploadDirFs)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Preview uploads folder is not writable.']);
        exit();
    }

    $names = $_FILES['evidence']['name'];
    $tmpNames = $_FILES['evidence']['tmp_name'];
    $errors = $_FILES['evidence']['error'];
    $sizes = $_FILES['evidence']['size'];

    $finfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;
    $evidenceId = 1;

    for ($i = 0; $i < count($names); $i++) {
        $err = (int) ($errors[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($err !== UPLOAD_ERR_OK) {
            continue;
        }

        $size = (int) ($sizes[$i] ?? 0);
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            continue;
        }

        $tmp = (string) ($tmpNames[$i] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            continue;
        }

        $mime = '';
        if ($finfo instanceof finfo) {
            $mime = (string) ($finfo->file($tmp) ?: '');
        } elseif (function_exists('mime_content_type')) {
            $mime = (string) (mime_content_type($tmp) ?: '');
        }

        $ext = '';
        if ($mime === 'image/jpeg') {
            $ext = 'jpg';
        } elseif ($mime === 'image/png') {
            $ext = 'png';
        } else {
            continue;
        }

        $storedName = 'preview_' . $token . '_' . ($i + 1) . '_' . date('YmdHis') . '.' . $ext;
        $destFs = $previewUploadDirFs . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($tmp, $destFs)) {
            continue;
        }

        $destRel = 'uploads/report_preview/' . $storedName;
        $previewTempFiles[] = $destFs;
        $previewEvidence[] = [
            'id' => $evidenceId++,
            'file_path' => $destRel,
        ];
    }
}

if (isset($_SESSION['submit_report_pdf_preview'][$token]) && is_array($_SESSION['submit_report_pdf_preview'][$token])) {
    $cleanupPreviewFiles($_SESSION['submit_report_pdf_preview'][$token]);
}
$_SESSION['submit_report_pdf_preview'][$token] = [
    'created_at' => $now,
    'created_by' => (string) ($user['employee_no'] ?? ''),
    'report' => $report,
    'evidence' => $previewEvidence,
    'temp_files' => $previewTempFiles,
];

echo json_encode(['success' => true, 'token' => $token]);
