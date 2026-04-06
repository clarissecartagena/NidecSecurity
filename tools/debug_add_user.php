<?php
/**
 * Debug Add-User Tool
 * ──────────────────────────────────────────────────────────────────────────
 * Diagnoses exactly why an employee can or cannot be found / added through
 * the web UI's "Add New User" modal.
 *
 * Usage:
 *   php tools/debug_add_user.php <employee_id>
 *
 * Example:
 *   php tools/debug_add_user.php 300553
 *
 * What it checks:
 *   1. API connectivity (same check as provision_allowed_users.php).
 *   2. getEmployee()  – exact ID lookup (same call as when the form submits).
 *   3. Role detection – shows every field the API returned and whether it
 *      matches the expected role-detection values.
 *   4. Free-text search – simulates typing the employee ID into the search
 *      box so you can see whether q= returns anything.
 *   5. Database      – whether a local account already exists.
 *
 * ──────────────────────────────────────────────────────────────────────────
 */
define('SCRIPT_ROOT', dirname(__DIR__));
require_once SCRIPT_ROOT . '/config/database.php';
require_once SCRIPT_ROOT . '/config/api.php';
require_once SCRIPT_ROOT . '/app/api_clients/EmployeeApiClient.php';
require_once SCRIPT_ROOT . '/app/services/EmployeeService.php';

// ── Helpers ────────────────────────────────────────────────────────────────
function hr(string $char = '─', int $width = 68): void
{
    echo str_repeat($char, $width) . "\n";
}
function pass(string $msg): void
{
    echo "  [PASS] {$msg}\n";
}
function fail(string $msg): void
{
    echo "  [FAIL] {$msg}\n";
}
function warn(string $msg): void
{
    echo "  [WARN] {$msg}\n";
}
function info(string $msg): void
{
    echo "  [INFO] {$msg}\n";
}
// ── Args ───────────────────────────────────────────────────────────────────
$employeeId = trim((string) ($argv[1] ?? ''));
// ── Banner ─────────────────────────────────────────────────────────────────
$service = new EmployeeService();
hr('=');
echo " Add-User Debug Tool\n";
hr('=');
printf(" %-16s : %s\n", 'Mode', $employeeId !== '' ? "Single employee ({$employeeId})" : 'System diagnostic');
printf(" %-16s : %s\n", 'API endpoint', $service->getApiBaseUrl());
printf(" %-16s : %s\n", 'Using mock', $service->isUsingMock() ? 'YES (local mock server)' : 'NO (company API)');
printf(" %-16s : %s\n", 'API_ENV', API_ENV);
hr('=');
echo "\n";

