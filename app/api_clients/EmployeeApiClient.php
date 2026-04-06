<?php

/**
 * EmployeeApiClient
 *
 * Low-level HTTP client for the company Employee API.
 *
 * Responsibilities
 * ────────────────
 * • Resolve which base URL to use (company API or local mock).
 * • Execute GET requests via cURL with configurable timeouts.
 * • Normalise raw API responses into a predictable flat shape.
 * • Cache responses per request-lifecycle to avoid duplicate HTTP calls.
 *
 * Fallback logic
 * ──────────────
 * 1. A fast TCP probe (2-second max) checks if the company server is reachable.
 * 2. If reachable → use COMPANY_API_BASE_URL.
 * 3. If unreachable AND API_ENV === 'development' → use MOCK_API_BASE_URL.
 * 4. If unreachable AND API_ENV === 'production'  → keep company URL; the
 *    subsequent HTTP request will fail gracefully and return an error result.
 *    The mock API is NEVER used in production.
 *
 * This class is intentionally infrastructure-only — no business logic here.
 * Use EmployeeService for validation and higher-level operations.
 *
 * Field-name mapping
 * ──────────────────
 * normalizeEmployee() maps raw API field names to a canonical set.
 * If the company API uses different field names, update the key aliases
 * in that method — nowhere else in the system needs to change.
 *
 * @see EmployeeService
 * @see config/api.php
 */

require_once __DIR__ . '/../../config/api.php';

class EmployeeApiClient
{
    /** Resolved base URL (company or mock). */
    private string $baseUrl;

    /** True when the mock API is active. */
    private bool $usingMock = false;

    /**
     * Simple per-request in-memory cache.
     * Prevents duplicate HTTP calls within the same PHP request.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $cache = [];

    public function __construct()
    {
        $this->baseUrl = $this->resolveBaseUrl();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────

    /** Returns true when the local mock API is being used. */
    public function isUsingMock(): bool
    {
        return $this->usingMock;
    }

