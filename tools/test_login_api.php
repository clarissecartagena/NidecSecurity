<?php
/**
 * Login API Test Script
 *
 * Tests the company corporate login API (DAIRS.php) to verify it is
 * reachable and responding correctly.
 *
 * ── HOW TO RUN ────────────────────────────────────────────────────────────
 *   From the project root directory, run:
 *
 *       php tools/test_login_api.php
 *
 *   To test with real credentials:
 *
 *       php tools/test_login_api.php B30-XXXXXXX yourpassword
 *
 * ── WHAT IT CHECKS ────────────────────────────────────────────────────────
 *   1. Whether the company login API server is TCP-reachable.
 *   2. Whether the AES-128-CTR encryption/decryption round-trip works.
 *   3. (Optional) Whether real credentials authenticate successfully.
 *
 * ── HOW TO KNOW IF IT IS WORKING ─────────────────────────────────────────
 *   PASS  – Each check prints "[PASS]" in green.
 *   FAIL  – A failed check prints "[FAIL]" in red with an explanation.
 *   If all checks pass and you supplied credentials, you will see the
 *   employee name returned by the API, confirming the full auth flow works.
 * ─────────────────────────────────────────────────────────────────────────
 */

// Resolve project root and load API config.
$root = dirname(__DIR__);
require_once $root . '/config/api.php';
require_once $root . '/app/api_clients/LoginApiClient.php';

// ── Terminal colour helpers ────────────────────────────────────────────────
$isCli = PHP_SAPI === 'cli';

function pass(string $msg): void
{
    global $isCli;
    $label = $isCli ? "\033[32m[PASS]\033[0m" : '[PASS]';
    echo $label . ' ' . $msg . PHP_EOL;
}

function fail(string $msg): void
{
    global $isCli;
    $label = $isCli ? "\033[31m[FAIL]\033[0m" : '[FAIL]';
    echo $label . ' ' . $msg . PHP_EOL;
}

function info(string $msg): void
{
    global $isCli;
    $label = $isCli ? "\033[36m[INFO]\033[0m" : '[INFO]';
    echo $label . ' ' . $msg . PHP_EOL;
}

function section(string $title): void
{
    echo PHP_EOL . str_repeat('─', 60) . PHP_EOL;
    echo "  $title" . PHP_EOL;
    echo str_repeat('─', 60) . PHP_EOL;
}

// ── Optional credentials from CLI args ────────────────────────────────────
$username = $argv[1] ?? '';
$password = $argv[2] ?? '';

// ── Check 1: Configuration ─────────────────────────────────────────────────
section('Check 1: Configuration');
info('Company Login URL : ' . COMPANY_LOGIN_URL);
info('Mock Login URL    : ' . MOCK_LOGIN_URL);
info('Environment       : ' . API_ENV);
info(
    'Encryption Key    : ' .
        substr(API_LOGIN_ENCRYPTION_KEY, 0, 8) .
        str_repeat('*', max(0, strlen(API_LOGIN_ENCRYPTION_KEY) - 8)),
);
info(
    'Encryption IV     : ' .
        substr(API_LOGIN_ENCRYPTION_IV, 0, 4) .
        str_repeat('*', max(0, strlen(API_LOGIN_ENCRYPTION_IV) - 4)),
);

$keyLen = strlen(API_LOGIN_ENCRYPTION_KEY);
$ivLen = strlen(API_LOGIN_ENCRYPTION_IV);

if ($keyLen >= 16) {
    pass("Encryption key length is valid ({$keyLen} bytes).");
} else {
    fail("Encryption key is too short: {$keyLen} bytes (minimum 16 required for AES-128-CTR).");
}

if ($ivLen === 16) {
    pass('Encryption IV is exactly 16 bytes.');
} else {
    fail("Encryption IV length is {$ivLen} bytes — must be exactly 16 bytes for AES-128-CTR.");
}

// ── Check 2: OpenSSL extension ─────────────────────────────────────────────
section('Check 2: OpenSSL extension');
if (extension_loaded('openssl')) {
    pass('The openssl extension is loaded.');
} else {
    fail('The openssl extension is NOT loaded. Login encryption will not work.');
}

