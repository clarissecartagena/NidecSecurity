<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../api_clients/LoginApiClient.php';
require_once __DIR__ . '/AllowedUsersService.php';
require_once __DIR__ . '/EmployeeService.php';

class AuthService
{
    /** @var UserModel */
    private $users;

    /** @var LoginApiClient */
    private $loginApi;

    /** @var AllowedUsersService */
    private $allowedUsers;

    public function __construct(
        ?UserModel $users = null,
        ?LoginApiClient $loginApi = null,
        ?AllowedUsersService $allowedUsers = null,
    ) {
        $this->users = $users ?: new UserModel();
        $this->loginApi = $loginApi ?: new LoginApiClient();
        $this->allowedUsers = $allowedUsers ?: new AllowedUsersService();
    }

    public function user(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    /**
     * Authenticate a user via the corporate login API.
     *
     * Flow:
     *   1. Check local database first (for seeded/provisioned users).
     *   2. Call the corporate login API (encrypted payload).
     *   3. API returns success + employee_no on valid credentials.
     *   4. Look up the employee_no in the local users table.
     *   5. Auto-provision if on the allowed list and not yet provisioned.
     *   6. Verify the local account is active and create a session.
     *
     * The role comes exclusively from the local users table (set at
     * registration/provisioning time, validated against Employee API data).
     *
     * @param  string $username  Employee number or username sent to the login API.
     * @param  string $password  Plain-text password (encrypted before sending).
     * @return bool              True on successful authentication and session creation.
     */
    public function login(string $username, string $password): bool
    {
        // --- STEP 1: CHECK LOCAL DATABASE FIRST (for seeded/provisioned users) ---
        $user = $this->users->findByUsername($username);

        if ($user && !empty($user['password_hash'])) {
            if (password_verify($password, $user['password_hash'])) {
                if (trim(strtolower((string) ($user['account_status'] ?? ''))) === 'active') {
                    return $this->establishSession($user);
                }
            }
        }

        // --- STEP 2: CALL CORPORATE API ---
        $apiResult = $this->loginApi->authenticate($username, $password);

        if (!empty($apiResult['success'])) {
            $employeeNo = (string) ($apiResult['employee_id'] ?? '');

            // Look up user by employee_no, then fall back to username.
            $user = $employeeNo !== '' ? $this->users->findByEmployeeNo($employeeNo) : null;
            if (!$user) {
                $user = $this->users->findByUsername($username);
            }

            // Auto-provision: if the employee is on the allowed list but does
            // not yet have a local account, create one now.
            if (!$user && $employeeNo !== '' && $this->allowedUsers->isAllowed($employeeNo)) {
                $user = $this->allowedUsers->provision($employeeNo, $username);
            }

            if ($user && trim(strtolower((string) ($user['account_status'] ?? ''))) === 'active') {
                return $this->establishSession($user);
            }
        }

        return false;
    }

    /**
     * Centralise session creation.
     */
    private function establishSession(array $user): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'employee_no' => (string) ($user['employee_no'] ?? ''),
            'username' => $user['username'],
            'role' => $user['role'],
            'displayName' => $user['name'],
            'department' => $user['department_name'] ?? null,
            'department_id' => isset($user['department_id']) ? (int) $user['department_id'] : null,
            'security_type' => $user['security_type'] ?? null,
            'entity' => $user['entity'] ?? null, // NCFL or NPFL (security users)
        ];
        return true;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        session_destroy();
    }
}
