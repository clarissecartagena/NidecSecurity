<?php

/**
 * Employee Sync API Endpoint — "Sync on Request"
 *
 * Method  : GET
 * Auth    : Session — roles ga_manager or ga_staff only
 *
 * Query params:
 *   employee_no  The employee number to synchronise with the local database.
 *
 * Behaviour
 * ─────────
 * 1. Calls the company Employee API with the supplied employee_no.
 * 2. Compares the API result against the local_employees table
 *    (nidec_security database, accessed via mysqli + prepared statements).
 * 3a. If the employee does not exist locally         → INSERT and report sync.
 * 3b. If the employee exists but name/entity differ  → UPDATE and report sync.
 * 3c. If the employee exists and data is identical   → report no changes.
 *
 * Response (JSON):
 * {
 *   "success" : true | false,
 *   "result"  : "inserted" | "updated" | "no_changes" | null,
 *   "message" : "Database Synchronized" | "No changes detected" | "<error text>",
 *   "error"   : null | "<error text>"
 * }
 *
 * HTTP status codes:
 *   200  Sync completed successfully (inserted / updated / no_changes).
 *   400  Missing or invalid employee_no parameter.
 *   401  Not authenticated.
 *   403  Authenticated but role not permitted.
 *   405  Non-GET request.
 *   500  Unexpected server error or API / database failure.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../app/services/EmployeeSyncService.php';

// ── Always emit JSON ────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// ── Authentication guard ────────────────────────────────────────────────────
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'result' => null, 'message' => 'Unauthorized.', 'error' => 'Unauthorized.']);
    exit();
}

$currentUser = getUser();
$allowedRoles = ['ga_manager', 'ga_staff'];

if (!in_array($currentUser['role'] ?? '', $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'result' => null, 'message' => 'Forbidden.', 'error' => 'Forbidden.']);
    exit();
}

// ── Only GET is accepted ────────────────────────────────────────────────────
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'result' => null,
        'message' => 'Method not allowed.',
        'error' => 'Method not allowed.',
    ]);
    exit();
}

// ── Input ───────────────────────────────────────────────────────────────────
$employeeNo = trim((string) ($_GET['employee_no'] ?? ''));

if ($employeeNo === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'result' => null,
        'message' => 'The employee_no parameter is required.',
        'error' => 'The employee_no parameter is required.',
    ]);
    exit();
}

// ── Sync ────────────────────────────────────────────────────────────────────
try {
    $service = new EmployeeSyncService();
    $result = $service->sync($employeeNo);

    if (!$result['success']) {
        http_response_code(500);
    }

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'result' => null,
        'message' => 'An unexpected server error occurred. Please try again.',
        'error' => 'An unexpected server error occurred. Please try again.',
    ]);
}
