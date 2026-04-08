<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/report_share.php';

header('Content-Type: application/json; charset=utf-8');

if (!defined('SHARE_LINK_TTL_SECONDS')) {
    define('SHARE_LINK_TTL_SECONDS', 604800);
}

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user = getUser();
$role = (string) ($user['role'] ?? '');
$allowedRoles = ['ga_manager', 'ga_staff', 'security', 'department', 'pic'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$reportNo = trim((string) ($_GET['id'] ?? ''));
if ($reportNo === '' || !preg_match('/^[A-Za-z0-9\-_.]+$/', $reportNo) || strlen($reportNo) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid report id']);
    exit();
}

$whereExtra = '';
$params = [$reportNo];
if ($role === 'department' || $role === 'pic') {
    $deptId = (int) ($user['department_id'] ?? 0);
    if ($deptId <= 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Account has no assigned department']);
        exit();
    }
    $whereExtra = ' AND r.responsible_department_id = ?';
    $params[] = $deptId;
}

$row = db_fetch_one('SELECT id FROM reports r WHERE r.report_no = ?' . $whereExtra . ' LIMIT 1', '', $params);
if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Report not found']);
    exit();
}

try {
    $token = report_share_generate_token($reportNo, SHARE_LINK_TTL_SECONDS);
    $expiresAt = time() + SHARE_LINK_TTL_SECONDS;
    $path = app_url('view-report.php?share_token=' . urlencode($token));
    $url = $path;
    if (!preg_match('#^https?://#i', $path)) {
        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $url = $scheme . '://' . $host . $path;
    }

    echo json_encode(
        [
            'success' => true,
            'url' => $url,
            'expires_at' => $expiresAt,
        ],
        JSON_UNESCAPED_SLASHES,
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to generate share link']);
}
