<?php

/**
 * EmployeeService
 *
 * Business-logic layer between controllers / AJAX endpoints and the raw
 * EmployeeApiClient.  Handles input validation, delegates to the client,
 * and returns consistent result envelopes to callers.
 *
 * Methods
 * ───────
 * search($query)                    – Free-text search returning a list of employees.
 * getEmployee($id)                  – Exact lookup by employee_id; used to re-verify
 *                                     employee data at form-submission time so that a
 *                                     malicious actor cannot spoof name/department by
 *                                     crafting a POST body.
 * detectRoleFromEmployee($emp)      – Derive role + entity from Employee API fields.
 *
 * Role detection rules (static, based on API data):
 *   GA Manager  – employee_no === GA_MANAGER_EMPLOYEE_NO ('300553')
 *   GA Staff      – section === 'HUMAN RESOURCE, GA AND COMPLIANCE'
 *   Security NCFL – job_level === 'Security'     (entity NCFL)
 *   Security NPFL – job_level === 'SEGURITY GUARD' (entity NPFL — note API typo)
 *   Department    – job_level === 'SUPPORT/PIC'
 *
 * @see EmployeeApiClient
 */

require_once __DIR__ . '/../api_clients/EmployeeApiClient.php';

/** Employee number that is permanently mapped to the GA Manager role. */
const GA_MANAGER_EMPLOYEE_NO = '300553';

/** Section string that identifies a GA Staff employee in the Employee API. */
const GA_STAFF_SECTION = 'HUMAN RESOURCE, GA AND COMPLIANCE';

/** job_level value for a Security Guard assigned to the NCFL entity. */
const SECURITY_JOB_LEVEL_NCFL = 'Security';

/**
 * job_level value for a Security Guard assigned to the NPFL entity.
 * Note: the company HR system has a typo ("SEGURITY" instead of "SECURITY").
 */
const SECURITY_JOB_LEVEL_NPFL = 'SEGURITY GUARD';

/** job_level value that identifies a Department PIC user. */
const DEPARTMENT_JOB_LEVEL = 'SUPPORT/PIC';

class EmployeeService
{
    private EmployeeApiClient $client;