// ══════════════════════════════════════════════════════════════════════════
// MODE A — No argument: full system diagnostic
// ══════════════════════════════════════════════════════════════════════════
if ($employeeId === '') {
    // ── A1: Database connectivity ──────────────────────────────────────────
    echo "Check 1 — Database connection\n";
    hr();
    $dbOk = false;
    try {
        db(); // triggers connection
        pass('Database connected  (host=' . DB_HOST . '  db=' . DB_NAME . ').');
        $dbOk = true;
    } catch (Throwable $ex) {
        fail('Database connection FAILED: ' . $ex->getMessage());
        echo "\n  ► Check config/database.php — DB_HOST, DB_NAME, DB_USER, DB_PASS.\n";
        echo "    Make sure MySQL / XAMPP is running.\n";
    }
    echo "\n";
    // ── A2: Users already in the database ─────────────────────────────────
    echo "Check 2 — Existing user accounts in the database\n";
    hr();
    if ($dbOk) {
        try {
            $users = db_fetch_all(
                'SELECT employee_no, username, role, account_status FROM users ORDER BY employee_no LIMIT 20',
            );
            if (empty($users)) {
                warn('No user accounts exist in the database yet.');
                echo "    ► This is normal for a fresh install.\n";
            } else {
                pass(count($users) . ' account(s) found:');
                printf("    %-12s  %-20s  %-16s  %s\n", 'Emp No', 'Username', 'Role', 'Status');
                echo '    ' . str_repeat('-', 70) . "\n";
                foreach ($users as $u) {
                    printf(
                        "    %-12s  %-20s  %-16s  %s\n",
                        $u['employee_no'] ?? '(none)',
                        $u['username'],
                        $u['employee_no'] ?? '(none)',
                        $u['role'],
                        $u['account_status'],
                    );
                }
            }
        } catch (Throwable $ex) {
            warn('Could not query users table: ' . $ex->getMessage());
        }
    } else {
        warn('Skipping — database is not connected.');
    }
    echo "\n";
    // ── A3: API connectivity ───────────────────────────────────────────────
    echo "Check 3 — Employee API connectivity\n";
    hr();
    // Try a broad single-letter search to get some real employee records.
    $sampleSearches = ['an', 'en', 'sa', 'na', 'jo', 'ma'];
    $apiEmployees = [];
    $apiError = null;
    foreach ($sampleSearches as $letter) {
        $res = $service->search($letter);
        if ($res['success'] && !empty($res['employees'])) {
            $apiEmployees = $res['employees'];
            pass("API search for \"{$letter}\" returned " . count($apiEmployees) . ' employee(s).');
            break;
        }
        $apiError = $res['error'] ?? 'No results';
    }
    if (empty($apiEmployees)) {
        fail('API returned no employees for any test query.');
        echo "\n  Last error : " . ($apiError ?? 'Unknown') . "\n";
        echo "\n  Possible causes:\n";
        if ($service->isUsingMock()) {
            echo '    • The mock server at ' . $service->getApiBaseUrl() . " is not running.\n";
            echo "      Start XAMPP and make sure nidec_api_mock is in your htdocs folder.\n";
        } else {
            echo '    • The company Employee API at ' . $service->getApiBaseUrl() . " is unreachable.\n";
            echo "      Check that you are on the company VPN / internal network.\n";
        }
        echo "    • config/api.php has a wrong endpoint URL.\n";
    }
    echo "\n";
    // ── A4: List employees returned by the API ─────────────────────────────
    if (!empty($apiEmployees)) {
        echo "Check 4 — Employees visible in the API  (role eligibility)\n";
        hr();
        echo "  The following employees were returned. The [ROLE] column shows\n";
        echo "  whether each one can be added via the web UI.\n\n";
        printf("  %-12s  %-28s  %-12s  %s\n", 'Employee ID', 'Name', 'Role', 'Job Level / Section');
        echo '  ' . str_repeat('-', 80) . "\n";
        foreach ($apiEmployees as $e) {
            $det = EmployeeService::detectRoleFromEmployee($e);
            $roleLabel =
                $det !== null
                    ? $det['role'] . ($det['entity'] !== '' ? '/' . $det['entity'] : '')
                    : '-- NOT ELIGIBLE --';
            $extra = $e['job_level'] !== '' ? $e['job_level'] : $e['section'];
            printf(
                "  %-12s  %-28s  %-12s  %s\n",
                $e['employee_id'] ?? '?',
                mb_substr($e['fullname'] ?? '?', 0, 28),
                $roleLabel,
                mb_substr($extra, 0, 30),
            );
        }
        echo "\n";
        echo "  ► To diagnose a specific employee, run:\n";
        echo "      php tools/debug_add_user.php EMPLOYEE_ID\n";
        $firstId = $apiEmployees[0]['employee_id'] ?? '';
        if ($firstId !== '') {
            echo "    Example (first employee returned above):\n";
            echo "      php tools/debug_add_user.php {$firstId}\n";
        }
    } elseif (empty($apiEmployees)) {
        // Already reported the failure in Check 3; skip the table.
        echo "Check 4 — Skipped (API not reachable).\n";
    }
    echo "\n";
    // ── A5: Role detection constants ───────────────────────────────────────
    echo "Check 5 — Role detection constants (from app/services/EmployeeService.php)\n";
    hr();
    echo "  For an employee to appear in the Add User search, their HR data must\n";
    echo "  match one of these values exactly (case-insensitive):\n\n";
    printf("  %-18s : employee_id === \"%s\"\n", 'ga_manager', GA_MANAGER_EMPLOYEE_NO);
    printf("  %-18s : section     === \"%s\"\n", 'ga_staff', GA_STAFF_SECTION);
    printf("  %-18s : job_level   === \"%s\"\n", 'security (NCFL)', SECURITY_JOB_LEVEL_NCFL);
    printf("  %-18s : job_level   === \"%s\"\n", 'security (NPFL)', SECURITY_JOB_LEVEL_NPFL);
    printf("  %-18s : job_level   === \"%s\"\n", 'department', DEPARTMENT_JOB_LEVEL);
    echo "\n";
    hr('=');
    echo " System diagnostic complete.\n";
    echo " Next step: run  php tools/debug_add_user.php EMPLOYEE_ID  to\n";
    echo " check why a specific employee cannot be added.\n";
    hr('=');
    echo "\n";
    exit(0);
}
// ══════════════════════════════════════════════════════════════════════════
// MODE B — Employee ID provided: deep-dive on one employee
// ══════════════════════════════════════════════════════════════════════════

