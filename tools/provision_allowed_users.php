<?php
/**
 * Provision Allowed Users
 * ──────────────────────────────────────────────────────────────────────────
 * CLI script that reads config/allowed_users.php and creates a local account
 * in the database for every entry that does not yet have one.
 *
 * Run once after a fresh database setup (instead of running a seeder):
 *
 *   php tools/provision_allowed_users.php
 *
 * What it does for each configured employee:
 *   1. Checks if the employee_no or username already exists in the users table.
 *   2. If not, calls the Employee API to fetch their name/email/position.
 *   3. Auto-detects role and entity from Employee API data.
 *   4. Inserts a new active account with the configured credentials.
 *
 * If the Employee API is unreachable the entry is skipped with a warning.
 * Re-running the script is safe — existing accounts are left untouched.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * TEST CREDENTIALS (all accounts below use the same password)
 *
 *  Username         Password       Role          Entity / Dept
 *  ───────────────  ─────────────  ────────────  ──────────────────────
 *  k.enriquez       Password123!   ga_president  —
 *  l.acosta         Password123!   ga_staff      —
 *  c.buenconsejo    Password123!   ga_staff      —
 *  b.esteban        Password123!   security      NCFL / external
 *  e.corrales       Password123!   security      NCFL / internal
 *  c.provido        Password123!   security      NPFL / internal
 *  j.ruazol         Password123!   security      NPFL / external
 * ──────────────────────────────────────────────────────────────────────────
 */

// ── Bootstrap ──────────────────────────────────────────────────────────────
define('SCRIPT_ROOT', dirname(__DIR__));

require_once SCRIPT_ROOT . '/config/database.php';
require_once SCRIPT_ROOT . '/config/api.php';
require_once SCRIPT_ROOT . '/app/models/UsersModel.php';
require_once SCRIPT_ROOT . '/app/api_clients/EmployeeApiClient.php';
require_once SCRIPT_ROOT . '/app/services/EmployeeService.php';
require_once SCRIPT_ROOT . '/app/services/AllowedUsersService.php';

$allowed = require SCRIPT_ROOT . '/config/allowed_users.php';

$model = new UsersModel();
$employeeService = new EmployeeService();

// ── API connectivity banner ────────────────────────────────────────────────

$apiUrl = $employeeService->getApiBaseUrl();
$usingMock = $employeeService->isUsingMock();

echo "API endpoint : {$apiUrl}\n";
if ($usingMock) {
    echo "Status       : WARNING — company API unreachable; using LOCAL MOCK API.\n";
    echo '               Ensure the mock server is running at ' . MOCK_API_BASE_URL . "\n";
    echo "               or connect to the company network/VPN and re-run.\n";
} else {
    echo "Status       : Connected to company API.\n";
}
echo "\n";

// ── Helpers ────────────────────────────────────────────────────────────────

function already_exists(string $employeeNo): bool
{
    $row = db_fetch_one('SELECT employee_no FROM users WHERE employee_no = ? LIMIT 1', 's', [$employeeNo]);
    return (bool) $row;
}

/**
 * Strip terminal control characters from a string before printing it.
 * Prevents terminal escape sequences in API response data from corrupting output.
 */
function safe_print(string $value): string
{
    return preg_replace('/[\x00-\x1f\x7f]/u', '', $value) ?? $value;
}

/**
 * Resolve a local department ID from a department name returned by the API.
 * Returns 0 when the name is blank or no matching department exists.
 */
function department_id_by_name(string $name): int
{
    $name = trim($name);
    if ($name === '') {
        return 0;
    }
    $row = db_fetch_one('SELECT id FROM departments WHERE LOWER(name) = ? LIMIT 1', 's', [strtolower($name)]);
    return $row ? (int) $row['id'] : 0;
}

// ── Main loop ──────────────────────────────────────────────────────────────

$provisioned = 0;
$skipped = 0;
$failed = 0;

echo "Provisioning allowed users from config/allowed_users.php...\n\n";

