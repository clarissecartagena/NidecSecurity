<?php

if (!defined('REPORT_SHARE_TOKEN_BYTES')) {
    define('REPORT_SHARE_TOKEN_BYTES', 24);
}
if (!defined('REPORT_SHARE_TOKEN_HEX_LENGTH')) {
    define('REPORT_SHARE_TOKEN_HEX_LENGTH', REPORT_SHARE_TOKEN_BYTES * 2);
}
if (!defined('REPORT_SHARE_MIN_TTL_SECONDS')) {
    define('REPORT_SHARE_MIN_TTL_SECONDS', 300); // 5 minutes minimum validity
}
if (!defined('REPORT_SHARE_MAX_TOKENS')) {
    define('REPORT_SHARE_MAX_TOKENS', 5000); // keep storage bounded
}

function report_share_token_store_path(): string
{
    $storageDir = dirname(__DIR__) . '/storage';
    if (!is_dir($storageDir)) {
        if (!mkdir($storageDir, 0700, true) && !is_dir($storageDir)) {
            throw new RuntimeException('Unable to create report share token storage directory.');
        }
    }
    $path = $storageDir . '/report_share_tokens.json';
    if (is_file($path)) {
        chmod($path, 0600);
    }
    return $path;
}

function report_share_read_store(): array
{
    $path = report_share_token_store_path();
    if (!is_file($path)) {
        return [];
    }

    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function report_share_write_store(array $store): void
{
    $path = report_share_token_store_path();
    $result = file_put_contents($path, json_encode($store, JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($result === false) {
        throw new RuntimeException('Unable to persist report share tokens.');
    }
    chmod($path, 0600);
}

function report_share_read_store_locked($handle): array
{
    rewind($handle);
    $raw = stream_get_contents($handle);
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function report_share_write_store_locked($handle, array $store): void
{
    $json = json_encode($store, JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Unable to encode report share tokens.');
    }
    rewind($handle);
    if (!ftruncate($handle, 0)) {
        throw new RuntimeException('Unable to truncate report share token store.');
    }
    if (fwrite($handle, $json) === false) {
        throw new RuntimeException('Unable to write report share token store.');
    }
    fflush($handle);
    $meta = stream_get_meta_data($handle);
    $path = (string) ($meta['uri'] ?? '');
    if ($path !== '') {
        chmod($path, 0600);
    }
}

function report_share_prune_store(array $store): array
{
    $now = time();
    foreach ($store as $token => $entry) {
        $expiresAt = (int) ($entry['expires_at'] ?? 0);
        $reportNo = (string) ($entry['report_no'] ?? '');
        if ($expiresAt <= $now || $reportNo === '') {
            unset($store[$token]);
        }
    }
    return $store;
}

function report_share_generate_token(string $reportNo, int $ttlSeconds = 604800): string
{
    $reportNo = trim($reportNo);
    if ($reportNo === '') {
        throw new InvalidArgumentException('Report number is required.');
    }

    $path = report_share_token_store_path();
    // c+ = read/write, create if missing, keep existing content intact.
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Unable to open report share token store.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Unable to lock report share token store.');
        }

        $store = report_share_prune_store(report_share_read_store_locked($handle));
        $token = bin2hex(random_bytes(REPORT_SHARE_TOKEN_BYTES));
        $now = time();

        $store[$token] = [
            'report_no' => $reportNo,
            'created_at' => $now,
            'expires_at' => $now + max(REPORT_SHARE_MIN_TTL_SECONDS, $ttlSeconds),
        ];

        // Keep storage bounded: if many links are generated, keep the most recent limit
        // (by expiry) and drop older entries to avoid unbounded growth.
        if (count($store) > REPORT_SHARE_MAX_TOKENS) {
            uasort($store, static function ($a, $b): int {
                return ($a['expires_at'] ?? 0) <=> ($b['expires_at'] ?? 0);
            });
            $store = array_slice($store, -REPORT_SHARE_MAX_TOKENS, null, true);
        }

        report_share_write_store_locked($handle, $store);
        flock($handle, LOCK_UN);
        fclose($handle);
        return $token;
    } catch (Throwable $e) {
        flock($handle, LOCK_UN);
        fclose($handle);
        throw $e;
    }
}

function report_share_validate_token(string $token, ?string $expectedReportNo = null): ?array
{
    if (!preg_match('/^[a-f0-9]{' . REPORT_SHARE_TOKEN_HEX_LENGTH . '}$/', $token)) {
        return null;
    }

    $path = report_share_token_store_path();
    // c+ = read/write, create if missing, keep existing content intact.
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Unable to open report share token store.');
    }
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        throw new RuntimeException('Unable to lock report share token store.');
    }

    $store = report_share_read_store_locked($handle);
    $entry = is_array($store[$token] ?? null) ? $store[$token] : null;
    if (!$entry) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return null;
    }

    $expiresAt = (int) ($entry['expires_at'] ?? 0);
    $reportNo = trim((string) ($entry['report_no'] ?? ''));
    if ($expiresAt <= time() || $reportNo === '') {
        unset($store[$token]);
        report_share_write_store_locked($handle, $store);
        flock($handle, LOCK_UN);
        fclose($handle);
        return null;
    }

    if ($expectedReportNo !== null && $reportNo !== (string) $expectedReportNo) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return null;
    }

    flock($handle, LOCK_UN);
    fclose($handle);
    return [
        'report_no' => $reportNo,
        'expires_at' => $expiresAt,
        'created_at' => (int) ($entry['created_at'] ?? 0),
    ];
}
