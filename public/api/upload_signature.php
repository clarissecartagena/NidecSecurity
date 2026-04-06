<?php
/**
 * API: Upload user signature (one-time)
 *
 * POST  multipart/form-data
 *   signature   – image file (PNG/JPG/GIF/WebP, max 2 MB)
 *   csrf_token  – CSRF token
 *
 * Response: JSON { success: true } or { error: "message" }
 */
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// CSRF check
$token = (string) ($_POST['csrf_token'] ?? '');
if (!csrf_validate($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Security check failed. Please refresh and try again.']);
    exit();
}

$currentUser = getUser();
$employeeNo = (string) ($currentUser['employee_no'] ?? '');

// Fallback: stale sessions (created before schema migration) may have
// employee_no = '' — look up by username and refresh the session.
if ($employeeNo === '' && !empty($currentUser['username'])) {
    $sessionUser = db_fetch_one('SELECT employee_no FROM users WHERE username = ? LIMIT 1', 's', [
        $currentUser['username'],
    ]);
    if ($sessionUser) {
        $employeeNo = $sessionUser['employee_no'];
        $_SESSION['user']['employee_no'] = $employeeNo;
    }
}

if ($employeeNo === '') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if a signature already exists (one-time only)
$existing = db_fetch_one('SELECT signature_path FROM users WHERE employee_no = ? LIMIT 1', 's', [$employeeNo]);
if ($existing && !empty($existing['signature_path'])) {
    http_response_code(409);
    echo json_encode(['error' => 'A signature has already been uploaded for this account. It cannot be changed.']);
    exit();
}

// Validate uploaded file
if (empty($_FILES['signature']) || $_FILES['signature']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['signature']['error'] ?? -1;
    $errMsg = match ((int) $errCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
        UPLOAD_ERR_NO_FILE => 'No file uploaded.',
        default => 'Upload error (code ' . $errCode . ').',
    };
    http_response_code(400);
    echo json_encode(['error' => $errMsg]);
    exit();
}

$file = $_FILES['signature'];
$maxBytes = 10 * 1024 * 1024; // 10 MB
if ($file['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['error' => 'File exceeds maximum size of 10 MB.']);
    exit();
}

// Validate MIME type via getimagesize (not relying on client-provided type)
$tmpPath = $file['tmp_name'];
$imgInfo = @getimagesize($tmpPath);
if (!$imgInfo) {
    http_response_code(400);
    echo json_encode(['error' => 'Uploaded file is not a valid image.']);
    exit();
}

$allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
$mime = $imgInfo['mime'] ?? '';
if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Only PNG, JPG, GIF, and WebP images are accepted.']);
    exit();
}

// Build destination path
$ext = match ($mime) {
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    default => 'png',
};
$fileName = 'sig_' . preg_replace('/[^a-z0-9]/', '', strtolower($employeeNo)) . '_' . time() . '.' . $ext;
$uploadDir = dirname(__DIR__) . '/uploads/signatures/';
$destPath = $uploadDir . $fileName;
$publicPath = 'uploads/signatures/' . $fileName; // relative to public/

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot create upload directory.']);
        exit();
    }
}

if (!move_uploaded_file($tmpPath, $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save the uploaded file.']);
    exit();
}

// Persist to database
db_execute('UPDATE users SET signature_path = ? WHERE employee_no = ? LIMIT 1', 'ss', [$publicPath, $employeeNo]);

echo json_encode(['success' => true, 'path' => $publicPath]);
exit();
