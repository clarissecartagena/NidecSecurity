<?php
$pageTitle = 'User Management';
$requiredRole = 'ga_staff';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/topnav.php';
require_once __DIR__ . '/../../app/services/EmployeeService.php';

$flash = null;
$flashType = 'success';

$currentUser = getUser();
$currentUserEmployeeNo = (string) ($currentUser['employee_no'] ?? '');

$departmentsDb = fetch_departments();

function has_user_audit_columns(): bool
{
    try {
        $a = db_fetch_one("SHOW COLUMNS FROM users LIKE 'created_by_role'");
        $b = db_fetch_one("SHOW COLUMNS FROM users LIKE 'created_by_employee_no'");
        return (bool) $a && (bool) $b;
    } catch (Throwable $e) {
        return false;
    }
}

$auditOk = has_user_audit_columns();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!csrf_validate($token)) {
        $flash = 'Security check failed. Please refresh and try again.';
        $flashType = 'error';
    } else {
        try {
            if ($action === 'add') {
                $employeeNo = trim($_POST['employee_id'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $password = (string) ($_POST['password'] ?? '');
                $departmentId = (int) ($_POST['department_id'] ?? 0);
                $securityType = (string) ($_POST['security_type'] ?? '');
                $accountStatus = 'active';

                if ($employeeNo === '' || $username === '' || $password === '') {
                    throw new RuntimeException('Please fill in all required fields.');
                }

                // Verify employee via company API and auto-detect role + entity.
                $empSvc = new EmployeeService();
                $empResult = $empSvc->getEmployee($employeeNo);
                if (!$empResult['success']) {
                    throw new RuntimeException(
                        'Employee verification failed: ' . ($empResult['error'] ?? 'Employee not found.'),
                    );
                }
                $emp = $empResult['employee'];

                // Auto-detect role and entity from Employee API data.
                $detected = EmployeeService::detectRoleFromEmployee($emp);
                if ($detected === null) {
                    throw new RuntimeException(
                        'This employee cannot be added to the system. ' .
                            'Only GA Staff (HUMAN RESOURCE, GA AND COMPLIANCE section), ' .
                            'Security Guards (job level: Security / Security Guard), ' .
                            'and Department PICs (job level: SUPPORT/PIC) may be registered.',
                    );
                }

                $role = $detected['role'];
                $entity = $detected['entity'];

                if ($role === 'ga_manager') {
                    throw new RuntimeException('The GA Manager account cannot be added through this form.');
                }

                // GA Staff restriction — only security and department roles allowed.
                if (!in_array($role, ['security', 'department', 'ga_staff'], true)) {
                    throw new RuntimeException('Unauthorized Role Creation');
                }

                if ($role === 'department') {
                    if ($departmentId <= 0) {
                        // Try auto-resolving department from API data.
                        $deptRow = db_fetch_one('SELECT id FROM departments WHERE LOWER(name) = ? LIMIT 1', 's', [
                            strtolower($emp['department'] ?? ''),
                        ]);
                        $departmentId = $deptRow ? (int) $deptRow['id'] : 0;
                    }
                    $securityType = '';
                    $entity = '';
                } elseif ($role === 'security') {
                    $departmentId = 0;
                    // security_type is optional for security users
                    if ($securityType !== '' && !in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a valid Security Type (Internal or External).');
                    }
                    $departmentId = 0;
                } else {
                    // ga_staff
                    $departmentId = 0;
                    $securityType = '';
                    $entity = '';
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($auditOk) {
                    db_execute(
                        "INSERT INTO users
                             (employee_no, name, email, position, job_level, department, username, password_hash,
                              role, department_id, security_type, entity, account_status,
                              created_by_role, created_by_employee_no)
                         VALUES
                             (NULLIF(?,''), ?, NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), ?, ?,
                              ?, NULLIF(?,0), NULLIF(?,''), NULLIF(?,''), ?,
                              'ga_staff', ?)",
                        '',
                        [
                            $emp['employee_id'],
                            $emp['fullname'],
                            $emp['email'],
                            $emp['position'],
                            $emp['job_level'],
                            $emp['department'],
                            $username,
                            $hash,
                            $role,
                            $departmentId,
                            $securityType,
                            $entity,
                            $accountStatus,
                            $currentUserEmployeeNo,
                        ],
                    );
                } else {
                    db_execute(
                        "INSERT INTO users
                             (employee_no, name, email, position, job_level, department, username, password_hash,
                              role, department_id, security_type, entity, account_status)
                         VALUES
                             (NULLIF(?,''), ?, NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), ?, ?,
                              ?, NULLIF(?,0), NULLIF(?,''), NULLIF(?,''), ?)",
                        '',
                        [
                            $emp['employee_id'],
                            $emp['fullname'],
                            $emp['email'],
                            $emp['position'],
                            $emp['job_level'],
                            $emp['department'],
                            $username,
                            $hash,
                            $role,
                            $departmentId,
                            $securityType,
                            $entity,
                            $accountStatus,
                        ],
                    );
                }

                $flash = 'User added successfully.';
            } elseif ($action === 'update') {
                $employeeNo = trim($_POST['id'] ?? '');
                $name = trim($_POST['name'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $password = (string) ($_POST['password'] ?? '');
                $role = (string) ($_POST['role'] ?? '');
                $departmentId = (int) ($_POST['department_id'] ?? 0);
                $securityType = (string) ($_POST['security_type'] ?? '');
                $entity = (string) ($_POST['entity'] ?? '');
                $accountStatus = (string) ($_POST['account_status'] ?? 'active');

                if ($employeeNo === '' || $name === '' || $username === '') {
                    throw new RuntimeException('Invalid update request.');
                }

                if (!in_array($accountStatus, ['active', 'inactive'], true)) {
                    throw new RuntimeException('Invalid account status.');
                }

                // Only allow editing of Security/Department users
                $existing = db_fetch_one('SELECT employee_no, role FROM users WHERE employee_no = ? LIMIT 1', 's', [
                    $employeeNo,
                ]);
                if (!$existing || !in_array($existing['role'] ?? '', ['security', 'department'], true)) {
                    throw new RuntimeException('Access denied.');
                }

                // GA Staff role restriction (server-side)
                if (!in_array($role, ['security', 'department'], true)) {
                    throw new RuntimeException('Unauthorized Role Creation');
                }

                if ($role !== 'department') {
                    $departmentId = 0;
                }

                if ($role !== 'security') {
                    $securityType = '';
                    $entity = '';
                } else {
                    // security_type is now optional for security users
                    if ($securityType !== '' && !in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a valid Security Type (Internal or External).');
                    }
                    if (!in_array($entity, ['NCFL', 'NPFL'], true)) {
                        throw new RuntimeException('Please select an assigned entity (NCFL/NPFL).');
                    }
                }

                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    db_execute(
                        'UPDATE users SET name=?, username=?, password_hash=?, role=?, department_id=NULLIF(?,0), security_type=NULLIF(?,\'\'), entity=NULLIF(?,\'\'), account_status=? WHERE employee_no=?',
                        'ssssissss',
                        [
                            $name,
                            $username,
                            $hash,
                            $role,
                            $departmentId,
                            $securityType,
                            $entity,
                            $accountStatus,
                            $employeeNo,
                        ],
                    );
                } else {
                    db_execute(
                        'UPDATE users SET name=?, username=?, role=?, department_id=NULLIF(?,0), security_type=NULLIF(?,\'\'), entity=NULLIF(?,\'\'), account_status=? WHERE employee_no=?',
                        'sssissss',
                        [$name, $username, $role, $departmentId, $securityType, $entity, $accountStatus, $employeeNo],
                    );
                }

                $flash = 'User updated successfully.';
            } elseif ($action === 'delete') {
                $employeeNo = trim($_POST['id'] ?? '');
                if ($employeeNo === '') {
                    throw new RuntimeException('Invalid request.');
                }
                if ($employeeNo === $currentUserEmployeeNo) {
                    throw new RuntimeException('You cannot delete your own account.');
                }

                $row = db_fetch_one('SELECT employee_no, role FROM users WHERE employee_no = ? LIMIT 1', 's', [
                    $employeeNo,
                ]);
                if (!$row || !in_array($row['role'] ?? '', ['security', 'department'], true)) {
                    throw new RuntimeException('Access denied.');
                }

                db_execute('DELETE FROM users WHERE employee_no = ? LIMIT 1', 's', [$employeeNo]);
                $flash = 'User deleted successfully.';
            } else {
                throw new RuntimeException('Unknown action.');
            }
        } catch (Throwable $e) {
            $flash = $e->getMessage();
            $flashType = 'error';
        }
    }
}

$users = db_fetch_all(
    /* department_name: prefer the departments-table name (department-role users).
       Fall back to u.department (raw API value stored at registration) so that
       security-role users — who have no department_id — still show a value. */
    "SELECT u.employee_no, u.name, u.username, u.role, u.department_id, u.security_type, u.entity, u.account_status, u.created_at, COALESCE(d.name, u.department) AS department_name
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     WHERE u.role IN ('security','department')
     ORDER BY u.created_at DESC",
);

function user_status_badge(string $status): string
{
    return $status === 'active'
        ? '<span class="badge badge--success">Active</span>'
        : '<span class="badge badge--muted">Inactive</span>';
}

function user_role_label(string $role): string
{
    return $role === 'security' ? 'Security' : 'Department PIC';
}
?>

<style>
    .user-filters-wrap {
        background: hsl(var(--card));
        border: 1px solid hsl(var(--border));
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 16px;
    }

    .user-filter-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: hsl(var(--muted-foreground));
        font-weight: 700;
    }

    .users-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    @media (min-width: 768px) {
        .users-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1200px) {
        .users-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .user-card {
        --user-accent: hsl(var(--info));
        --user-accent-soft: hsl(var(--info) / 0.1);
        border: 1px solid hsl(var(--border));
        border-left: 4px solid var(--user-accent);
        border-radius: 12px;
        background: linear-gradient(165deg, var(--user-accent-soft) 0%, hsl(var(--card)) 46%, hsl(var(--muted) / 0.2) 100%);
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-height: 188px;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .user-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.1);
        border-color: color-mix(in srgb, var(--user-accent) 35%, hsl(var(--border)));
    }

    .user-card--security {
        --user-accent: var(--bs-primary, #0d6efd);
        --user-accent-soft: color-mix(in srgb, var(--bs-primary, #0d6efd) 18%, transparent);
    }

    .user-card--department {
        --user-accent: var(--bs-warning, #f59f00);
        --user-accent-soft: color-mix(in srgb, var(--bs-warning, #f59f00) 20%, transparent);
    }

    .user-card-top {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: hsl(var(--primary) / 0.12);
        color: hsl(var(--primary));
        font-size: 16px;
        flex: 0 0 auto;
    }

    .user-card-name {
        margin: 0;
        font-weight: 700;
        font-size: 15px;
        line-height: 1.2;
    }

    .user-card-name--security {
        color: var(--bs-primary, #0d6efd);
    }

    .user-card-name--department {
        color: #b06a00;
    }

    .user-card-sub {
        margin: 0;
        color: hsl(var(--muted-foreground));
        font-size: 12px;
        line-height: 1.4;
        word-break: break-word;
    }

    .user-card-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .user-role-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
    }

    .user-role-chip--security {
        background: color-mix(in srgb, var(--bs-primary, #0d6efd) 16%, transparent);
        border: 1px solid color-mix(in srgb, var(--bs-primary, #0d6efd) 40%, transparent);
        color: var(--bs-primary, #0d6efd);
    }

    .user-role-chip--department {
        background: color-mix(in srgb, var(--bs-warning, #f59f00) 16%, transparent);
        border: 1px solid color-mix(in srgb, var(--bs-warning, #f59f00) 40%, transparent);
        color: #9a5d00;
    }

    .user-card-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .user-meta-box {
        border: 1px solid hsl(var(--border));
        border-radius: 8px;
        padding: 8px;
        background: hsl(var(--muted) / 0.25);
    }

    .user-meta-label {
        font-size: 11px;
        color: hsl(var(--muted-foreground));
        margin-bottom: 2px;
    }

    .user-meta-value {
        font-size: 12px;
        color: hsl(var(--foreground));
        font-weight: 600;
        margin: 0;
    }

    .user-card-actions {
        margin-top: auto;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .user-card-action-btn {
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 8px;
        min-height: 34px;
        padding: 6px 10px;
    }

    .user-card-action-btn i {
        font-size: 13px;
    }

    .user-card-action-btn--edit {
        background-color: var(--bs-primary, #0d6efd);
        border-color: var(--bs-primary, #0d6efd);
        color: #fff;
    }

    .user-card-action-btn--edit:hover,
    .user-card-action-btn--edit:focus {
        background-color: #0b5ed7;
        border-color: #0a58ca;
        color: #fff;
    }

    .user-card-action-btn--delete {
        background-color: var(--bs-danger, #dc3545);
        border-color: var(--bs-danger, #dc3545);
        color: #fff;
    }

    .user-card-action-btn--delete:hover,
    .user-card-action-btn--delete:focus {
        background-color: #bb2d3b;
        border-color: #b02a37;
        color: #fff;
    }

    .users-empty-card {
        border: 1px dashed hsl(var(--border));
        border-radius: 12px;
        background: hsl(var(--muted) / 0.2);
        padding: 24px;
        text-align: center;
        color: hsl(var(--muted-foreground));
        font-size: 14px;
    }
</style>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-people-fill me-2 text-primary"></i>User Management</h1>
                <p class="text-sm text-muted-foreground mb-0">GA Staff can manage Security and Department accounts</p>
            </div>
            <button type="button" onclick="UsersPage.openAddModal()" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14"/><path d="M12 5v14"/>
                </svg>
                Add User
            </button>
        </div>

        <?php if (!$auditOk): ?>
            <div class="alert alert-error mb-4">
                Database audit columns are missing. Update your schema/migration to add <strong>users.created_by_role</strong> and <strong>users.created_by_employee_no</strong>.
            </div>
        <?php endif; ?>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'error' : 'success'; ?> mb-4">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <div class="user-filters-wrap">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-7 col-lg-8">
                    <label class="user-filter-label mb-1" for="users-search-input">Search Users</label>
                    <input type="text" id="users-search-input" class="form-control" placeholder="Search name, employee no, username, department..." />
                </div>
                <div class="col-12 col-md-5 col-lg-4">
                    <label class="user-filter-label mb-1" for="users-role-filter">Role</label>
                    <select id="users-role-filter" class="form-select">
                        <option value="all">All Roles</option>
                        <option value="security">Security</option>
                        <option value="department">Department PIC</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <div class="p-0">
                <div id="users-card-grid" class="users-grid">
                    <?php if (empty($users)): ?>
                        <div class="users-empty-card">No users found.</div>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <?php
                            $roleValue = (string) ($u['role'] ?? '');
                            $roleLabel = user_role_label($roleValue);
                            $nameRoleClass =
                                $roleValue === 'security' ? 'user-card-name--security' : 'user-card-name--department';
                            $chipRoleClass =
                                $roleValue === 'security' ? 'user-role-chip--security' : 'user-role-chip--department';
                            $cardRoleClass =
                                $roleValue === 'security' ? 'user-card--security' : 'user-card--department';
                            $employeeNo = (string) ($u['employee_no'] ?? '—');
                            $departmentName = (string) ($u['department_name'] ?? '—');
                            $username = (string) ($u['username'] ?? '—');
                            $fullName = (string) ($u['name'] ?? '');
                            $searchBlob = strtolower(
                                trim(
                                    $fullName .
                                        ' ' .
                                        $employeeNo .
                                        ' ' .
                                        $username .
                                        ' ' .
                                        $roleValue .
                                        ' ' .
                                        $departmentName,
                                ),
                            );
                            ?>
                            <article
                                class="user-card user-card-item <?php echo htmlspecialchars($cardRoleClass); ?>"
                                data-role="<?php echo htmlspecialchars($roleValue); ?>"
                                data-search="<?php echo htmlspecialchars($searchBlob); ?>"
                            >
                                <div class="user-card-top">
                                    <div class="user-avatar" aria-hidden="true">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <p class="user-card-name <?php echo htmlspecialchars(
                                            $nameRoleClass,
                                        ); ?> text-truncate"><?php echo htmlspecialchars($fullName); ?></p>
                                        <p class="user-card-sub mb-0 text-truncate">
                                            <i class="bi bi-person-badge me-1" aria-hidden="true"></i><?php echo htmlspecialchars(
                                                $username,
                                            ); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="user-card-badges">
                                    <span class="user-role-chip <?php echo htmlspecialchars(
                                        $chipRoleClass,
                                    ); ?>"><?php echo htmlspecialchars($roleLabel); ?></span>
                                    <?php echo user_status_badge((string) ($u['account_status'] ?? 'inactive')); ?>
                                </div>

                                <div class="user-card-meta">
                                    <div class="user-meta-box">
                                        <div class="user-meta-label">User ID</div>
                                        <p class="user-meta-value font-mono"><?php echo htmlspecialchars(
                                            $employeeNo,
                                        ); ?></p>
                                    </div>
                                    <div class="user-meta-box">
                                        <div class="user-meta-label">Department</div>
                                        <p class="user-meta-value text-truncate"><?php echo htmlspecialchars(
                                            $departmentName,
                                        ); ?></p>
                                    </div>
                                </div>

                                <div class="user-card-actions">
                                    <button type="button" class="btn user-card-action-btn user-card-action-btn--edit" title="Edit"
                                        onclick="UsersPage.openEditModal(this)"
                                        data-user="<?php echo htmlspecialchars(
                                            json_encode([
                                                'id' => (string) $u['employee_no'],
                                                'name' => (string) $u['name'],
                                                'username' => (string) $u['username'],
                                                'role' => (string) $u['role'],
                                                'department_id' => (int) ($u['department_id'] ?? 0),
                                                'department' => (string) ($u['department_name'] ?? ''),
                                                'security_type' => (string) ($u['security_type'] ?? ''),
                                                'entity' => (string) ($u['entity'] ?? ''),
                                                'account_status' => (string) ($u['account_status'] ?? 'active'),
                                            ]),
                                            ENT_QUOTES,
                                            'UTF-8',
                                        ); ?>">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        <span>Edit</span>
                                    </button>
                                    <button type="button" class="btn user-card-action-btn user-card-action-btn--delete" title="Delete"
                                        onclick="UsersPage.openDeleteModal(this)"
                                        data-user="<?php echo htmlspecialchars(
                                            json_encode([
                                                'id' => (string) $u['employee_no'],
                                                'name' => (string) $u['name'],
                                                'username' => (string) $u['username'],
                                                'role' => (string) $u['role'],
                                                'department' => (string) ($u['department_name'] ?? ''),
                                                'account_status' => (string) ($u['account_status'] ?? 'active'),
                                            ]),
                                            ENT_QUOTES,
                                            'UTF-8',
                                        ); ?>">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div id="users-filter-empty" class="users-empty-card mt-3 hidden">No users match your filter.</div>
            </div>
        </div>

        <!-- Add User Modal — two-step: search employee → set credentials -->
        <div id="add-user-modal" class="modal-overlay hidden">
            <div class="modal modal--accent">
                <div class="modal-accent-header">
                    <div>
                        <h2 class="modal-accent-title">Add New User</h2>
                        <p class="modal-accent-subtitle">Search the company employee directory first</p>
                    </div>
                    <button type="button" class="modal-accent-close" aria-label="Close" onclick="UsersPage.closeAddModal()">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-accent-body">

                    <!-- Step 1: Employee search -->
                    <div id="add-step-search">
                        <div class="mb-3">
                            <label class="form-label text-sm font-medium text-foreground mb-1" for="emp-search-input">
                                Search Employee
                            </label>
                            <div class="input-group">
                                <input type="text" id="emp-search-input"
                                    class="form-control form-control-sm"
                                    placeholder="Employee ID or Full Name (min 2 characters)…"
                                    autocomplete="off" />
                                <button type="button" id="emp-search-btn" class="btn btn-primary btn-sm">
                                    <i class="bi bi-search me-1" aria-hidden="true"></i>Search
                                </button>
                            </div>
                            <div class="form-text text-xs">
                                Data is fetched from the company employee directory.
                            </div>
                        </div>

                        <div id="emp-search-loader" class="text-center py-3 hidden" aria-live="polite">
                            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                            <span class="ms-2 text-sm text-muted-foreground">Searching…</span>
                        </div>

                        <div id="emp-search-alert" class="alert alert-danger text-sm py-2 mb-0 hidden" role="alert"></div>

                        <div id="emp-search-results" class="hidden">
                            <p class="text-xs text-muted-foreground mb-2">Select an employee to continue:</p>
                            <div id="emp-results-list" class="d-flex flex-column gap-2"></div>
                        </div>
                    </div>

                    <!-- Step 2: Confirm employee + set credentials -->
                    <div id="add-step-form" class="hidden">

                        <div class="p-3 mb-4 rounded border" style="background: var(--surface-2, #f8f9fa)">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-xs text-muted-foreground fw-semibold text-uppercase letter-spacing-wide">
                                    Selected Employee
                                </span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-xs"
                                    onclick="UsersPage.resetAddModal()">
                                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Change
                                </button>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Employee ID</div>
                                    <div class="text-sm fw-medium font-mono" id="emp-card-id">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Full Name</div>
                                    <div class="text-sm fw-medium" id="emp-card-name">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Department</div>
                                    <div class="text-sm" id="emp-card-dept">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Position</div>
                                    <div class="text-sm" id="emp-card-pos">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Email</div>
                                    <div class="text-sm" id="emp-card-email">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Detected Role</div>
                                    <div class="text-sm fw-semibold" id="emp-card-role">—</div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="row g-3" id="add-user-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(
                                csrf_token(),
                            ); ?>" />
                            <input type="hidden" name="action" value="add" />
                            <!-- employee_id is verified server-side via Employee API; role & entity are auto-detected -->
                            <input type="hidden" name="employee_id" id="add-employee-id" />
                            <input type="hidden" id="add-role" />
                            <input type="hidden" name="entity" id="add-entity" />

                            <div class="col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1" for="add-username">
                                    Username
                                </label>
                                <input type="text" class="form-control form-control-sm" name="username" id="add-username"
                                    required placeholder="Login username"
                                    autocomplete="off" />
                                <div class="form-text text-xs">Used to log in to the system.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1">Password</label>
                                <input type="password" class="form-control form-control-sm" name="password"
                                    id="add-password" required placeholder="Set initial password"
                                    autocomplete="new-password" />
                            </div>

                            <div id="add-department-wrap" class="hidden col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1">Department</label>
                                <select name="department_id" class="form-select form-select-sm" id="add-department-id">
                                    <option value="0">— select department —</option>
                                    <?php foreach ($departmentsDb as $d): ?>
                                        <option value="<?php echo (int) $d['id']; ?>"><?php echo htmlspecialchars(
    $d['name'],
); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="add-security-type-wrap" class="hidden col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1">Security Type <span class="text-danger">*</span></label>
                                <select name="security_type" class="form-select form-select-sm" id="add-security-type">
                                    <option value="" selected disabled>Select type</option>
                                    <option value="internal">Internal</option>
                                    <option value="external">External</option>
                                </select>
                                <div class="form-text text-xs">Entity: <strong id="add-entity-display">—</strong></div>
                            </div>

                            <div class="modal-footer col-12 d-flex justify-content-end gap-2 flex-wrap">
                                <button type="button" onclick="UsersPage.closeAddModal()" class="btn btn-outline">Cancel</button>
                                <button type="submit" id="add-user-submit-btn" class="btn btn-primary">
                                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>Add User
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="edit-user-modal" class="modal-overlay hidden">
            <div class="modal modal--accent">
                <div class="modal-accent-header">
                    <div>
                        <h2 class="modal-accent-title">Edit User</h2>
                        <p class="modal-accent-subtitle">Update details, role, and status</p>
                    </div>
                    <button type="button" class="modal-accent-close" aria-label="Close" onclick="UsersPage.closeEditModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-accent-body">
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="edit-user-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                        <input type="hidden" name="action" value="update" />
                        <input type="hidden" name="id" id="edit-id" value="" />

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Name</label>
                            <input type="text" name="name" id="edit-name" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Username</label>
                            <input type="text" name="username" id="edit-username" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">New Password (optional)</label>
                            <input type="password" name="password" id="edit-password" placeholder="Leave blank to keep current" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Account Status</label>
                            <select name="account_status" id="edit-account-status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Role</label>
                            <select name="role" id="edit-role" required>
                                <option value="security">Security</option>
                                <option value="department">Department</option>
                            </select>
                        </div>

                        <div id="edit-department-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Department</label>
                            <select name="department_id" id="edit-department-id">
                                <option value="0">—</option>
                                <?php foreach ($departmentsDb as $d): ?>
                                    <option value="<?php echo (int) $d['id']; ?>"><?php echo htmlspecialchars(
    $d['name'],
); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="edit-security-type-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Security Type</label>
                            <select name="security_type" id="edit-security-type">
                                <option value="" selected disabled>Select type</option>
                                <option value="internal">Internal</option>
                                <option value="external">External</option>
                            </select>
                        </div>

                        <div id="edit-entity-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Assigned Entity</label>
                            <select name="entity" id="edit-entity">
                                <option value="" selected disabled>Select entity</option>
                                <option value="NCFL">NCFL</option>
                                <option value="NPFL">NPFL</option>
                            </select>
                        </div>

                        <div class="modal-footer md:col-span-2">
                            <button type="button" class="btn btn-outline" onclick="UsersPage.closeEditModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete User Modal -->
        <div id="delete-user-modal" class="modal-overlay hidden">
            <div class="modal modal--accent modal--accent-destructive">
                <div class="modal-accent-header">
                    <div>
                        <h2 class="modal-accent-title">Delete User</h2>
                        <p class="modal-accent-subtitle">This action cannot be undone</p>
                    </div>
                    <button type="button" class="modal-accent-close" aria-label="Close" onclick="UsersPage.closeDeleteModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-accent-body">
                    <div class="alert alert-error">
                        You are about to permanently delete this account. Historical records may still reference this user.
                    </div>

                    <div class="confirm-details mt-4">
                        <div class="confirm-detail-card flex items-center justify-between">
                            <div>
                                <div class="text-xs text-muted-foreground">Name</div>
                                <div class="font-medium" id="delete-user-name">—</div>
                            </div>
                            <div class="confirm-detail-right">
                                <div class="text-xs text-muted-foreground">Username</div>
                                <div class="font-mono text-xs" id="delete-user-username">—</div>
                            </div>
                        </div>
                        <div class="confirm-detail-card flex items-center justify-between">
                            <div>
                                <div class="text-xs text-muted-foreground">Role</div>
                                <div class="text-sm" id="delete-user-role">—</div>
                            </div>
                            <div class="confirm-detail-right">
                                <div class="text-xs text-muted-foreground">Department</div>
                                <div class="text-sm" id="delete-user-dept">—</div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" id="delete-user-form" class="modal-footer">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                        <input type="hidden" name="action" value="delete" />
                        <input type="hidden" name="id" id="delete-user-id" value="" />

                        <div class="flex-1 hidden" id="delete-self-warning">
                            <span class="confirm-self-warning text-xs">You cannot delete your own account.</span>
                        </div>

                        <button type="button" class="btn btn-outline" onclick="UsersPage.closeDeleteModal()">Cancel</button>
                        <button type="submit" class="btn btn-destructive" id="delete-user-submit">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const UsersPage = window.UsersPage = {
        currentUserId: <?php echo json_encode($currentUserEmployeeNo); ?>,
        _empApiUrl: '<?php echo htmlspecialchars(app_url('api/employee_search.php'), ENT_QUOTES, 'UTF-8'); ?>',

        toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            const willOpen = modal.classList.contains('hidden');
            modal.classList.toggle('hidden');
            if (willOpen) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            } else {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        },

        init() {
            // add-role is now a hidden input updated by selectEmployee(); no change listener needed.
            this.syncConditionalFields('add');

            const editRole = document.getElementById('edit-role');
            if (editRole) {
                editRole.addEventListener('change', () => this.syncConditionalFields('edit'));
            }

            const usersSearch = document.getElementById('users-search-input');
            if (usersSearch) usersSearch.addEventListener('input', () => this.applyUserFilters());

            const usersRoleFilter = document.getElementById('users-role-filter');
            if (usersRoleFilter) usersRoleFilter.addEventListener('change', () => this.applyUserFilters());

            // Employee search bindings
            const empInput = document.getElementById('emp-search-input');
            const empBtn   = document.getElementById('emp-search-btn');
            if (empInput) {
                empInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); this.doEmployeeSearch(); }
                });
            }
            if (empBtn) empBtn.addEventListener('click', () => this.doEmployeeSearch());

            const addModal = document.getElementById('add-user-modal');
            if (addModal) {
                addModal.addEventListener('click', (e) => {
                    if (e.target === addModal) this.closeAddModal();
                });
            }

            const editModal = document.getElementById('edit-user-modal');
            if (editModal) {
                editModal.addEventListener('click', (e) => {
                    if (e.target === editModal) this.closeEditModal();
                });
            }

            const deleteModal = document.getElementById('delete-user-modal');
            if (deleteModal) {
                deleteModal.addEventListener('click', (e) => {
                    if (e.target === deleteModal) this.closeDeleteModal();
                });
            }

            this.applyUserFilters();
        },

        applyUserFilters() {
            const cards = Array.from(document.querySelectorAll('.user-card-item'));
            if (!cards.length) return;

            const searchEl = document.getElementById('users-search-input');
            const roleEl = document.getElementById('users-role-filter');
            const emptyEl = document.getElementById('users-filter-empty');

            const searchVal = (searchEl ? searchEl.value : '').trim().toLowerCase();
            const roleVal = roleEl ? roleEl.value : 'all';

            let visibleCount = 0;
            cards.forEach((card) => {
                const role = card.getAttribute('data-role') || '';
                const searchBlob = card.getAttribute('data-search') || '';
                const matchSearch = searchVal === '' || searchBlob.includes(searchVal);
                const matchRole = roleVal === 'all' || role === roleVal;
                const show = matchSearch && matchRole;
                card.style.display = show ? '' : 'none';
                if (show) visibleCount += 1;
            });

            if (emptyEl) emptyEl.classList.toggle('hidden', visibleCount > 0);
        },

        // ── Add User (employee-search flow) ──────────────────────────────
        openAddModal() {
            this.resetAddModal();
            this.toggleModal('add-user-modal');
        },

        closeAddModal() {
            const modal = document.getElementById('add-user-modal');
            if (modal && !modal.classList.contains('hidden')) this.toggleModal('add-user-modal');
            this.resetAddModal();
        },

        resetAddModal() {
            const stepSearch = document.getElementById('add-step-search');
            const stepForm   = document.getElementById('add-step-form');
            if (stepSearch) stepSearch.classList.remove('hidden');
            if (stepForm)   stepForm.classList.add('hidden');

            const empInput = document.getElementById('emp-search-input');
            if (empInput) empInput.value = '';

            const list = document.getElementById('emp-results-list');
            if (list) list.innerHTML = '';

            const results = document.getElementById('emp-search-results');
            if (results) results.classList.add('hidden');

            this._showSearchAlert(null);

            const empIdField = document.getElementById('add-employee-id');
            if (empIdField) empIdField.value = '';

            const form = document.getElementById('add-user-form');
            if (form) form.reset();

            this.syncConditionalFields('add');
        },

        doEmployeeSearch() {
            const input = document.getElementById('emp-search-input');
            const query = input ? input.value.trim() : '';

            if (query.length < 2) {
                this._showSearchAlert('Please enter at least 2 characters.');
                return;
            }

            this._setSearchLoading(true);
            this._showSearchAlert(null);

            const results = document.getElementById('emp-search-results');
            if (results) results.classList.add('hidden');

            // Use exact employee_id lookup for all-digit input; free-text search otherwise.
            const isNumericId = /^\d+$/.test(query);
            const url = isNumericId
                ? this._empApiUrl + '?employee_id=' + encodeURIComponent(query)
                : this._empApiUrl + '?q=' + encodeURIComponent(query);

            fetch(url, { credentials: 'same-origin' })
                .then(r => {
                    if (!r.ok) {
                        return r.text().then(text => {
                            let errMsg = 'Server error (HTTP ' + r.status + '). Please try again.';
                            // Attempt to extract a descriptive error from a JSON response body.
                            // If parsing fails we keep the generic fallback message above.
                            try { const j = JSON.parse(text); if (j.error) errMsg = j.error; } catch (_) {}
                            throw new Error(errMsg);
                        });
                    }
                    return r.json();
                })
                .then(data => {
                    this._setSearchLoading(false);
                    if (!data.success) {
                        this._showSearchAlert(data.error || 'Search failed. Please try again.');
                        return;
                    }
                    const employees = Array.isArray(data.employees) ? data.employees : [];
                    if (employees.length === 0) {
                        this._showSearchAlert('No employees found for "' + query + '".');
                        return;
                    }
                    this._renderResults(employees, !!data.using_mock);
                })
                .catch(err => {
                    this._setSearchLoading(false);
                    this._showSearchAlert((err && err.message) ? err.message : 'Network error. Please check your connection and try again.');
                });
        },

        selectEmployee(emp) {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = (val && String(val).trim()) ? String(val) : '—';
            };

            set('emp-card-id',    emp.employee_id);
            set('emp-card-name',  emp.fullname);
            set('emp-card-dept',  emp.department);
            set('emp-card-pos',   emp.position);
            set('emp-card-email', emp.email);

            const empIdField = document.getElementById('add-employee-id');
            if (empIdField) empIdField.value = emp.employee_id || '';

            // Pre-fill username suggestion from employee_id.
            const usernameField = document.getElementById('add-username');
            if (usernameField && !usernameField.value && emp.employee_id) {
                usernameField.value = String(emp.employee_id).toLowerCase();
            }

            // Auto-detect role; populate hidden #add-role so syncConditionalFields can read it.
            const detectedRole = this._detectEmployeeRole(emp);
            const roleInput = document.getElementById('add-role');
            if (roleInput) roleInput.value = detectedRole || '';

            // Show human-readable role in the employee info card.
            const roleLabels = { ga_staff: 'GA Staff', security: 'Security Guard', department: 'Department PIC' };
            set('emp-card-role', roleLabels[detectedRole] || (detectedRole ? detectedRole : 'Unknown / Not Eligible'));

            // Derive entity from API data (mirrors server-side EmployeeService logic).
            const apiEntity = String(emp.entity || '').trim().toUpperCase();
            let resolvedEntity = '';
            if (apiEntity === 'NCFL' || apiEntity === 'NPFL') {
                resolvedEntity = apiEntity;
            } else {
                const jl = String(emp.job_level || '').trim().toLowerCase();
                resolvedEntity = (jl === 'segurity guard') ? 'NPFL' : (jl === 'security' ? 'NCFL' : '');
            }
            const entityField = document.getElementById('add-entity');
            if (entityField) entityField.value = resolvedEntity;
            const entityDisplay = document.getElementById('add-entity-display');
            if (entityDisplay) entityDisplay.textContent = resolvedEntity || '—';

            const stepSearch = document.getElementById('add-step-search');
            const stepForm   = document.getElementById('add-step-form');
            if (stepSearch) stepSearch.classList.add('hidden');
            if (stepForm)   stepForm.classList.remove('hidden');
            this.syncConditionalFields('add');
        },

         /**
         * Mirror the server-side EmployeeService::detectRoleFromEmployee() logic.
         * Returns 'ga_staff' | 'security' | 'department' | null.
         */
        _detectEmployeeRole(emp) {
            const section  = (emp.section   || '').trim().toLowerCase();
            const jobLevel = (emp.job_level || '').trim().toLowerCase();
            if (section === 'human resource, ga and compliance') return 'ga_staff';
            if (jobLevel === 'security')       return 'security';
            if (jobLevel === 'segurity guard') return 'security'; // 'segurity' is the actual typo in the company HR system (SEGURITY GUARD, not SECURITY GUARD)
            if (jobLevel === 'support/pic')    return 'department';
            return null;
        },

        _setSearchLoading(loading) {
            const loader = document.getElementById('emp-search-loader');
            const btn    = document.getElementById('emp-search-btn');
            if (loader) loader.classList.toggle('hidden', !loading);
            if (btn)    btn.disabled = loading;
        },

        _showSearchAlert(msg) {
            const el = document.getElementById('emp-search-alert');
            if (!el) return;
            if (msg) {
                el.textContent = msg;
                el.classList.remove('hidden');
            } else {
                el.textContent = '';
                el.classList.add('hidden');
            }
        },

        _renderResults(employees, usingMock) {
            const list      = document.getElementById('emp-results-list');
            const container = document.getElementById('emp-search-results');
            if (!list || !container) return;

            list.innerHTML = '';
            employees.forEach(emp => {
                const row = document.createElement('div');
                row.className = 'd-flex align-items-center justify-content-between gap-3 p-2 rounded border bg-body';
                row.style.cursor = 'pointer';

                const info = document.createElement('div');
                info.className = 'overflow-hidden';

                const nameLine = document.createElement('div');
                nameLine.className = 'text-sm fw-medium text-truncate';
                nameLine.textContent = emp.fullname || '—';

                const subLine = document.createElement('div');
                subLine.className = 'text-xs text-muted-foreground text-truncate';
                subLine.textContent = [emp.employee_id, emp.department, emp.position]
                    .filter(v => v && String(v).trim())
                    .join(' · ');

                info.appendChild(nameLine);
                info.appendChild(subLine);

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline py-1 px-2 flex-shrink-0';
                btn.textContent = 'Select';

                row.appendChild(info);
                row.appendChild(btn);

                row.addEventListener('click', () => this.selectEmployee(emp));
                list.appendChild(row);
            });

            if (usingMock) {
                const notice = document.createElement('div');
                notice.className = 'alert alert-warning text-xs py-1 mt-2 mb-0';
                notice.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>'
                    + 'Development mode: data from local mock API.';
                list.appendChild(notice);
            }

            container.classList.remove('hidden');
        },

        // ── Role-conditional fields ───────────────────────────────────────
        syncConditionalFields(prefix) {
            const roleEl = document.getElementById(prefix + '-role');
            if (!roleEl) return;
            const role = roleEl.value;

            const deptWrap    = document.getElementById(prefix + '-department-wrap');
            const deptSelect  = document.getElementById(prefix + '-department-id');
            const secWrap     = document.getElementById(prefix + '-security-type-wrap');
            const secSelect   = document.getElementById(prefix + '-security-type');
            // Entity wrap/select only exists on the edit form; on add it is a hidden input.
            const entityWrap  = document.getElementById(prefix + '-entity-wrap');
            const entitySelect = document.getElementById(prefix + '-entity');

            const isDepartment = role === 'department';
            const isSecurity   = role === 'security';

            if (deptWrap) deptWrap.classList.toggle('hidden', !isDepartment);
            if (deptSelect) deptSelect.required = isDepartment;
            if (!isDepartment && deptSelect) deptSelect.value = '0';

            if (secWrap) secWrap.classList.toggle('hidden', !isSecurity);
            if (secSelect) secSelect.required = isSecurity;
            if (!isSecurity && secSelect) secSelect.value = '';

            // Entity wrap/select only present on edit form (visible select with options).
            if (entityWrap) entityWrap.classList.toggle('hidden', !isSecurity);
            if (entitySelect && entitySelect.tagName === 'SELECT') {
                entitySelect.required = isSecurity;
                if (!isSecurity) entitySelect.value = '';
            }
        },

        // ── Edit modal ────────────────────────────────────────────────────
        openEditModal(btn) {
            const raw = btn && btn.dataset ? btn.dataset.user : '';
            if (!raw) return;
            let user;
            try { user = JSON.parse(raw); } catch (e) { return; }

            document.getElementById('edit-id').value = String(user.id || '');
            document.getElementById('edit-name').value = user.name || '';
            document.getElementById('edit-username').value = user.username || '';
            document.getElementById('edit-password').value = '';
            document.getElementById('edit-account-status').value = (user.account_status === 'inactive') ? 'inactive' : 'active';

            const roleEl = document.getElementById('edit-role');
            if (roleEl) roleEl.value = user.role || 'security';

            const deptSelect = document.getElementById('edit-department-id');
            if (deptSelect) deptSelect.value = String(user.department_id || 0);

            const secSelect = document.getElementById('edit-security-type');
            if (secSelect) secSelect.value = user.security_type || '';

            const entitySelect = document.getElementById('edit-entity');
            if (entitySelect) entitySelect.value = user.entity || '';

            this.syncConditionalFields('edit');
            this.toggleModal('edit-user-modal');
        },

        closeEditModal() {
            const modal = document.getElementById('edit-user-modal');
            if (modal && !modal.classList.contains('hidden')) {
                this.toggleModal('edit-user-modal');
            }
        },

        // ── Delete modal ──────────────────────────────────────────────────
        openDeleteModal(btn) {
            const raw = btn && btn.dataset ? btn.dataset.user : '';
            if (!raw) return;
            let user;
            try { user = JSON.parse(raw); } catch (e) { return; }

            const id = String(user.id || '');
            document.getElementById('delete-user-id').value = id;
            document.getElementById('delete-user-name').textContent = user.name || '—';
            document.getElementById('delete-user-username').textContent = user.username || '—';
            document.getElementById('delete-user-role').textContent = (user.role || '—').replace(/_/g, ' ');
            document.getElementById('delete-user-dept').textContent = user.department ? String(user.department) : '—';

            const isSelf    = id !== '' && this.currentUserId === id;
            const warn      = document.getElementById('delete-self-warning');
            const submitBtn = document.getElementById('delete-user-submit');
            if (warn)      warn.classList.toggle('hidden', !isSelf);
            if (submitBtn) submitBtn.disabled = !!isSelf;

            this.toggleModal('delete-user-modal');
        },

        closeDeleteModal() {
            const modal = document.getElementById('delete-user-modal');
            if (modal && !modal.classList.contains('hidden')) {
                this.toggleModal('delete-user-modal');
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => UsersPage.init());
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
