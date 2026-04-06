# Reports Sync API (Cross-System)

This API lets another system fetch a full snapshot of reports from this system and mirror them.

## Endpoint

- `GET /public/api/reports_sync.php`

## Authentication

Use the shared API key configured by `APP_REPORTS_SYNC_API_KEY` (or default `nidec-sync-demo-key`).

Send it as one of:

- Header: `X-API-Key: <key>`
- Header: `Authorization: Bearer <key>`
- Query: `?api_key=<key>`

## Snapshot Behavior (handles deletions)

Response field `sync_mode` is `snapshot_full_replace`.

Meaning for coworker system:

1. Fetch full `reports` array.
2. Replace local mirrored dataset with this array.
3. If this system has fewer reports (or zero), coworker dataset must also remove missing reports.

This guarantees deleted reports on source are removed on coworker side.

## Optional Filters

- `entity=NCFL` or `entity=NPFL` (optional)

## PDF Template URLs

Each report contains:

- `pdf_template` (`internal` or `external`)
- `pdf_url` (template-matched URL)
- `pdf_internal_url`
- `pdf_external_url`

These PDF URLs are API-key accessible and do not require session login.

## Example (PowerShell)

```powershell
$k = 'nidec-sync-demo-key'
Invoke-WebRequest "http://localhost/NidecSecurity/public/api/reports_sync.php" -Headers @{ 'X-API-Key' = $k } | Select-Object -Expand Content
```

## Example Sync Logic (Coworker Side)

- Keep a local table keyed by `report_no`.
- On each sync:
    - Upsert all records from `reports`.
    - Delete local records where `report_no` is not in incoming snapshot.

## Ready-to-run Sample Script (PHP)

This repository now includes a sample consumer script:

- `tools/sample_consumer_reports_sync.php`

What it does:

- Calls `reports_sync.php` using `X-API-Key`
- Creates local mirror table `mirrored_reports` if missing
- Upserts incoming reports
- Deletes records no longer present in source snapshot
- Stores template-aware PDF links (`pdf_url`, `pdf_internal_url`, `pdf_external_url`)

Run command:

```bash
php tools/sample_consumer_reports_sync.php
```

Optional environment variables (for coworker system):

- `SOURCE_SYNC_URL`
- `SOURCE_API_KEY`
- `SOURCE_ENTITY` (`NCFL` or `NPFL`)
- `MIRROR_DB_HOST`
- `MIRROR_DB_PORT`
- `MIRROR_DB_NAME`
- `MIRROR_DB_USER`
- `MIRROR_DB_PASS`

## Notes

- Response includes `snapshot_checksum` to detect changes quickly.
- If unauthorized, endpoint returns `401`.
- If no reports exist, `reports` is an empty array and `total_reports` is `0`.
