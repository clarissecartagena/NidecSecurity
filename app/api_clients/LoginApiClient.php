<?php

require_once __DIR__ . '/../../config/api.php';

/**
 * LoginApiClient
 *
 * Handles encrypted credential exchange with the company's corporate login
 * API (DAIRS.php).  In development / when the company server is unreachable,
 * the client automatically falls back to a local mock endpoint.
 *
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │ Wire format (request / response)                                         │
 * │                                                                          │
 * │  REQUEST  — POST with application/x-www-form-urlencoded body:            │
 * │    msg=<AES-128-CTR-base64( JSON({username, password}) )>                │
 * │                                                                          │
 * │  RESPONSE — encrypted string; after AES-128-CTR decrypt + json_decode:  │
 * │    { "status": "Success", "employee_id": "B30-1234", "fullname": "..." } │
 * │    { "status": "Failed",  "message": "Invalid credentials" }            │
 * │                                                                          │
 * │  The mock endpoint uses the same cipher so local testing exercises the   │
 * │  full encryption / decryption path.                                      │
 * └──────────────────────────────────────────────────────────────────────────┘
 *
 * Encryption:  AES-128-CTR  (PHP openssl, options = 0 → base64 I/O)
 *   Key : API_LOGIN_ENCRYPTION_KEY (set via APP_LOGIN_ENC_KEY env)
 *   IV  : API_LOGIN_ENCRYPTION_IV  (16 bytes; set via APP_LOGIN_ENC_IV env)
 *   Both must be coordinated with the company API team.
 */
class LoginApiClient
{
    private string $url;
    private bool $isMock = false;

    // ── Constructor ────────────────────────────────────────────────────────

    public function __construct()
    {
        // In production the mock is never used, regardless of reachability.
        if (defined('API_ENV') && API_ENV === 'production') {
            $this->url = COMPANY_LOGIN_URL;
            $this->isMock = false;
            return;
        }

        // In development, prefer the company server; fall back to mock if it
        // is not TCP-reachable.
        $companyParsed = parse_url(COMPANY_LOGIN_URL);
        $host = $companyParsed['host'] ?? '';
        $port = (int) ($companyParsed['port'] ?? (strpos(COMPANY_LOGIN_URL, 'https') === 0 ? 443 : 80));

        if ($host !== '' && self::isTcpReachable($host, $port)) {
            $this->url = COMPANY_LOGIN_URL;
            $this->isMock = false;
        } else {
            $this->url = MOCK_LOGIN_URL;
            $this->isMock = true;
        }
    }

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Authenticate a user against the corporate login API.
     *
     * @param  string $username  The user's login name (typically employee ID).
     * @param  string $password  The user's plain-text password (encrypted
     *                           before being sent over the wire).
     * @return array {
     *   success     bool    Whether authentication succeeded.
     *   employee_id string  The canonical employee ID returned by the API.
     *   name        string  Employee display name (may be empty string).
     *   error       string  Human-readable error description on failure.
     *   using_mock  bool    True when the mock endpoint was used.
     * }
     */
    // 4. Add this helper method to the class
    private function identifyUserAttributes($username)
    {
        $prefix = substr($username, 0, 3);
        $entity = $prefix === 'B30' ? 'NCFL' : 'NPFL';
        return ['entity' => $entity];
    }

    public function authenticate(string $username, string $password): array
    {
        // 1. Identify Entity (New Logic)
        $attributes = $this->identifyUserAttributes($username);
        $entity = $attributes['entity'];

        $base = [
            'success' => false,
            'employee_id' => '',
            'name' => '',
            'error' => '',
            'using_mock' => $this->isMock,
        ];

        if ($username === '' || $password === '') {
            $base['error'] = 'Username and password are required.';
            return $base;
        }

        // Encrypt the credential bundle before sending.
        // 2. Update Payload to include 'entity'
        try {
            $payload = $this->encrypt([
                'username' => $username,
                'password' => $password,
                'entity' => $entity, // Added entity
            ]);
        } catch (\RuntimeException $e) {
            $base['error'] = 'Encryption error: ' . $e->getMessage();
            return $base;
        }

        // POST to the login API.
        $raw = $this->post($this->url, ['msg' => $payload]);
        if ($raw === null) {
            $base['error'] = 'Login service is unreachable.';
            return $base;
        }

        $decrypted = $this->decrypt($raw);

        if ($decrypted && ($decrypted['status'] ?? '') === 'Success') {
            // The names are inside a JSON string in the 'data' key
            $userData = json_decode($decrypted['data'], true);

            return [
                'success' => true,
                'employee_id' => (string) substr($username, -7), // Extraction logic from login_check
                'name' => ($userData['fname'] ?? '') . ' ' . ($userData['lname'] ?? ''),
                'error' => '',
                'using_mock' => $this->isMock,
            ];
        }

        $base['error'] = (string) ($decrypted['message'] ?? ($decrypted['error'] ?? 'Authentication failed.'));
        return $base;
    }

    /** True when the mock endpoint was selected at construction time. */
    public function isUsingMock(): bool
    {
        return $this->isMock;
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * AES-128-CTR encrypt an associative array and return the base64 result.
     *
     * Matches the Encrypt::encrypt() method used by the DAIRS mock API.
     * openssl options = 0 means PHP handles base64 encoding automatically.
     *
     * @param  array  $data  Associative array to encode as JSON then encrypt.
     * @return string        Base64-encoded ciphertext ready to POST.
     * @throws \RuntimeException on OpenSSL failure.
     */
    private function encrypt(array $data): string
    {
        $result = openssl_encrypt(
            json_encode($data),
            'AES-128-CTR',
            API_LOGIN_ENCRYPTION_KEY,
            0,
            API_LOGIN_ENCRYPTION_IV,
        );

        if ($result === false) {
            throw new \RuntimeException('openssl_encrypt failed.');
        }

        return $result;
    }

    /**
     * AES-128-CTR decrypt and json_decode a response string.
     *
     * Matches the Encrypt::decrypt() method used by the DAIRS mock API.
     * Returns null (not an array) when decryption or JSON parsing fails.
     *
     * @param  string     $ciphertext  Base64-encoded ciphertext from the API.
     * @return array|null              Decoded response array, or null on failure.
     */
    private function decrypt(string $ciphertext): ?array
    {
        $plain = openssl_decrypt($ciphertext, 'AES-128-CTR', API_LOGIN_ENCRYPTION_KEY, 0, API_LOGIN_ENCRYPTION_IV);

        if ($plain === false) {
            return null;
        }

        $decoded = json_decode($plain, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * cURL POST helper.  Returns the response body string, or null on
     * transport / timeout errors.
     *
     * @param  string   $url
     * @param  string[] $fields  Associative array of POST fields.
     * @return string|null
     */
    private function post(string $url, array $fields): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_CONNECTTIMEOUT => defined('API_CONNECT_TIMEOUT') ? API_CONNECT_TIMEOUT : 3,
            CURLOPT_TIMEOUT => defined('API_READ_TIMEOUT') ? API_READ_TIMEOUT : 8,
            CURLOPT_FAILONERROR => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== CURLE_OK || $body === false || $body === '') {
            return null;
        }

        return (string) $body;
    }

    /**
     * TCP probe — returns true only if a TCP connection to $host:$port can be
     * opened within 2 seconds.  Used to decide whether to fall back to mock.
     */
    private static function isTcpReachable(string $host, int $port): bool
    {
        if ($host === '' || $port <= 0) {
            return false;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($socket === false) {
            return false;
        }

        fclose($socket);
        return true;
    }
}