// ── Check 3: cURL extension ────────────────────────────────────────────────
section('Check 3: cURL extension');
if (extension_loaded('curl')) {
    pass('The curl extension is loaded.');
} else {
    fail('The curl extension is NOT loaded. API HTTP calls will not work.');
}

// ── Check 4: Encryption round-trip ────────────────────────────────────────
section('Check 4: AES-128-CTR encryption round-trip');
$testPayload = ['username' => 'test_user', 'password' => 'test_pass', 'entity' => 'NCFL'];
$testJson = json_encode($testPayload);

$encrypted = openssl_encrypt($testJson, 'AES-128-CTR', API_LOGIN_ENCRYPTION_KEY, 0, API_LOGIN_ENCRYPTION_IV);
if ($encrypted === false) {
    fail('openssl_encrypt returned false — check your PHP OpenSSL configuration.');
} else {
    pass('Encryption succeeded.');
    $decrypted = openssl_decrypt($encrypted, 'AES-128-CTR', API_LOGIN_ENCRYPTION_KEY, 0, API_LOGIN_ENCRYPTION_IV);
    if ($decrypted === false) {
        fail('openssl_decrypt returned false — decryption failed unexpectedly.');
    } elseif ($decrypted === $testJson) {
        pass('Round-trip encryption/decryption matches original payload.');
    } else {
        fail("Decrypted value does not match original.\n  Original : $testJson\n  Decrypted: $decrypted");
    }
}

// ── Check 5: TCP reachability ──────────────────────────────────────────────
section('Check 5: TCP reachability of company login server');
$parsed = parse_url(COMPANY_LOGIN_URL);
$host = (string) ($parsed['host'] ?? '');
$port = (int) ($parsed['port'] ?? (strpos(COMPANY_LOGIN_URL, 'https') === 0 ? 443 : 80));

if ($host === '') {
    fail('Could not parse host from COMPANY_LOGIN_URL: ' . COMPANY_LOGIN_URL);
} else {
    info("Testing TCP connection to {$host}:{$port} (2-second timeout) …");
    $sock = @fsockopen($host, $port, $errno, $errstr, 2);
    if ($sock !== false) {
        fclose($sock);
        pass("Company login server is reachable at {$host}:{$port}.");
        $serverReachable = true;
    } else {
        fail("Cannot reach company login server at {$host}:{$port} — {$errstr} (errno={$errno}).");
        info('The system will fall back to the mock login endpoint in development mode.');
        $serverReachable = false;
    }
}

// ── Check 6: LoginApiClient construction ──────────────────────────────────
section('Check 6: LoginApiClient construction');
try {
    $client = new LoginApiClient();
    $usingMock = $client->isUsingMock();
    if ($usingMock) {
        info('LoginApiClient is using the MOCK endpoint: ' . MOCK_LOGIN_URL);
        pass('LoginApiClient constructed successfully (mock mode).');
    } else {
        pass('LoginApiClient constructed successfully (company API mode).');
    }
} catch (Throwable $e) {
    fail('LoginApiClient constructor threw an exception: ' . $e->getMessage());
    $client = null;
}

// ── Check 7: Credential authentication (only if supplied) ─────────────────
section('Check 7: Credential authentication');
if ($username === '' || $password === '') {
    info('No credentials supplied — skipping live authentication test.');
    info('To test with real credentials, run:');
    info('  php tools/test_login_api.php <username> <password>');
} elseif (!isset($client)) {
    fail('Skipping authentication test — LoginApiClient could not be constructed.');
} else {
    info("Attempting to authenticate as: {$username}");
    $result = $client->authenticate($username, $password);

    if ($result['success']) {
        pass('Authentication SUCCEEDED.');
        info("  Employee ID : {$result['employee_id']}");
        info("  Name        : {$result['name']}");
        info('  Using mock  : ' . ($result['using_mock'] ? 'yes' : 'no'));
    } else {
        fail("Authentication failed: {$result['error']}");
        info('  Using mock  : ' . ($result['using_mock'] ? 'yes' : 'no'));
    }
}

// ── Summary ────────────────────────────────────────────────────────────────
section('Summary');
echo PHP_EOL;
echo '  All checks above marked [PASS] indicate the login API stack is ready.' . PHP_EOL;
echo '  Any [FAIL] item must be resolved before the login flow will work.' . PHP_EOL;
echo PHP_EOL;
