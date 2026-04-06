<?php

class UsersModel
{
    public function getUserRoleByEmployeeNo(string $employeeNo): ?string
    {
        $row = db_fetch_one('SELECT role FROM users WHERE employee_no=? LIMIT 1', 's', [$employeeNo]);
        if (!$row) {
            return null;
        }
        return (string) ($row['role'] ?? '');
    }

    /**
     * Insert a new user whose identity has been verified via the Employee API.
     *
     * $employeeNo, $name, $email, $position, $job_level, $department come from
     * the API response — they are never sourced from raw POST data to prevent spoofing.
     */
    public function insertUser(
        string $employeeNo,
        string $name,
        string $email,
        string $position,
        string $jobLevel,
        string $department,
        string $username,
        string $passwordHash,
        string $role,
        string $securityType,
        string $entity,
        int $departmentId,
        string $accountStatus,
    ): void {
        db_execute(
            'INSERT INTO users
                 (employee_no, name, email, position, job_level, department, username, password_hash,
                  role, entity, department_id, account_status)
             VALUES
                 (?, ?, NULLIF(?,\'\'), NULLIF(?,\'\'), NULLIF(?,\'\'), NULLIF(?,\'\'),
                  ?, ?, ?,
                  NULLIF(?,\'\'), NULLIF(?,0), ?)',
            '',
            [
                $employeeNo,
                $name,
                $email,
                $position,
                $jobLevel,
                $department,
                $username,
                $passwordHash,
                $role,
                $entity,
                $departmentId,
                $accountStatus,
            ],
        );
    }

    public function updateUserWithPassword(
        string $employeeNo,
        string $name,
        string $username,
        string $passwordHash,
        string $role,
        string $securityType,
        string $entity,
        int $departmentId,
        string $accountStatus,
    ): void {
        db_execute(
            'UPDATE users SET name=?, username=?, password_hash=?, role=?, entity=NULLIF(?,\'\'), department_id=NULLIF(?,0), account_status=? WHERE employee_no=?',
            'ssssssisc',
            [$name, $username, $passwordHash, $role, $entity, $departmentId, $accountStatus, $employeeNo],
        );
    }

    public function updateUserNoPassword(
        string $employeeNo,
        string $name,
        string $username,
        string $role,
        string $securityType,
        string $entity,
        int $departmentId,
        string $accountStatus,
    ): void {
        db_execute(
            'UPDATE users SET name=?, username=?, role=?, entity=NULLIF(?,\'\'), department_id=NULLIF(?,0), account_status=? WHERE employee_no=?',
            'sssssiss',
            [$name, $username, $role, $entity, $departmentId, $accountStatus, $employeeNo],
        );
    }

    public function deleteUserByEmployeeNo(string $employeeNo): void
    {
        db_execute('DELETE FROM users WHERE employee_no=?', 's', [$employeeNo]);
    }

    /**
     * Look up a just-provisioned user by employee_no or username.
     * Returns the full record (including department_name join) needed for
     * session creation, or null when no matching row is found.
     *
     * @return array<string,mixed>|null
     */
    public function findProvisionedUser(string $employeeNo, string $username): ?array
    {
        // Try by employee_no first (most reliable).
        $user = db_fetch_one(
            'SELECT u.employee_no, u.name, u.email, u.position,
                    u.username, u.password_hash, u.role, u.account_status,
                    u.department_id, NULL AS security_type, u.entity,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.employee_no = ? LIMIT 1',
            's',
            [$employeeNo],
        );

        if ($user) {
            return $user;
        }

        // Fall back to username lookup.
        return db_fetch_one(
            'SELECT u.employee_no, u.name, u.email, u.position,
                    u.username, u.password_hash, u.role, u.account_status,
                    u.department_id, NULL AS security_type, u.entity,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.username = ? LIMIT 1',
            's',
            [$username],
        ) ?:
            null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getAllUsers(): array
    {
        return db_fetch_all(
            'SELECT u.employee_no, u.name, u.email, u.position,
                    u.username, u.role, NULL AS security_type, u.entity,
                    u.department_id, u.account_status,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             ORDER BY u.created_at DESC',
        );
    }
}
