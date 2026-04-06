<?php
/**
 * Sample consumer sync script (run on coworker system).
 *
 * Purpose:
 * - Pull full snapshot from Nidec reports sync API
 * - Upsert reports into local mirror table
 * - Delete local rows that no longer exist in source (handles source-side deletions)
 *
 * Run:
 *   php sample_consumer_reports_sync.php
 *
 * Configure these values before use.
 */

// ====== SOURCE API (your system) ======
$sourceSyncUrl = getenv('SOURCE_SYNC_URL') ?: 'http://localhost/NidecSecurity/public/api/reports_sync.php';
$sourceApiKey = getenv('SOURCE_API_KEY') ?: 'nidec-sync-demo-key';
$entityFilter = getenv('SOURCE_ENTITY') ?: ''; // optional: NCFL / NPFL / empty

// ====== COWORKER DB (destination mirror) ======
$dbHost = getenv('MIRROR_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('MIRROR_DB_PORT') ?: '3306';
$dbName = getenv('MIRROR_DB_NAME') ?: 'coworker_system';
$dbUser = getenv('MIRROR_DB_USER') ?: 'root';
$dbPass = getenv('MIRROR_DB_PASS') ?: '';

function fail(string $message, int $code = 1): void
{
    fwrite(STDERR, "[SYNC ERROR] {$message}\n");
    exit($code);
}

function fetchSyncPayload(string $url, string $apiKey, string $entity = ''): array
{
    if ($entity !== '') {
        $sep = str_contains($url, '?') ? '&' : '?';
        $url .= $sep . 'entity=' . rawurlencode($entity);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-API-Key: ' . $apiKey],
    ]);

    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        fail('Failed to call source API: ' . $err);
    }

    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        fail('Source API returned HTTP ' . $httpCode . ' | body: ' . substr((string) $raw, 0, 500));
    }

    $json = json_decode((string) $raw, true);
    if (!is_array($json)) {
        fail('Source API returned invalid JSON');
    }

    if (!($json['success'] ?? false)) {
        fail('Source API returned unsuccessful response: ' . json_encode($json, JSON_UNESCAPED_SLASHES));
    }

    if (!isset($json['reports']) || !is_array($json['reports'])) {
        fail('Source API payload missing reports array');
    }

    return $json;
}

function ensureMirrorTable(PDO $pdo): void
{
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS mirrored_reports (
      report_no            VARCHAR(30) NOT NULL,
      source_report_id     INT NULL,
      subject              VARCHAR(255) NULL,
      category             VARCHAR(120) NULL,
      location             VARCHAR(255) NULL,
      severity             VARCHAR(20) NULL,
      entity               VARCHAR(10) NULL,
      department_id        INT NULL,
      department_name      VARCHAR(150) NULL,
      status               VARCHAR(80) NULL,
      current_reviewer     VARCHAR(40) NULL,
      submitted_at         DATETIME NULL,
      updated_at           DATETIME NULL,
      pdf_template         VARCHAR(20) NULL,
      pdf_url              TEXT NULL,
      pdf_internal_url     TEXT NULL,
      pdf_external_url     TEXT NULL,
      report_json          LONGTEXT NULL,
      synced_at            DATETIME NOT NULL,
      PRIMARY KEY (report_no),
      KEY idx_mirror_status (status),
      KEY idx_mirror_entity (entity),
      KEY idx_mirror_updated (updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    SQL;

    $pdo->exec($sql);
}

function toNullableDateTime(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }
    $str = trim((string) $value);
    return $str === '' ? null : $str;
}

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fail('DB connection failed: ' . $e->getMessage());
}

$payload = fetchSyncPayload($sourceSyncUrl, $sourceApiKey, $entityFilter);
$reports = $payload['reports'];
$syncAt = date('Y-m-d H:i:s');

ensureMirrorTable($pdo);

$existingRows = $pdo->query('SELECT report_no, updated_at FROM mirrored_reports')->fetchAll();
$existingMap = [];
foreach ($existingRows as $row) {
    $existingMap[(string) $row['report_no']] = (string) ($row['updated_at'] ?? '');
}

$inserted = 0;
$updated = 0;
$unchanged = 0;

