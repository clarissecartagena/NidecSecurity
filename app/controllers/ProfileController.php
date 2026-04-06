<?php

namespace App\Controllers;

class ProfileController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'My Profile';
        $currentPage = 'profile.php';

        require_once __DIR__ . '/../../includes/config.php';

        if (!isAuthenticated()) {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $currentUser = getUser();
        $employeeNo = (string) ($currentUser['employee_no'] ?? '');

        $flash = null;
        $flashType = 'success';

        // Fetch fresh user data from DB (session may not have email/signature_path).
        // Falls back to a query without signature_path when migration 005 has not yet
        // been applied to the database (prevents fatal PDOException on older installs).
        $dbUser = $this->fetchUser('employee_no', $employeeNo !== '' ? $employeeNo : null);

        // Fallback: stale sessions (created before schema migration) may have
        // employee_no = '' — look up by username and refresh the session.
        if (!$dbUser && !empty($currentUser['username'])) {
            $dbUser = $this->fetchUser('username', $currentUser['username']);
            if ($dbUser) {
                $employeeNo = $dbUser['employee_no'];
                $_SESSION['user']['employee_no'] = $employeeNo;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = (string) ($_POST['csrf_token'] ?? '');
            if (!csrf_validate($token)) {
                $flash = 'Security check failed. Please refresh and try again.';
                $flashType = 'error';
            } else {
                $action = (string) ($_POST['action'] ?? '');

                if ($action === 'update_email') {
                    $newEmail = trim((string) ($_POST['email'] ?? ''));
                    if ($newEmail === '') {
                        $flash = 'Email address cannot be empty.';
                        $flashType = 'error';
                    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        $flash = 'Please enter a valid email address.';
                        $flashType = 'error';
                    } else {
                        db_execute('UPDATE users SET email = ? WHERE employee_no = ? LIMIT 1', 'ss', [
                            $newEmail,
                            $employeeNo,
                        ]);
                        // Refresh db user
                        $dbUser['email'] = $newEmail;
                        $flash = 'Email updated successfully.';
                        $flashType = 'success';
                    }
                }
            }

            // Re-fetch after possible update
            $dbUser = $this->fetchUser('employee_no', $employeeNo !== '' ? $employeeNo : null);
            if (!$dbUser && !empty($currentUser['username'])) {
                $dbUser = $this->fetchUser('username', $currentUser['username']);
            }
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/profile/profile.php';
    }

    /**
     * Fetch a user row by either 'employee_no' or 'username'.
     * Tries to include signature_path first; falls back to a query without it
     * so the profile page still loads on databases where migration 005 has not
     * yet been applied.
     */
    private function fetchUser(string $column, ?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Whitelist allowed column names to prevent SQL injection
        $allowed = ['employee_no', 'username'];
        if (!in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid column: {$column}");
        }

        // Try full query including signature_path
        try {
            return db_fetch_one(
                "SELECT employee_no, name, email, signature_path, role, username, position, department FROM users WHERE {$column} = ? LIMIT 1",
                's',
                [$value],
            );
        } catch (\PDOException $e) {
            // SQLSTATE 42S22 = "Unknown column in field list"
            // This happens when migration 005 has not yet been applied.
            // Any other error is unrelated and should propagate.
            if ($e->getCode() !== '42S22') {
                throw $e;
            }
        }

        // Fallback: query without signature_path, inject null so view logic works
        $row = db_fetch_one(
            "SELECT employee_no, name, email, role, username, position, department FROM users WHERE {$column} = ? LIMIT 1",
            's',
            [$value],
        );
        if ($row !== null) {
            $row['signature_path'] = null;
        }
        return $row;
    }
}
