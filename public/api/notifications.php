<?php

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user = getUser();
$uid = (string) ($user['employee_no'] ?? '');
if ($uid === '') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $limit = (int) ($_GET['limit'] ?? 20);
    $offset = (int) ($_GET['offset'] ?? 0);
    $filter = (string) ($_GET['filter'] ?? ($_GET['f'] ?? 'all'));
    $role = (string) ($user['role'] ?? '');

    $filter = strtolower(trim($filter));
    if (!in_array($filter, ['all', 'unread', 'today', 'week', 'month'], true)) {
        $filter = 'all';
    }

    $offset = max(0, $offset);

    $whereExtra = '';
    if ($filter === 'unread') {
        $whereExtra .= ' AND n.is_read = 0';
    } elseif ($filter === 'today') {
        $whereExtra .= ' AND n.created_at >= CURDATE()';
    } elseif ($filter === 'week') {
        $whereExtra .= ' AND n.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    } elseif ($filter === 'month') {
        $whereExtra .= ' AND n.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    }

    if ($role === 'security') {
        $building = normalize_building($user['building'] ?? null);
        if (!$building) {
            echo json_encode(['unread_count' => 0, 'items' => []]);
            exit();
        }

        $limit = max(1, min(100, $limit));
        $sql =
            'SELECT n.id, n.report_id, n.message, n.is_read, n.created_at, r.report_no' .
            ' FROM notifications n' .
            ' LEFT JOIN reports r ON r.id = n.report_id' .
            ' WHERE n.user_id = ? AND (n.report_id IS NULL OR r.building = ?)' .
            $whereExtra .
            ' ORDER BY n.created_at DESC, n.id DESC' .
            ' LIMIT ' .
            (int) $limit .
            ' OFFSET ' .
            (int) $offset;

        $items = db_fetch_all($sql, 'ss', [$uid, $building]);

        $row = db_fetch_one(
            'SELECT COUNT(*) AS c' .
                ' FROM notifications n' .
                ' LEFT JOIN reports r ON r.id = n.report_id' .
                ' WHERE n.user_id = ? AND n.is_read = 0 AND (n.report_id IS NULL OR r.building = ?)',
            'ss',
            [$uid, $building],
        );
        $unread = (int) ($row['c'] ?? 0);
    } else {
        // Inline filter support for non-security (keeps the dropdown behavior intact)
        $limit = max(1, min(100, $limit));

        $sql =
            'SELECT n.id, n.report_id, n.message, n.is_read, n.created_at, r.report_no' .
            ' FROM notifications n' .
            ' LEFT JOIN reports r ON r.id = n.report_id' .
            ' WHERE n.user_id = ?' .
            $whereExtra .
            ' ORDER BY n.created_at DESC, n.id DESC' .
            ' LIMIT ' .
            (int) $limit .
            ' OFFSET ' .
            (int) $offset;

        $items = db_fetch_all($sql, 's', [$uid]);
        $unread = notifications_unread_count($uid);
    }

    echo json_encode([
        'unread_count' => $unread,
        'items' => array_map(static function ($r) {
            return [
                'id' => (int) $r['id'],
                'report_no' => $r['report_no'] ?? null,
                'message' => $r['message'],
                'is_read' => (int) ($r['is_read'] ?? 0),
                'created_at' => $r['created_at'],
            ];
        }, $items),
    ]);
    exit();
}

if ($method === 'POST') {
    // CSRF: accept from header or body
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($csrf === '') {
        $csrf = $_POST['csrf_token'] ?? '';
    }

    if (!csrf_validate($csrf)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF failed']);
        exit();
    }

    $raw = file_get_contents('php://input');
    $json = [];
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }
    }

    $action = (string) ($json['action'] ?? ($_POST['action'] ?? ''));
    if ($action === 'mark_read') {
        $id = (int) ($json['id'] ?? ($_POST['id'] ?? 0));
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            exit();
        }

        $ok = notifications_mark_read($uid, $id);
        echo json_encode(['ok' => $ok]);
        exit();
    }

    if ($action === 'mark_all_read') {
        $count = notifications_mark_all_read($uid);
        echo json_encode(['ok' => true, 'marked' => $count]);
        exit();
    }

    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