    public function __construct(?EmployeeApiClient $client = null)
    {
        $this->client = $client ?? new EmployeeApiClient();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Returns true when the mock API is currently in use.
     * Useful for diagnostic / status output in CLI tools.
     */
    public function isUsingMock(): bool
    {
        return $this->client->isUsingMock();
    }

    /**
     * Returns the resolved API base URL that is currently in use
     * (either the company API or the local mock).
     */
    public function getApiBaseUrl(): string
    {
        return $this->client->getBaseUrl();
    }

    /**
     * Search employees by free-text query (name fragment or employee ID).
     *
     * Returns up to however many records the company API returns. The caller
     * is responsible for presenting / paginating the list.
     *
     * @return array{
     *   success:    bool,
     *   employees:  list<array<string,string>>,
     *   error:      ?string,
     *   using_mock: bool
     * }
     */
    // Inside EmployeeService.php -> search() function
    /**
     * Search employees by free-text query (name fragment or employee ID).
     *
     * @return array{success: bool, employees: list<array>, error: ?string, using_mock: bool}
     */
    public function search(string $query): array
    {
        // Ensure the query is a clean string and remove any non-numeric characters
        // just in case the user types "ID-9200191"
        $query = preg_replace('/\D/', '', trim($query));

        // 1. Validation check
        if (strlen($query) < 7) {
            return [
                'success' => false,
                'employees' => [],
                'error' => 'Please enter a complete 7-digit Employee ID.',
                'using_mock' => $this->client->isUsingMock(),
            ];
        }

        try {
            // 2. Call API
            $result = $this->client->searchEmployees($query);

            // Check if data exists in either 'data' or 'employees' keys
            $employees = $result['data'] ?? ($result['employees'] ?? []);

            if ($result['success'] && !empty($employees)) {
                $searchTerm = (string) $query;

                $matches = array_filter($employees, function ($emp) use ($searchTerm) {
                    // Ensure we are comparing strings to strings to avoid leading zero issues
                    $empId = trim((string) ($emp['employee_id'] ?? ''));
                    return $empId === $searchTerm;
                });

                if (!empty($matches)) {
                    return [
                        'success' => true,
                        'employees' => array_values($matches),
                        'error' => null,
                        'using_mock' => $this->client->isUsingMock(),
                    ];
                }
            }

            // 3. Explicit "Not Found" response
            return [
                'success' => false,
                'employees' => [],
                'error' => "Employee ID '$query' not found in the database.",
                'using_mock' => $this->client->isUsingMock(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'employees' => [],
                'error' => 'Employee database connection error.',
                'using_mock' => $this->client->isUsingMock(),
            ];
        }
    }
    /**
     * Verify and retrieve a single employee by exact employee_id.
     *
     * Always called at form-submission time so that name / department / email
     * / position data is sourced from the authoritative API rather than from a
     * POST body that could have been tampered with.
     *
     * @return array{
     *   success:  bool,
     *   employee: ?array<string,string>,
     *   error:    ?string
     * }
     */
    public function getEmployee(string $employeeId): array
    {
        $employeeId = trim($employeeId);

        if ($employeeId === '') {
            return ['success' => false, 'employee' => null, 'error' => 'Employee ID is required.'];
        }

        $result = $this->client->getEmployeeById($employeeId);

        return [
            'success' => $result['success'],
            'employee' => $result['data'] ?? null,
            'error' => $result['error'],
        ];
    }

    /**
     * Derive the system role and entity assignment from an Employee API record.
     *
     * Matching rules (evaluated in priority order):
     *   1. employee_id === GA_MANAGER_EMPLOYEE_NO    → role='ga_manager'
     *   2. section === GA_STAFF_SECTION              → role='ga_staff'
     *   3. job_level === SECURITY_JOB_LEVEL_NCFL     → role='security', entity='NCFL'
     *   4. job_level === SECURITY_JOB_LEVEL_NPFL     → role='security', entity='NPFL'
     *   5. job_level === DEPARTMENT_JOB_LEVEL        → role='department'
     *   6. No match                                  → null (employee cannot be added)
     *
     * @param  array<string,string> $emp  Normalized employee record from EmployeeApiClient.
     * @return array{role:string, entity:string}|null  Role + entity on match, null when
     *         the employee's API data does not match any recognised role pattern.
     */
    public static function detectRoleFromEmployee(array $emp): ?array
    {
        $employeeId = trim((string) ($emp['employee_id'] ?? ''));
        $section = trim((string) ($emp['section'] ?? ''));
        $jobLevel = trim((string) ($emp['job_level'] ?? ''));

        // 1. GA Manager — exactly one person, identified by employee number.
        if ($employeeId === GA_MANAGER_EMPLOYEE_NO) {
            return ['role' => 'ga_manager', 'entity' => ''];
        }

        // 2. GA Staff — identified by section name in the company HR system.
        if (strcasecmp($section, GA_STAFF_SECTION) === 0) {
            return ['role' => 'ga_staff', 'entity' => ''];
        }

        // 3. Security Guard – NCFL entity.
        if (strcasecmp($jobLevel, SECURITY_JOB_LEVEL_NCFL) === 0) {
            return ['role' => 'security', 'entity' => 'NCFL'];
        }

        // 4. Security Guard – NPFL entity (API has a typo: "SEGURITY").
        if (strcasecmp($jobLevel, SECURITY_JOB_LEVEL_NPFL) === 0) {
            return ['role' => 'security', 'entity' => 'NPFL'];
        }

        // 5. Department PIC — identified by job level.
        if (strcasecmp($jobLevel, DEPARTMENT_JOB_LEVEL) === 0) {
            return ['role' => 'department', 'entity' => ''];
        }

        // Employee does not match any system role.
        return null;
    }
}
