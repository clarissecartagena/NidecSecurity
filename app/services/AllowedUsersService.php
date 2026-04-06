<?php

require_once __DIR__ . '/EmployeeService.php';
require_once __DIR__ . '/../models/UsersModel.php';

/**
 * AllowedUsersService
 * ──────────────────────────────────────────────────────────────────────────
 * Manages the list of employee numbers that are permitted to log in.
 *
 * When an employee in the allowed list authenticates via the corporate login
 * API but does not yet have a local account, this service:
 *   1. Looks up their profile data in the Employee API.
 *   2. Derives their username from the API fullname (firstinitial.lastname).
 *   3. Auto-detects their role, entity, and department from the API data.
 *   4. Inserts a new active account in the local `users` table.
 *   5. Returns the newly created user record.
 *
 * Role detection (via EmployeeService::detectRoleFromEmployee()):
 *   GA President  – employee_no === '300553'
 *   GA Staff      – section  === 'HUMAN RESOURCE, GA AND COMPLIANCE'
 *   Security NCFL – job_level === 'Security'
 *   Security NPFL – job_level === 'SEGURITY GUARD'
 *   Department    – job_level === 'SUPPORT/PIC'
 *
 * The allowed list is configured in config/allowed_users.php.
 *
 * @see config/allowed_users.php
 * @see EmployeeService
 * @see UsersModel
 */
class AllowedUsersService
{
    /** @var array<int, array<string,mixed>> Loaded from config/allowed_users.php */
    private array $allowed;

    private EmployeeService $employeeService;
    private UsersModel $usersModel;

    public function __construct(?EmployeeService $employeeService = null, ?UsersModel $usersModel = null)
    {
        $this->employeeService = $employeeService ?? new EmployeeService();
        $this->usersModel = $usersModel ?? new UsersModel();

        $configPath = __DIR__ . '/../../config/allowed_users.php';
        $this->allowed = is_file($configPath) ? (array) require $configPath : [];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Returns true when the given employee number is in the allowed list.
     */
    public function isAllowed(string $employeeNo): bool
    {
        return $this->getConfig($employeeNo) !== null;
    }

    /**
     * Returns the configuration for an allowed employee, or null when
     * the employee number is not in the list.
     *
     * @return array{employee_no:string, password:?string}|null
     */
    public function getConfig(string $employeeNo): ?array
    {
        $employeeNo = trim($employeeNo);
        foreach ($this->allowed as $entry) {
            $id = (string) ($entry['employee_no'] ?? ($entry['employee_id'] ?? ''));
            if ($id === $employeeNo) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Provision a new local user account for an allowed employee.
     *
     * Steps:
     *   1. Verify the employee_no is in the allowed list.
     *   2. Fetch the employee's profile from the Employee API.
     *   3. Auto-detect role and entity from the API data.
     *   4. Derive username from the employee's fullname (firstinitial.lastname).
     *   5. Insert a new active account into the local `users` table.
     *   6. Return the newly created user record (ready for session creation).
     *
     * @param  string $employeeNo  The employee number returned by the login API.
     * @param  string $username    The username the employee used to log in (fallback).
     * @return array<string,mixed>|null  User record on success, null on failure.
     */
    public function provision(string $employeeNo, string $username): ?array
    {
        $config = $this->getConfig($employeeNo);
        if ($config === null) {
            return null;
        }

        // Hash the configured test password, or leave empty (API-only auth).
        $plainPassword = (string) ($config['password'] ?? '');
        $passwordHash = $plainPassword !== '' ? password_hash($plainPassword, PASSWORD_DEFAULT) : '';

        // Fetch profile from the Employee API.
        $empResult = $this->employeeService->getEmployee($employeeNo);
        if (!$empResult['success'] || empty($empResult['employee'])) {
            return null;
        }
        $emp = $empResult['employee'];

        // ── Determine role + entity from Employee API data ────────────────
        $detected = EmployeeService::detectRoleFromEmployee($emp);
        if ($detected === null) {
            // Employee is not eligible for any system role.
            return null;
        }
        $role = $detected['role'];
        $entity = $detected['entity'];

        // ── Derive username from API fullname (firstinitial.lastname) ─────
        $resolvedUsername = self::generateUsername((string) ($emp['fullname'] ?? ''));
        // Fall back to the login-time username if name parsing fails.
        if ($resolvedUsername === '') {
            $resolvedUsername = $username !== '' ? $username : $employeeNo;
        }

        // security_type is not derivable from the Employee API alone;
        // default to 'internal' and let GA Staff/President update it if needed.
        $securityType = $role === 'security' ? 'internal' : '';

        $departmentId = $this->departmentIdByName((string) ($emp['department'] ?? ''));

        try {
            $this->usersModel->insertUser(
                (string) ($emp['employee_id'] ?? $employeeNo),
                (string) ($emp['fullname'] ?? ''),
                (string) ($emp['email'] ?? ''),
                (string) ($emp['position'] ?? ''),
                (string) ($emp['job_level'] ?? ''),
                (string) ($emp['department'] ?? ''),
                $resolvedUsername,
                $passwordHash,
                $role,
                $securityType,
                $entity,
                $departmentId,
                'active',
            );
        } catch (Throwable $e) {
            // Insertion failed (e.g., duplicate username or employee_no).
            return null;
        }

        // Return the freshly inserted record so the caller can open a session.
        return $this->usersModel->findProvisionedUser($employeeNo, $resolvedUsername);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Public helpers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Generate a username from an employee's fullname.
     *
     * Expects the "LASTNAME, FIRSTNAME" format used by the company HR system.
     * Returns "firstinitial.lastname" in lowercase (e.g. "k.enriquez").
     * Returns an empty string when the name cannot be parsed.
     */
    public static function generateUsername(string $fullname): string
    {
        $fullname = trim($fullname);
        if ($fullname === '' || strpos($fullname, ',') === false) {
            return '';
        }
        [$last, $first] = explode(',', $fullname, 2);
        $last = strtolower(trim($last));
        $first = strtolower(trim($first));
        $initial = $first !== '' ? mb_substr($first, 0, 1) : '';
        if ($initial !== '' && $last !== '') {
            return $initial . '.' . $last;
        }
        return '';
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────
    private function departmentIdByName(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            return 0;
        }
        $row = db_fetch_one('SELECT id FROM departments WHERE LOWER(name) = ? LIMIT 1', 's', [strtolower($name)]);
        return $row ? (int) $row['id'] : 0;
    }
}
