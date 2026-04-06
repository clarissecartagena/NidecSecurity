<?php

require_once __DIR__ . '/../models/UsersModel.php';
require_once __DIR__ . '/EmployeeService.php';

class UsersService
{
    private UsersModel $model;
    private EmployeeService $employeeService;

    public function __construct(?UsersModel $model = null, ?EmployeeService $employeeService = null)
    {
        $this->model = $model ?? new UsersModel();
        $this->employeeService = $employeeService ?? new EmployeeService();
    }

    /**
     * @param array<string, mixed> $post
     * @return array{flash:?string, flashType:string}
     */
    public function handlePost(array $post, string $currentUserEmployeeNo): array
    {
        $flash = null;
        $flashType = 'success';

        $action = (string) ($post['action'] ?? '');
        $token = (string) ($post['csrf_token'] ?? '');

        if (!csrf_validate($token)) {
            return [
                'flash' => 'Security check failed. Please refresh and try again.',
                'flashType' => 'error',
            ];
        }

        try {
            if ($action === 'add') {
                // ── Step 1: basic field extraction ──────────────────────────────
                $employeeNo = trim((string) ($post['employee_id'] ?? ''));
                $username = trim((string) ($post['username'] ?? ''));
                $password = (string) ($post['password'] ?? '');
                $securityType = (string) ($post['security_type'] ?? '');
                $accountStatus = 'active';

                if ($employeeNo === '' || $username === '' || $password === '') {
                    throw new RuntimeException('Please fill in all required fields.');
                }

                // ── Step 2: verify employee via API and auto-detect role ─────────
                // Name, email, department, and position are ALWAYS sourced from
                // the API result — never from the submitted form fields.
                $empResult = $this->employeeService->getEmployee($employeeNo);
                if (!$empResult['success']) {
                    throw new RuntimeException(
                        'Employee verification failed: ' . ($empResult['error'] ?? 'Employee not found.'),
                    );
                }
                $emp = $empResult['employee'];

                // ── Step 3: auto-detect role + entity from Employee API ──────────
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

                // GA Manager can only be added via the allowed_users config.
                if ($role === 'ga_manager') {
                    throw new RuntimeException('The GA Manager account cannot be added through this form.');
                }

                // ── Step 4: role-specific validation ────────────────────────────
                $departmentId = 0;
                if ($role === 'department') {
                    $departmentId = (int) ($post['department_id'] ?? 0);
                    if ($departmentId <= 0) {
                        // Try auto-resolving from API dept name.
                        $departmentId = $this->departmentIdByName((string) ($emp['department'] ?? ''));
                    }
                    if ($departmentId <= 0) {
                        throw new RuntimeException('Please select a department for this user.');
                    }
                    $securityType = '';
                    $entity = '';
                } elseif ($role === 'security') {
                    if (!in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a security type (internal/external).');
                    }
                    // entity (NCFL/NPFL) is auto-detected from job_level — no manual input needed.
                } else {
                    // ga_staff
                    $departmentId = 0;
                    $securityType = '';
                    $entity = '';
                }

                // ── Step 5: persist ─────────────────────────────────────────────
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $this->model->insertUser(
                    $emp['employee_id'],
                    $emp['fullname'],
                    $emp['email'],
                    $emp['position'],
                    $emp['job_level'],
                    $emp['department'],
                    $username,
                    $hash,
                    $role,
                    $securityType,
                    $entity,
                    $departmentId,
                    $accountStatus,
                );

                $flash = 'User added successfully.';
            } elseif ($action === 'update') {
                $id = trim((string) ($post['id'] ?? ''));
                $name = trim((string) ($post['name'] ?? ''));
                $username = trim((string) ($post['username'] ?? ''));
                $password = (string) ($post['password'] ?? '');
                $role = (string) ($post['role'] ?? '');
                $departmentId = (int) ($post['department_id'] ?? 0);
                $accountStatus = (string) ($post['account_status'] ?? 'active');
                $securityType = (string) ($post['security_type'] ?? '');
                $entity = (string) ($post['entity'] ?? '');

                if (
                    $id === '' ||
                    $name === '' ||
                    $username === '' ||
                    !in_array($role, ['ga_manager', 'ga_staff', 'security', 'department'], true)
                ) {
                    throw new RuntimeException('Invalid update request.');
                }

                if (!in_array($accountStatus, ['active', 'inactive'], true)) {
                    throw new RuntimeException('Invalid account status.');
                }

                // Prevent assigning GA Manager to someone else.
                $currentRole = $this->model->getUserRoleByEmployeeNo($id);
                if ($currentRole === null || $currentRole === '') {
                    throw new RuntimeException('User not found.');
                }
                if ($role === 'ga_manager' && $currentRole !== 'ga_manager') {
                    throw new RuntimeException('You cannot assign the GA Manager role.');
                }

                if ($role === 'department') {
                    if ($departmentId <= 0) {
                        throw new RuntimeException('Please select a department.');
                    }
                    $securityType = '';
                    $entity = '';
                } elseif ($role === 'security') {
                    if (!in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a security type (internal/external).');
                    }
                    if (!in_array($entity, ['NCFL', 'NPFL'], true)) {
                        throw new RuntimeException('Please select an assigned entity (NCFL/NPFL).');
                    }
                    $departmentId = 0;
                } else {
                    $departmentId = 0;
                    $securityType = '';
                    $entity = '';
                }

                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $this->model->updateUserWithPassword(
                        $id,
                        $name,
                        $username,
                        $hash,
                        $role,
                        $securityType,
                        $entity,
                        $departmentId,
                        $accountStatus,
                    );
                } else {
                    $this->model->updateUserNoPassword(
                        $id,
                        $name,
                        $username,
                        $role,
                        $securityType,
                        $entity,
                        $departmentId,
                        $accountStatus,
                    );
                }

                $flash = 'User updated successfully.';
            } elseif ($action === 'delete') {
                $id = trim((string) ($post['id'] ?? ''));
                if ($id === '') {
                    throw new RuntimeException('Invalid delete request.');
                }
                if ($currentUserEmployeeNo !== '' && $currentUserEmployeeNo === $id) {
                    throw new RuntimeException('You cannot delete your own account.');
                }

                $this->model->deleteUserByEmployeeNo($id);
                $flash = 'User deleted successfully.';
            } else {
                throw new RuntimeException('Unknown action.');
            }
        } catch (Throwable $e) {
            $flash = $e->getMessage();
            $flashType = 'error';
        }

        return ['flash' => $flash, 'flashType' => $flashType];
    }

    /** @return array<int, array<string, mixed>> */
    public function getAllUsers(): array
    {
        return $this->model->getAllUsers();
    }

    /**
     * Resolve a local department ID from a department name returned by the API.
     */
    private function departmentIdByName(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            return 0;
        }
        $row = db_fetch_one('SELECT id FROM departments WHERE LOWER(name) = ? LIMIT 1', 's', [strtolower($name)]);
        return $row ? (int) $row['id'] : 0;
    }
}