// ─────────────────────────────────────────────────────────────────────────
// Step 1 — Exact lookup (employee_id=)
// This is the lookup used by:
//   • the web UI when you type a numeric ID into the search box
//   • the form submission that re-fetches employee data server-side
// ─────────────────────────────────────────────────────────────────────────
echo "Step 1 — Exact lookup: getEmployee('{$employeeId}')\n";
hr();
$result = $service->getEmployee($employeeId);
if (!$result['success'] || $result['employee'] === null) {
    fail('Employee not found via exact lookup.');
    echo "\n  Error : " . ($result['error'] ?? 'No error message returned.') . "\n";
    echo "\n  ► This is why the web UI shows no results when you type this employee\n";
    echo "    ID into the Add User search box.\n";
    echo "\n  Possible causes:\n";
    echo "    • Employee ID does not exist in the company HR system.\n";
    echo "    • Company API is unreachable (check VPN / network).\n";
    echo "    • API endpoint URL is wrong  — check config/api.php.\n";
    echo "    • Run without an argument first to see all available employee IDs:\n";
    echo "        php tools/debug_add_user.php\n";
    echo "\n";
    exit(1);
}
$emp = $result['employee'];
pass('Employee found via exact lookup.');
echo "\n";
echo "  Raw fields returned by the API:\n";
$fieldWidth = max(array_map('strlen', array_keys($emp))) + 2;
foreach ($emp as $field => $value) {
    printf("  %-{$fieldWidth}s : %s\n", $field, $value !== '' && $value !== null ? $value : '(empty)');
}
echo "\n";
// ─────────────────────────────────────────────────────────────────────────
// Step 2 — Role detection
// ─────────────────────────────────────────────────────────────────────────
echo "Step 2 — Role detection\n";
hr();
$detected = EmployeeService::detectRoleFromEmployee($emp);
$empEmpId = trim((string) ($emp['employee_id'] ?? ''));
$empSection = trim((string) ($emp['section'] ?? ''));
$empJobLvl = trim((string) ($emp['job_level'] ?? ''));
echo "  Fields used for role detection:\n";
printf("  %-15s : \"%s\"\n", 'employee_id', $empEmpId);
printf("  %-15s : \"%s\"\n", 'section', $empSection);
printf("  %-15s : \"%s\"\n", 'job_level', $empJobLvl);
echo "\n";
echo "  Expected values per role:\n";
printf("  %-18s : employee_id === \"%s\"\n", 'ga_manager', GA_MANAGER_EMPLOYEE_NO);
printf("  %-18s : section     === \"%s\"\n", 'ga_staff', GA_STAFF_SECTION);
printf("  %-18s : job_level   === \"%s\"\n", 'security (NCFL)', SECURITY_JOB_LEVEL_NCFL);
printf("  %-18s : job_level   === \"%s\"\n", 'security (NPFL)', SECURITY_JOB_LEVEL_NPFL);
printf("  %-18s : job_level   === \"%s\"\n", 'department', DEPARTMENT_JOB_LEVEL);
echo "\n";
if ($detected === null) {
    fail('Role detection FAILED — employee does not match any allowed role.');
    echo "\n  ► This is why the web UI rejects this employee (even if they appear\n";
    echo "    in search results, they cannot be added).\n";
    echo "\n  How to fix:\n";
    echo "    Option A: Update the employee's data in the company HR system so\n";
    echo "              their section or job_level matches one of the expected\n";
    echo "              values above.\n";
    echo "    Option B: Add a new matching rule in app/services/EmployeeService.php\n";
    echo "              inside detectRoleFromEmployee() and define a new constant\n";
    echo "              at the top of that file if needed.\n";
    echo "\n";
    // Check character-level differences for the closest match
    foreach (
        [
            'section (ga_staff check)' => [$empSection, GA_STAFF_SECTION],
            'job_level (Security NCFL)' => [$empJobLvl, SECURITY_JOB_LEVEL_NCFL],
            'job_level (Security NPFL)' => [$empJobLvl, SECURITY_JOB_LEVEL_NPFL],
            'job_level (Department)' => [$empJobLvl, DEPARTMENT_JOB_LEVEL],
        ]
        as $label => [$actual, $expected]
    ) {
        if ($actual === '' || $expected === '') {
            continue;
        }
        $similarity = 0;
        similar_text(strtolower($actual), strtolower($expected), $similarity);
        if ($similarity >= 60) {
            warn("Close match on {$label}: \"{$actual}\" vs \"{$expected}\" ({$similarity}% similar).");
            echo "       Likely a whitespace, capitalisation, or typo difference.\n";
        }
    }
    exit(1);
}
pass(
    "Role detection OK → role='{$detected['role']}'" .
        ($detected['entity'] !== '' ? ", entity='{$detected['entity']}'" : '') .
        '.',
);
echo "\n";
// ─────────────────────────────────────────────────────────────────────────
// Step 3 — Free-text search (q=)
// This simulates what the web UI sends when the user types the employee ID
// into the search box WITHOUT the numeric-ID detection fix applied.
// ─────────────────────────────────────────────────────────────────────────
echo "Step 3 — Free-text search: search('{$employeeId}')\n";
hr();
$searchResult = $service->search($employeeId);
if (!$searchResult['success']) {
    warn('Free-text search returned no results: ' . ($searchResult['error'] ?? 'Unknown error'));
    echo "\n  ► If the web UI was using ?q= for this employee ID (instead of\n";
    echo "    ?employee_id=), it would show no results.\n";
    echo "    The fix in views/users/users.php ensures numeric queries now use\n";
    echo "    ?employee_id= so exact lookup is used instead.\n";
} else {
    $found = $searchResult['employees'];
    pass('Free-text search returned ' . count($found) . ' employee(s).');
    foreach ($found as $e) {
        printf("    • %-10s  %s\n", $e['employee_id'] ?? '?', $e['fullname'] ?? '?');
    }
}
echo "\n";
// ─────────────────────────────────────────────────────────────────────────
// Step 4 — Database check
// ─────────────────────────────────────────────────────────────────────────
echo "Step 4 — Database account check\n";
hr();
try {
    $row = db_fetch_one(
        'SELECT employee_no, username, role, account_status FROM users WHERE employee_no = ? LIMIT 1',
        's',
        [$employeeId],
    );
    if ($row) {
        warn('An account already exists for this employee.');
        printf("  %-15s : %s\n", 'username', $row['username']);
        printf("  %-15s : %s\n", 'role', $row['role']);
        printf("  %-15s : %s\n", 'account_status', $row['account_status']);
        echo "\n  ► Adding again via the web UI will fail with a duplicate key error.\n";
        echo "    Use the Edit or Delete buttons in User Management instead.\n";
    } else {
        pass('No existing account — this employee can be added via the web UI.');
    }
} catch (Throwable $e) {
    warn('Could not query the database: ' . $e->getMessage());
}
echo "\n";
// ─────────────────────────────────────────────────────────────────────────
// Summary
// ─────────────────────────────────────────────────────────────────────────
hr('=');
echo " RESULT: Employee '{$employeeId}' passes all checks.\n";
echo "         Detected role : {$detected['role']}" .
    ($detected['entity'] !== '' ? " / entity: {$detected['entity']}" : '') .
    "\n";
echo "         They can be added via the web UI Add New User form.\n";
hr('=');
echo "\n";
exit(0);