    /** Returns the resolved base URL (company API or mock) that is currently in use. */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Search employees by free-text query (name fragment or employee ID).
     *
     * @return array{success: bool, data: list<array<string,string>>, error: ?string}
     */
    public function searchEmployees(string $query): array
    {
        $query = trim($query);
        // Use 'q' but also try adding a wildcard if your company API supports it
        // Example: $query . '*'
        $url = $this->buildUrl('get_employees.php', ['search' => $query]);
        $result = $this->get($url);

        if ($result['success']) {
            $result['data'] = array_map([$this, 'normalizeEmployee'], $result['data']);
        }
        return $result;
    }
    /**
     * Fetch a single employee by exact employee_id.
     *
     * @return array{success: bool, data: ?array<string,string>, error: ?string}
     */
    public function getEmployeeById(string $employeeId): array
    {
        $employeeId = trim($employeeId);
        if ($employeeId === '') {
            return ['success' => false, 'data' => null, 'error' => 'Employee ID is required.'];
        }

        $cacheKey = 'emp:' . $employeeId;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $url = $this->buildUrl('get_employees.php', ['employee_id' => $employeeId]);
        $result = $this->get($url);

        if ($result['success']) {
            $list = $result['data'];
            if (empty($list)) {
                $result = ['success' => false, 'data' => null, 'error' => 'Employee not found.'];
            } else {
                // Some company APIs return the full employee list without server-side
                // filtering (ignoring the employee_id query parameter). Search through
                // all returned records to find the one whose employee_id matches.
                $found = null;
                foreach ($list as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $normalized = $this->normalizeEmployee($item);
                    if ($normalized['employee_id'] === (string) $employeeId) {
                        $found = $normalized;
                        break;
                    }
                }

                if ($found !== null) {
                    $result['data'] = $found;
                } elseif (count($list) === 1) {
                    // The API returned exactly one record — it filtered server-side.
                    // Return it regardless of whether the employee_id field could be
                    // read (e.g. the API uses a non-standard field name for the ID).
                    $raw = is_array($list[0] ?? null) ? $list[0] : $list;
                    $result['data'] = $this->normalizeEmployee($raw);
                } else {
                    // Multiple records were returned but none matched the requested ID.
                    // The employee does not exist in the API data.
                    $result = ['success' => false, 'data' => null, 'error' => 'Employee not found.'];
                }
            }
        } else {
            $result['data'] = null;
        }

        $this->cache[$cacheKey] = $result;
        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Determine which base URL to use.
     *
     * TCP probe is intentionally short (2 s) so that the fall-through to the
     * mock happens quickly in a development context instead of hanging the page.
     */
    private function resolveBaseUrl(): string
    {
        if ($this->isTcpReachable(COMPANY_API_BASE_URL)) {
            $this->usingMock = false;
            return COMPANY_API_BASE_URL;
        }

        // Production: never fall back — surface the connectivity error.
        if (API_ENV === 'production') {
            $this->usingMock = false;
            return COMPANY_API_BASE_URL;
        }

        // Development: silently switch to the local mock API.
        $this->usingMock = true;
        return MOCK_API_BASE_URL;
    }

    /**
     * Build a full endpoint URL.
     *
     * When the resolved base URL already points to a PHP file (e.g.
     * "http://10.216.8.90/dummy_hris/api.php"), it IS the endpoint — appending
     * a script name like "get_employees.php" would produce a broken path such as
     * "api.php/get_employees.php" that causes the server to ignore query
     * parameters and return wrong (default) data.
     *
     * When the base URL is a directory (e.g. "http://localhost/nidec_api_mock"),
     * the script name is appended as normal.
     *
     * @param array<string, string|int> $params
     */
    private function buildUrl(string $script, array $params = []): string
    {
        // If the base URL is the company API, we need to make sure we are
        // appending the correct script for searching.
        $base = rtrim($this->baseUrl, '/');

        // If your company API is a single file like 'api.php',
        // we use that. Otherwise, we append the script name.
        if (strpos($base, '.php') === false) {
            $base .= '/' . ltrim($script, '/');
        }

        return $params !== [] ? $base . '?' . http_build_query($params) : $base;
    }

    /**
     * Probe TCP reachability of a base URL (2-second hard timeout).
     * Used once at construction to decide which endpoint to use.
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

    /**
     * Execute a GET request via cURL.
     *
     * Returns a normalised result envelope:
     *   success  – true on HTTP 2xx with valid JSON
     *   data     – raw decoded array (may be empty)
     *   error    – human-readable message when success is false
     *
     * @return array{success: bool, data: array, error: ?string}
     */
    private function get(string $url): array
    {
        if (!extension_loaded('curl')) {
            return [
                'success' => false,
                'data' => [],
                'error' => 'The cURL PHP extension is required but is not enabled on this server.',
            ];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => API_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => API_READ_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            // Only skip peer verification for localhost mock (never in production).
            CURLOPT_SSL_VERIFYPEER => !$this->usingMock,
            CURLOPT_SSL_VERIFYHOST => $this->usingMock ? 0 : 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Requested-By: NidecSecuritySystem'],
        ]);

        $raw = curl_exec($ch);
        $curlErrno = (int) curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrno !== 0 || $raw === false) {
            return [
                'success' => false,
                'data' => [],
                'error' => 'Unable to connect to the employee API. Please check network connectivity.',
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'data' => [],
                'error' => "Employee API returned HTTP {$httpCode}.",
            ];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'data' => [],
                'error' => 'Employee API returned an unrecognised response format.',
            ];
        }

        // Accept both {data: [...]} envelope and a bare array of employees.
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $employees = $decoded['data'];
        } elseif (array_key_exists(0, $decoded) || $decoded === []) {
            $employees = $decoded;
        } else {
            // Single employee object at top level (common for ID lookups).
            $employees = [$decoded];
        }

        return ['success' => true, 'data' => $employees, 'error' => null];
    }

    /**
     * Map raw API fields to a canonical, predictable shape.
     *
     * ── HOW TO ADAPT THIS ──────────────────────────────────────────────────
     * If the company API uses different field names, add the alias to the
     * corresponding line below.  Only this method needs to change.
     * ───────────────────────────────────────────────────────────────────────
     *
     * Fields used for role / entity auto-detection:
     *   section    – "HUMAN RESOURCE, GA AND COMPLIANCE" → ga_staff
     *   job_level  – "Security" (NCFL), "SEGURITY GUARD" (NPFL), "SUPPORT/PIC" → department
     *   entity     – Company entity the employee belongs to (NCFL / NPFL)
     *
     * @param  array<string, mixed> $raw
     * @return array<string, string>
     */
    private function normalizeEmployee(array $raw): array
    {
        return [
            'employee_id' =>
                (string) ($raw['employee_id'] ??
                    ($raw['emp_id'] ??
                        ($raw['id'] ?? ($raw['employee_no'] ?? ($raw['emp_no'] ?? ($raw['empno'] ?? '')))))),
            'fullname' => trim((string) ($raw['fullname'] ?? ($raw['full_name'] ?? ($raw['name'] ?? '')))),
            'department' => trim((string) ($raw['department'] ?? ($raw['dept'] ?? ($raw['department_name'] ?? '')))),
            'position' => trim((string) ($raw['position'] ?? ($raw['job_title'] ?? ($raw['title'] ?? '')))),
            'email' => strtolower(trim((string) ($raw['email'] ?? ($raw['email_address'] ?? '')))),
            // ── Additional fields for role / entity auto-detection ───────────
            'section' => trim((string) ($raw['section'] ?? '')),
            'job_level' => trim((string) ($raw['job_level'] ?? ($raw['joblevel'] ?? ($raw['job_grade'] ?? '')))),
            'entity' => strtoupper(trim((string) ($raw['entity'] ?? ($raw['company'] ?? ($raw['plant'] ?? ''))))),
        ];
    }
}
