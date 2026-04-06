<?php

require_once __DIR__ . '/../includes/db.php';

// ---------------------------------------------------------------------------
// Internal logging — writes to PHP error_log; swap for a DB/file logger later.
// ---------------------------------------------------------------------------
function _notif_log(string $level, string $msg, array $ctx = []): void
{
    $line = '[NOTIF:' . strtoupper($level) . '] ' . $msg;
    if ($ctx) {
        $line .= ' ' . json_encode($ctx, JSON_UNESCAPED_SLASHES);
    }
    error_log($line);
}

// ---------------------------------------------------------------------------
// Prevent duplicate notifications in a rolling time window.
// Returns true when the same (user, report, message) already exists within
// $windowSeconds. Used before every insert to guarantee idempotency.
// ---------------------------------------------------------------------------
function _notif_already_sent(string $userId, ?int $reportId, string $message, int $windowSeconds = 3600): bool
{
    if ($windowSeconds <= 0) {
        return false;
    }
    $row = db_fetch_one(
        'SELECT id FROM notifications' .
            ' WHERE user_id = ? AND ' .
            ($reportId !== null ? 'report_id = ?' : 'report_id IS NULL') .
            ' AND message = ?' .
            ' AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)' .
            ' LIMIT 1',
        $reportId !== null ? 'sisi' : 'ssi',
        $reportId !== null ? [$userId, $reportId, $message, $windowSeconds] : [$userId, $message, $windowSeconds],
    );
    return (bool) $row;
}

function notifications_unread_count(string $userId): int
{
    $row = db_fetch_one('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0', 's', [$userId]);
    return (int) ($row['c'] ?? 0);
}

function notifications_fetch(string $userId, int $limit = 20): array
{
    $limit = max(1, min(50, $limit));

    // LIMIT cannot be bound in MySQL prepared statements in all modes; inline safely.
    $sql =
        'SELECT n.id, n.report_id, n.message, n.is_read, n.created_at, r.report_no' .
        ' FROM notifications n' .
        ' LEFT JOIN reports r ON r.id = n.report_id' .
        ' WHERE n.user_id = ?' .
        ' ORDER BY n.created_at DESC, n.id DESC' .
        ' LIMIT ' .
        (int) $limit;

    return db_fetch_all($sql, 's', [$userId]);
}

function notifications_mark_read(string $userId, int $notificationId): bool
{
    $affected = db_execute('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', 'is', [
        $notificationId,
        $userId,
    ]);
    return $affected > 0;
}

function notifications_mark_all_read(string $userId): int
{
    return db_execute('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', 's', [$userId]);
}

function notify_user(string $userId, ?int $reportId, string $message, int $dedupWindowSeconds = 0): void
{
    try {
        if ($dedupWindowSeconds > 0 && _notif_already_sent($userId, $reportId, $message, $dedupWindowSeconds)) {
            return; // duplicate suppressed within window
        }
        db_execute(
            'INSERT INTO notifications (user_id, report_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())',
            'sis',
            [$userId, $reportId, $message],
        );
    } catch (Throwable $e) {
        _notif_log('error', 'notify_user failed', [
            'user_id' => $userId,
            'report_id' => $reportId,
            'message' => $message,
            'error' => $e->getMessage(),
        ]);
    }
}

function notify_users(array $userIds, ?int $reportId, string $message, int $dedupWindowSeconds = 0): void
{
    $unique = [];
    foreach ($userIds as $id) {
        $id = (string) $id;
        if ($id !== '') {
            $unique[$id] = true;
        }
    }

    foreach (array_keys($unique) as $uid) {
        notify_user($uid, $reportId, $message, $dedupWindowSeconds);
    }
}

// ---------------------------------------------------------------------------
// notify_role — dispatches to every active user in the given role.
//
// $dedupWindowSeconds: skip insert when the same (user, report, message)
//   already exists within this many seconds. 0 = always insert.
// ---------------------------------------------------------------------------
function notify_role(
    string $role,
    ?int $reportId,
    string $message,
    ?int $departmentId = null,
    int $dedupWindowSeconds = 0,
): void {
    $params = [$role];
    $types = 's';
    $where = "role = ? AND account_status = 'active'";

    // Entity scoping removed: all active security users can receive security notifications.

    if ($departmentId !== null) {
        $where .= ' AND department_id = ?';
        $params[] = $departmentId;
        $types .= 'i';
    }

    $rows = db_fetch_all('SELECT employee_no FROM users WHERE ' . $where, $types, $params);

    if (empty($rows)) {
        _notif_log('warning', 'notify_role: no active users found', [
            'role' => $role,
            'report_id' => $reportId,
            'department_id' => $departmentId,
        ]);
        return;
    }

    $ids = array_map(static fn($r) => (string) ($r['employee_no'] ?? ''), $rows);
    $ids = array_filter($ids, static fn($id) => $id !== '');
    notify_users($ids, $reportId, $message, $dedupWindowSeconds);
}
