<?php

/**
 * EmployeeSyncService
 *
 * Implements "Sync on Request" logic for the local_employees table.
 *
 * Workflow
 * ────────
 * 1. Call the company Employee API with the given employee_no.
 *    Expected API response: {"status":"success","results":{"employee_no":"…","name":"…","entity":"…"}}
 * 2. Look up the employee_no in the local_employees table.
 * 3a. If found AND name/entity differ   → UPDATE the local record.
 * 3b. If not found                      → INSERT a new record.
 * 3c. If found AND data is identical    → no-op.
 * 4. Return a result describing what happened.
 *
 * Database access
 * ───────────────
 * Intentionally uses mysqli with prepared statements (not PDO) as required
 * by the feature specification.  The host/user/pass/db values are sourced
 * from the same constants defined in config/database.php so that only one
 * place needs to be updated when credentials change.
 *
 * @see public/api/employee_sync.php  (HTTP entry point)
 * @see config/api.php                (API base URL and timeouts)
 * @see config/database.php           (DB_HOST, DB_USER, DB_PASS, DB_NAME)
 */

require_once __DIR__ . '/../../config/api.php';
require_once __DIR__ . '/../../config/database.php';

class EmployeeSyncService
{
    // ── Result constants ───────────────────────────────────────────────────

    const RESULT_INSERTED = 'inserted';
    const RESULT_UPDATED = 'updated';
    const RESULT_NO_CHANGES = 'no_changes';

    // ──────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Sync a single employee by employee_no.
     *
     * @param  string $employeeNo  The employee number to synchronise.
     * @return array{
     *   success: bool,
     *   result:  ?string,
     *   message: string,
     *   error:   ?string
     * }
     */
    public function sync(string $employeeNo): array
    {
        $employeeNo = trim($employeeNo);

        if ($employeeNo === '') {
            return $this->failure('employee_no is required.');
        }

        // 1. Fetch from the API.
        $apiResult = $this->fetchFromApi($employeeNo);
        if (!$apiResult['success']) {
            return $this->failure($apiResult['error']);
        }

        $apiData = $apiResult['data'];

        // 2. Open a mysqli connection.
        $conn = $this->openConnection();
        if ($conn === null) {
            return $this->failure('Unable to connect to the local database.');
        }

        try {
            // 3. Check for an existing local record.
            $local = $this->findLocal($conn, $employeeNo);

            if ($local === null) {
                // Employee does not exist locally → INSERT.
                $this->insertEmployee($conn, $apiData['employee_no'], $apiData['name'], $apiData['entity']);
                return [
                    'success' => true,
                    'result' => self::RESULT_INSERTED,
                    'message' => 'Database Synchronized',
                    'error' => null,
                ];
            }

            // Employee exists — check whether name or entity has changed.
            $nameChanged = $local['name'] !== $apiData['name'];
            $entityChanged = $local['entity'] !== $apiData['entity'];

            if ($nameChanged || $entityChanged) {
                // Data differs → UPDATE.
                $this->updateEmployee($conn, $apiData['name'], $apiData['entity'], $employeeNo);
                return [
                    'success' => true,
                    'result' => self::RESULT_UPDATED,
                    'message' => 'Database Synchronized',
                    'error' => null,
                ];
            }

            // Data is identical → no-op.
            return [
                'success' => true,
                'result' => self::RESULT_NO_CHANGES,
                'message' => 'No changes detected',
                'error' => null,
            ];
        } catch (RuntimeException $e) {
            return $this->failure('A database error occurred. Please try again.');
        } finally {
            mysqli_close($conn);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers — API
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Call the company Employee API and return a normalised result.
     *
     * @return array{success: bool, data: ?array<string,string>, error: ?string}
     */
    private function fetchFromApi(string $employeeNo): array
    {
        if (!extension_loaded('curl')) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'The cURL PHP extension is required but is not enabled.',
            ];
        }

        // Use mock API as fallback in development when the company API is unreachable.
        $baseUrl = COMPANY_API_BASE_URL;
        if (API_ENV !== 'production' && !$this->isTcpReachable(COMPANY_API_BASE_URL)) {
            $baseUrl = MOCK_API_BASE_URL;
        }
        $url = $baseUrl . '?' . http_build_query(['employee_no' => $employeeNo]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => API_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => API_READ_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Requested-By: NidecSecuritySystem'],
        ]);

