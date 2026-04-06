<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../config/api.php';

header('Content-Type: application/json; charset=utf-8');

function sync_api_read_key_from_request(): string
{
    $headerKey = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
    if ($headerKey !== '') {
        return $headerKey;
    }

    $authHeader = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    if ($authHeader !== '' && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
        return trim((string) ($m[1] ?? ''));
    }

    return trim((string) ($_GET['api_key'] ?? ''));
}

function sync_api_fail(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    echo json_encode(
        [
            'success' => false,
            'error' => $message,
        ],
        JSON_UNESCAPED_SLASHES,
    );
    exit();
}

function sync_api_absolute_url(string $relativePath): string
{
    if (preg_match('#^https?://#i', $relativePath)) {
        return $relativePath;
    }

    $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if ($relativePath === '' || $relativePath[0] !== '/') {
        $relativePath = '/' . ltrim($relativePath, '/');
    }
    return $scheme . '://' . $host . $relativePath;
}

$expectedKey = (string) (defined('REPORTS_SYNC_API_KEY') ? REPORTS_SYNC_API_KEY : '');
$providedKey = sync_api_read_key_from_request();
if ($expectedKey === '' || $providedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    sync_api_fail(401, 'Unauthorized');
}

$entity = normalize_building($_GET['entity'] ?? null);
$whereSql = '';
$params = [];
if ($entity) {
    $whereSql = ' WHERE r.building = ?';
    $params[] = $entity;
}

$rows = db_fetch_all(
    "SELECT
        r.id,
        r.report_no,
        r.subject,
        r.category,
        r.location,
        r.severity,
        r.building,
        r.security_type,
        r.responsible_department_id,
        d.name AS department_name,
        r.details,
        r.actions_taken,
        r.remarks,
        r.security_remarks,
        r.status,
        r.current_reviewer,
        r.fix_due_date,
        r.submitted_at,
        r.updated_at,
        r.resolved_at,
        r.returned_at,
        u.employee_no AS submitted_by,
        u.name AS submitted_by_name,
        u.security_type AS submitted_by_security_type
     FROM reports r
     JOIN departments d ON d.id = r.responsible_department_id
LEFT JOIN users u ON u.employee_no = r.submitted_by
     $whereSql
     ORDER BY r.id ASC",
    '',
    $params,
);

$reports = [];
$checksumParts = [];

foreach ($rows as $row) {
    $reportNo = (string) ($row['report_no'] ?? '');
    // Use the report's own security_type; fall back to the submitter's for older records
    $securityTypeRaw = strtolower(trim((string) ($row['security_type'] ?? ($row['submitted_by_security_type'] ?? ''))));
    $securityType = in_array($securityTypeRaw, ['internal', 'external'], true) ? $securityTypeRaw : 'external';

    $pdfInternalUrl = sync_api_absolute_url(
        app_url('api/report_pdf_internal.php?id=' . rawurlencode($reportNo) . '&api_key=' . rawurlencode($expectedKey)),
    );
    $pdfExternalUrl = sync_api_absolute_url(
        app_url('api/report_pdf_external.php?id=' . rawurlencode($reportNo) . '&api_key=' . rawurlencode($expectedKey)),
    );

    $reports[] = [
        'id' => (int) ($row['id'] ?? 0),
        'report_no' => $reportNo,
        'subject' => (string) ($row['subject'] ?? ''),
        'category' => (string) ($row['category'] ?? ''),
        'location' => (string) ($row['location'] ?? ''),
        'severity' => (string) ($row['severity'] ?? ''),
        'entity' => (string) ($row['building'] ?? ''),
        'department_id' => (int) ($row['responsible_department_id'] ?? 0),
        'department_name' => (string) ($row['department_name'] ?? ''),
        'details' => (string) ($row['details'] ?? ''),
        'actions_taken' => (string) ($row['actions_taken'] ?? ''),
        'remarks' => (string) ($row['remarks'] ?? ''),
        'security_remarks' => (string) ($row['security_remarks'] ?? ''),
        'status' => (string) ($row['status'] ?? ''),
        'current_reviewer' => (string) ($row['current_reviewer'] ?? ''),
        'fix_due_date' => $row['fix_due_date'],
        'submitted_at' => $row['submitted_at'],
        'updated_at' => $row['updated_at'],
        'resolved_at' => $row['resolved_at'],
        'returned_at' => $row['returned_at'],
        'submitted_by' => (string) ($row['submitted_by'] ?? ''),
        'submitted_by_name' => (string) ($row['submitted_by_name'] ?? ''),
        'security_type' => $securityType,
        'pdf_template' => $securityType,
        'pdf_url' => $securityType === 'external' ? $pdfExternalUrl : $pdfInternalUrl,
        'pdf_internal_url' => $pdfInternalUrl,
        'pdf_external_url' => $pdfExternalUrl,
    ];

    $checksumParts[] = $reportNo . '|' . (string) ($row['updated_at'] ?? '') . '|' . (string) ($row['status'] ?? '');
}

$generatedAt = gmdate('c');
$snapshotChecksum = sha1(implode("\n", $checksumParts));

http_response_code(200);
echo json_encode(
    [
        'success' => true,
        'sync_mode' => 'snapshot_full_replace',
        'generated_at' => $generatedAt,
        'entity_filter' => $entity ?: 'all',
        'total_reports' => count($reports),
        'snapshot_checksum' => $snapshotChecksum,
        'reports' => $reports,
    ],
    JSON_UNESCAPED_SLASHES,
);
