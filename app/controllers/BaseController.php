<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Base Controller
 *
 * Provides common functionality for all controllers including:
 * - View rendering
 * - JSON responses
 * - Redirects
 * - Input validation
 * - Helper methods
 */
abstract class BaseController
{
    protected Request $request;
    protected Response $response;

    public function __construct()
    {
        // For backward compatibility, create Request and Response if not using DI
        $this->request = new Request();
        $this->response = new Response();
    }

    /**
     * Render a view template with data.
     *
     * @param string $viewPath Path to view file relative to views directory
     * @param array $data Data to pass to the view
     * @return void
     */
    protected function render(string $viewPath, array $data = []): void
    {
        extract($data);
        $fullPath = __DIR__ . '/../../views/' . $viewPath;

        if (!file_exists($fullPath)) {
            throw new \Exception("View file not found: {$fullPath}");
        }

        require $fullPath;
    }

    /**
     * Send a JSON response.
     *
     * @param mixed $data Data to send as JSON
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * Redirect to a URL.
     *
     * @param string $location URL to redirect to
     * @param int $statusCode HTTP status code (default 302)
     * @return void
     */
    protected function redirect(string $location, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header('Location: ' . $location);
        exit();
    }

    /**
     * Redirect back to the previous page.
     *
     * @return void
     */
    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    /**
     * Validate input data against rules.
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Array of validation errors (empty if valid)
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                // Parse rule and parameters
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramString] = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                // Apply validation rules
                switch ($rule) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field][] = ucfirst($field) . ' is required.';
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = ucfirst($field) . ' must be a valid email address.';
                        }
                        break;

                    case 'min':
                        $min = (int) ($params[0] ?? 0);
                        if (!empty($value) && strlen($value) < $min) {
                            $errors[$field][] = ucfirst($field) . " must be at least {$min} characters.";
                        }
                        break;

                    case 'max':
                        $max = (int) ($params[0] ?? 0);
                        if (!empty($value) && strlen($value) > $max) {
                            $errors[$field][] = ucfirst($field) . " must not exceed {$max} characters.";
                        }
                        break;

                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field][] = ucfirst($field) . ' must be numeric.';
                        }
                        break;

                    case 'alpha':
                        if (!empty($value) && !ctype_alpha($value)) {
                            $errors[$field][] = ucfirst($field) . ' must contain only letters.';
                        }
                        break;

                    case 'alphanumeric':
                        if (!empty($value) && !ctype_alnum($value)) {
                            $errors[$field][] = ucfirst($field) . ' must contain only letters and numbers.';
                        }
                        break;

                    case 'in':
                        if (!empty($value) && !in_array($value, $params, true)) {
                            $errors[$field][] = ucfirst($field) . ' must be one of: ' . implode(', ', $params) . '.';
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * Get input from request (POST takes priority over GET).
     *
     * @param string $key Input key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? ($_GET[$key] ?? $default);
    }

    /**
     * Get all input data.
     *
     * @return array
     */
    protected function allInput(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Check if user is authenticated.
     *
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Get current authenticated user.
     *
     * @return array|null
     */
    protected function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Check if current user has a specific role.
     *
     * @param string|array $roles Role(s) to check
     * @return bool
     */
    protected function hasRole(string|array $roles): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        $userRole = $user['role'] ?? null;
        if (is_array($roles)) {
            return in_array($userRole, $roles, true);
        }

        return $userRole === $roles;
    }

    /**
     * Require authentication - redirect to login if not authenticated.
     *
     * @return void
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login.php');
        }
    }

    /**
     * Require specific role - redirect to login if not authorized.
     *
     * @param string|array $roles Required role(s)
     * @return void
     */
    protected function requireRole(string|array $roles): void
    {
        $this->requireAuth();

        if (!$this->hasRole($roles)) {
            http_response_code(403);
            die('Access denied. You do not have permission to access this page.');
        }
    }
}