        $raw = curl_exec($ch);
        $curlErrno = (int) curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrno !== 0 || $raw === false) {
            return ['success' => false, 'data' => null, 'error' => 'Unable to connect to the employee API.'];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return ['success' => false, 'data' => null, 'error' => "Employee API returned HTTP {$httpCode}."];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Employee API returned an unrecognised response format.',
            ];
        }

        // Expected format: {"status":"success","results":{"employee_no":"…","name":"…","entity":"…"}}
        $status = (string) ($decoded['status'] ?? '');
        $results = $decoded['results'] ?? null;

        if ($status !== 'success' || !is_array($results)) {
            $apiMsg = (string) ($decoded['message'] ?? ($decoded['error'] ?? 'Employee not found or API error.'));
            return ['success' => false, 'data' => null, 'error' => $apiMsg];
        }

        $empNo = trim((string) ($results['employee_no'] ?? ''));
        $name = trim((string) ($results['name'] ?? ''));
        $entity = strtoupper(trim((string) ($results['entity'] ?? '')));

        if ($empNo === '' || $name === '') {
            return [
                'success' => false,
                'data' => null,
                'error' => 'API response is missing required fields (employee_no, name).',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'employee_no' => $empNo,
                'name' => $name,
                'entity' => $entity,
            ],
            'error' => null,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers — Database (mysqli)
    // ──────────────────────────────────────────────────────────────────────

    /** Open and return a mysqli connection, or null on failure. */
    private function openConnection(): ?mysqli
    {
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if ($conn === false || mysqli_connect_errno() !== 0) {
            return null;
        }

        mysqli_set_charset($conn, 'utf8mb4');
        return $conn;
    }

    /**
     * Find an employee in the local_employees table by employee_no.
     *
     * @return array<string,string>|null  Associative row or null if not found.
     * @throws RuntimeException  On database prepare or execute failure.
     */
    private function findLocal(mysqli $conn, string $employeeNo): ?array
    {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT employee_no, name, entity FROM local_employees WHERE employee_no = ? LIMIT 1',
        );

        if ($stmt === false) {
            throw new RuntimeException('DB prepare failed (findLocal): ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 's', $employeeNo);

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException('DB execute failed (findLocal): ' . $err);
        }

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        mysqli_stmt_close($stmt);

        return $row ?: null;
    }

    /**
     * Insert a new employee record into local_employees.
     *
     * @throws RuntimeException  On database prepare or execute failure.
     */
    private function insertEmployee(mysqli $conn, string $employeeNo, string $name, string $entity): void
    {
        $stmt = mysqli_prepare($conn, 'INSERT INTO local_employees (employee_no, name, entity) VALUES (?, ?, ?)');

        if ($stmt === false) {
            throw new RuntimeException('DB prepare failed (insertEmployee): ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'sss', $employeeNo, $name, $entity);

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException('DB execute failed (insertEmployee): ' . $err);
        }

        mysqli_stmt_close($stmt);
    }

    /**
     * Update an existing employee record in local_employees.
     *
     * @throws RuntimeException  On database prepare or execute failure.
     */
    private function updateEmployee(mysqli $conn, string $name, string $entity, string $employeeNo): void
    {
        $stmt = mysqli_prepare($conn, 'UPDATE local_employees SET name = ?, entity = ? WHERE employee_no = ?');

        if ($stmt === false) {
            throw new RuntimeException('DB prepare failed (updateEmployee): ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'sss', $name, $entity, $employeeNo);

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException('DB execute failed (updateEmployee): ' . $err);
        }

        mysqli_stmt_close($stmt);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers — Result builders
    // ──────────────────────────────────────────────────────────────────────

    /** Build a failure result array. */
    private function failure(string $error): array
    {
        return [
            'success' => false,
            'result' => null,
            'message' => $error,
            'error' => $error,
        ];
    }
    /**
     * Probe TCP reachability of a base URL (2-second hard timeout).
     * Used to decide whether to fall back to the mock API in development.
     */
    private function isTcpReachable(string $url): bool
    {
        $parts = parse_url($url);
        $host = (string) ($parts['host'] ?? '');
        $port = (int) ($parts['port'] ?? (($parts['scheme'] ?? 'http') === 'https' ? 443 : 80));
        if ($host === '') {
            return false;
        }
        $sock = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($sock !== false) {
            fclose($sock);
            return true;
        }
        return false;
    }
}