foreach ($allowed as $entry) {
    // Support both 'employee_no' (new) and 'employee_id' (legacy) key names.
    $employeeNo = trim((string) ($entry['employee_no'] ?? ($entry['employee_id'] ?? '')));
    $password = (string) ($entry['password'] ?? '');

    if ($employeeNo === '') {
        echo "  [SKIP]  Entry has no employee_no — skipped.\n";
        $skipped++;
        continue;
    }

    // Check if an account already exists for this employee.
    if (already_exists($employeeNo)) {
        echo "  [OK]    {$employeeNo} already exists — skipped.\n";
        $skipped++;
        continue;
    }

    // Fetch employee data from the API.
    $empResult = $employeeService->getEmployee($employeeNo);
    if (!$empResult['success'] || empty($empResult['employee'])) {
        $error = $empResult['error'] ?? 'Employee API unreachable or employee not found.';
        echo "  [FAIL]  {$employeeNo}: {$error}\n";
        echo "          (endpoint: {$apiUrl})\n";
        $failed++;
        continue;
    }
    $emp = $empResult['employee'];

    // ── Auto-detect role + entity from Employee API ────────────────────────
    $detected = EmployeeService::detectRoleFromEmployee($emp);
    if ($detected === null) {
        echo "  [SKIP]  {$employeeNo}: employee does not match any allowed role — skipped.\n";
        echo "          API returned: employee_id='" .
            safe_print($emp['employee_id']) .
            "'" .
            " section='" .
            safe_print($emp['section']) .
            "'" .
            " job_level='" .
            safe_print($emp['job_level']) .
            "'\n";
        echo "          Expected one of:\n";
        echo "            employee_id === '" . GA_PRESIDENT_EMPLOYEE_NO . "' (ga_president)\n";
        echo "            section     === '" . GA_STAFF_SECTION . "' (ga_staff)\n";
        echo "            job_level   === '" .
            SECURITY_JOB_LEVEL_NCFL .
            "' or '" .
            SECURITY_JOB_LEVEL_NPFL .
            "' (security)\n";
        echo "            job_level   === '" . DEPARTMENT_JOB_LEVEL . "' (department)\n";
        $skipped++;
        continue;
    }
    $role = $detected['role'];
    $entity = $detected['entity'];

    // ── Derive username from API fullname ─────────────────────────────────
    $username = AllowedUsersService::generateUsername((string) ($emp['fullname'] ?? ''));
    if ($username === '') {
        // Fall back to employee_no when name cannot be parsed.
        $username = $employeeNo;
    }

    // Hash the configured password (or leave blank if none set).
    $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '';

    $departmentId = department_id_by_name((string) ($emp['department'] ?? ''));

    try {
        $model->insertUser(
            (string) ($emp['employee_id'] ?? $employeeNo),
            (string) ($emp['fullname'] ?? $employeeNo),
            (string) ($emp['email'] ?? ''),
            (string) ($emp['position'] ?? ''),
            (string) ($emp['job_level'] ?? ''),
            (string) ($emp['department'] ?? ''),
            $username,
            $passwordHash,
            $role,
            '', // 10. $securityType (Empty string or default value)
            $entity, // 11. $entity
            (int) $departmentId, // 12. $departmentId (MUST be the 12th argument)
            'active', // 13. $accountStatus
        );

        $tag = $password !== '' ? '(password set)' : '(no local password — API login only)';
        echo "  [DONE]  {$username} ({$employeeNo}) provisioned as {$role} {$tag}\n";
        $provisioned++;
    } catch (Throwable $e) {
        echo "  [FAIL]  {$employeeNo}: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n";
echo "Done. Provisioned: {$provisioned}  Skipped: {$skipped}  Failed: {$failed}\n";

if ($failed > 0) {
    echo "\nNote: Failed entries are usually caused by the Employee API being unreachable.\n";
    echo "Endpoint tried : {$apiUrl}\n";
    if ($usingMock) {
        echo 'The company API (' . COMPANY_API_BASE_URL . ") was unreachable and the\n";
        echo 'local mock server (' . MOCK_API_BASE_URL . ") was used instead.\n";
        echo "If the mock is also unreachable, start the mock server and re-run.\n";
    } else {
        echo "Check that you are connected to the company network/VPN and re-run.\n";
    }
    exit(1);
}

exit(0);