$upsertSql = <<<SQL
INSERT INTO mirrored_reports (
  report_no, source_report_id, subject, category, location, severity, entity,
  department_id, department_name, status, current_reviewer,
  submitted_at, updated_at,
  pdf_template, pdf_url, pdf_internal_url, pdf_external_url,
  report_json, synced_at
) VALUES (
  :report_no, :source_report_id, :subject, :category, :location, :severity, :entity,
  :department_id, :department_name, :status, :current_reviewer,
  :submitted_at, :updated_at,
  :pdf_template, :pdf_url, :pdf_internal_url, :pdf_external_url,
  :report_json, :synced_at
)
ON DUPLICATE KEY UPDATE
  source_report_id = VALUES(source_report_id),
  subject = VALUES(subject),
  category = VALUES(category),
  location = VALUES(location),
  severity = VALUES(severity),
  entity = VALUES(entity),
  department_id = VALUES(department_id),
  department_name = VALUES(department_name),
  status = VALUES(status),
  current_reviewer = VALUES(current_reviewer),
  submitted_at = VALUES(submitted_at),
  updated_at = VALUES(updated_at),
  pdf_template = VALUES(pdf_template),
  pdf_url = VALUES(pdf_url),
  pdf_internal_url = VALUES(pdf_internal_url),
  pdf_external_url = VALUES(pdf_external_url),
  report_json = VALUES(report_json),
  synced_at = VALUES(synced_at)
SQL;

$upsertStmt = $pdo->prepare($upsertSql);

try {
    $pdo->beginTransaction();

    $pdo->exec('DROP TEMPORARY TABLE IF EXISTS tmp_sync_report_keys');
    $pdo->exec('CREATE TEMPORARY TABLE tmp_sync_report_keys (report_no VARCHAR(30) PRIMARY KEY) ENGINE=MEMORY');
    $tmpInsertStmt = $pdo->prepare('INSERT INTO tmp_sync_report_keys (report_no) VALUES (?)');

    foreach ($reports as $report) {
        $reportNo = (string) ($report['report_no'] ?? '');
        if ($reportNo === '') {
            continue;
        }

        $incomingUpdatedAt = (string) ($report['updated_at'] ?? '');
        if (!array_key_exists($reportNo, $existingMap)) {
            $inserted++;
        } elseif ($existingMap[$reportNo] !== $incomingUpdatedAt) {
            $updated++;
        } else {
            $unchanged++;
        }

        $upsertStmt->execute([
            ':report_no' => $reportNo,
            ':source_report_id' => isset($report['id']) ? (int) $report['id'] : null,
            ':subject' => (string) ($report['subject'] ?? ''),
            ':category' => (string) ($report['category'] ?? ''),
            ':location' => (string) ($report['location'] ?? ''),
            ':severity' => (string) ($report['severity'] ?? ''),
            ':entity' => (string) ($report['entity'] ?? ''),
            ':department_id' => isset($report['department_id']) ? (int) $report['department_id'] : null,
            ':department_name' => (string) ($report['department_name'] ?? ''),
            ':status' => (string) ($report['status'] ?? ''),
            ':current_reviewer' => (string) ($report['current_reviewer'] ?? ''),
            ':submitted_at' => toNullableDateTime($report['submitted_at'] ?? null),
            ':updated_at' => toNullableDateTime($report['updated_at'] ?? null),
            ':pdf_template' => (string) ($report['pdf_template'] ?? ''),
            ':pdf_url' => (string) ($report['pdf_url'] ?? ''),
            ':pdf_internal_url' => (string) ($report['pdf_internal_url'] ?? ''),
            ':pdf_external_url' => (string) ($report['pdf_external_url'] ?? ''),
            ':report_json' => json_encode($report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':synced_at' => $syncAt,
        ]);

        $tmpInsertStmt->execute([$reportNo]);
    }

    $deleteStmt = $pdo->prepare(
        'DELETE mr FROM mirrored_reports mr LEFT JOIN tmp_sync_report_keys t ON t.report_no = mr.report_no WHERE t.report_no IS NULL',
    );
    $deleteStmt->execute();
    $deleted = $deleteStmt->rowCount();

    $pdo->commit();

    echo "[SYNC OK]\n";
    echo 'snapshot_checksum: ' . (string) ($payload['snapshot_checksum'] ?? '') . "\n";
    echo 'generated_at: ' . (string) ($payload['generated_at'] ?? '') . "\n";
    echo 'total_in_snapshot: ' . count($reports) . "\n";
    echo "inserted: {$inserted}\n";
    echo "updated: {$updated}\n";
    echo "unchanged: {$unchanged}\n";
    echo "deleted: {$deleted}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail('Sync transaction failed: ' . $e->getMessage());
}
