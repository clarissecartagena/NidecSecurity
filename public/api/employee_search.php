<?php

/**
 * Employee Search API Endpoint
 *
 * Method  : GET
 * Auth    : Session — roles ga_manager or ga_staff only
 *
 * Query params (mutually exclusive; employee_id takes precedence):
 *   employee_id  Exact employee ID lookup → returns 0 or 1 record.
 *   q            Free-text search query (min 2 chars) → returns 0..N records.
 *
 * Response (JSON):
 * {
 *   "success"    : true | false,
 *   "employees"  : [ { employee_id, fullname, department, position, email }, … ],
 *   "error"      : null | "human readable message",
 *   "using_mock" : true | false
 * }
 *
 * This is a READ-ONLY endpoint — it never modifies any database.
 * The company Employee API database is never written to by this system.
 */
// 1. LOAD CONFIG FIRST
// This file sets the session_save_path and starts the session correctly.
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php'; // Add this line if getUser lives here
require_once __DIR__ . '/../../app/services/EmployeeService.php';

// 2. ALWAYS EMIT JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// 3. AUTHENTICATION GUARD
// Now that config.php is loaded, isAuthenticated() will find the 'user' session key.
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'employees' => [],
        'error' => 'Unauthorized. Please log in again.',
        'using_mock' => false,
    ]);
    exit();
}

// 4. ROLE AUTHORIZATION
$currentUser = getUser();
$allowedRoles = ['ga_manager', 'ga_staff'];

if (!in_array($currentUser['role'] ?? '', $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'employees' => [],
        'error' => 'Forbidden. You do not have permission to search employees.',
        'using_mock' => false,
    ]);
    exit();
}

// 5. METHOD CHECK
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'employees' => [], 'error' => 'Method not allowed.', 'using_mock' => false]);
    exit();
}

// ── Input ───────────────────────────────────────────────────────────────────
$employeeId = trim((string) ($_GET['employee_id'] ?? ''));
$query = trim((string) ($_GET['q'] ?? ''));

// ── Dispatch ────────────────────────────────────────────────────────────────
try {
    $service = new EmployeeService();

    if ($employeeId !== '') {
        // Exact lookup by employee_id.
        $result = $service->getEmployee($employeeId);

        if ($result['success'] && $result['employee'] !== null) {
            $emp = $result['employee'];
            // Check if the employee qualifies for any system role.
            if (EmployeeService::detectRoleFromEmployee($emp) === null) {
                $empSection = trim((string) ($emp['section'] ?? ''));
                $empJobLevel = trim((string) ($emp['job_level'] ?? ''));
                echo json_encode([
                    'success' => false,
                    'employees' => [],
                    'error' =>
                        'Employee found but does not match an allowed role. ' .
                        "API returned: section=\"{$empSection}\", job_level=\"{$empJobLevel}\". " .
                        'Expected section="' .
                        GA_STAFF_SECTION .
                        '" (GA Staff), ' .
                        'job_level="' .
                        SECURITY_JOB_LEVEL_NCFL .
                        '" or "' .
                        SECURITY_JOB_LEVEL_NPFL .
                        '" (Security), ' .
                        'or job_level="' .
                        DEPARTMENT_JOB_LEVEL .
                        '" (Department). ' .
                        'Run tools/debug_add_user.php for full diagnostics.',
                    'using_mock' => $service->isUsingMock(),
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'employees' => [$emp],
                    'error' => null,
                    'using_mock' => $service->isUsingMock(),
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'employees' => [],
                'error' => $result['error'],
                'using_mock' => $service->isUsingMock(),
            ]);
        }
    } elseif ($query !== '') {
        // Free-text search.
        $result = $service->search($query);

        if ($result['success']) {
            // Filter to only employees who qualify for a system role.
            $allFound = $result['employees'];
            $eligible = array_values(
                array_filter($allFound, static function (array $emp): bool {
                    return EmployeeService::detectRoleFromEmployee($emp) !== null;
                }),
            );

            if (count($eligible) === 0 && count($allFound) > 0) {
                // Employees were found in the directory but none match an allowed role.
                // Build a concise list of what fields the API returned so the admin
                // can compare against the expected role-detection values.
                $sample = array_slice($allFound, 0, 3);
                $details = implode(
                    '; ',
                    array_map(static function (array $e): string {
                        $sec = trim((string) ($e['section'] ?? ''));
                        $jl = trim((string) ($e['job_level'] ?? ''));
                        return '"' .
                            ($e['fullname'] ?? '?') .
                            '"' .
                            ' section="' .
                            $sec .
                            '"' .
                            ' job_level="' .
                            $jl .
                            '"';
                    }, $sample),
                );

                echo json_encode([
                    'success' => false,
                    'employees' => [],
                    'error' =>
                        count($allFound) .
                        ' employee(s) found but none match an allowed role. ' .
                        'Run tools/debug_add_user.php <employee_id> for diagnostics. ' .
                        'Sample: ' .
                        $details,
                    'using_mock' => $result['using_mock'],
                ]);
            } else {
                echo json_encode([
                    'success' => count($eligible) > 0,
                    'employees' => $eligible,
                    'error' => count($eligible) === 0 ? $result['error'] ?? 'No employees found.' : null,
                    'using_mock' => $result['using_mock'],
                ]);
            }
        } else {
            echo json_encode($result);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'employees' => [],
            'error' => 'Provide either a search query (q=...) or an employee_id.',
            'using_mock' => false,
        ]);
    }
} catch (Throwable $e) {
    // Never leak internal details in response body.
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'employees' => [],
        'error' => 'An unexpected server error occurred. Please try again.',
        'using_mock' => false,
    ]);
}
