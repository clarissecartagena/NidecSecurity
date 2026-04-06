<?php

namespace App\Core;

/**
 * Request Class
 *
 * Handles HTTP request data and provides helper methods for accessing
 * request parameters, headers, and other request information.
 */
class Request
{
    public function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    /**
     * Returns the request path relative to the app's public directory.
     *
     * Supports two modes:
     *   1. Legacy ?r=/path query parameter (still honoured for compatibility).
     *   2. Apache RewriteRule front-controller: REQUEST_URI is stripped of the
     *      base directory (e.g. /NidecSecurity/public) so that a request for
     *      /NidecSecurity/public/login.php is normalised to /login.php.
     */
    public function path(): string
    {
        // Legacy mode: explicit ?r= parameter takes priority.
        $path = $_GET['r'] ?? null;
        if ($path !== null && $path !== '') {
            $path = '/' . trim((string) $path, '/');
            return $path === '//' ? '/' : $path;
        }

        $uriPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
        $uriPath = $uriPath === '' ? '/' : $uriPath;

        // Strip the base directory so that routes are registered as /login.php
        // instead of /NidecSecurity/public/login.php.
        //
        // scriptDir is always /NidecSecurity/public (SCRIPT_NAME = public/index.php).
        // When accessed via root .htaccess the REQUEST_URI has no /public segment,
        // so we fall back to stripping just the parent (project root) directory.
        $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        if ($scriptDir !== '' && $scriptDir !== '/') {
            if (str_starts_with($uriPath, $scriptDir . '/')) {
                // Standard: /NidecSecurity/public/login.php → /login.php
                $uriPath = substr($uriPath, strlen($scriptDir)) ?: '/';
            } elseif ($uriPath === $scriptDir) {
                $uriPath = '/';
            } else {
                // Root-access fallback: REQUEST_URI is /NidecSecurity/login.php
                // but scriptDir is /NidecSecurity/public — strip project root only.
                $parentDir = rtrim(dirname($scriptDir), '/');
                if ($parentDir !== '' && $parentDir !== '/') {
                    if (str_starts_with($uriPath, $parentDir . '/')) {
                        $uriPath = substr($uriPath, strlen($parentDir)) ?: '/';
                    } elseif ($uriPath === $parentDir) {
                        $uriPath = '/';
                    }
                }
            }
        }

        // Treat a direct request to index.php (or just /) as the root route.
        if ($uriPath === '/' || preg_match('#^/index\.php#i', $uriPath)) {
            return '/';
        }

        $path = '/' . trim($uriPath, '/');
        return $path === '//' ? '/' : $path;
    }

    /**
     * Get input from GET parameters.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get input from POST parameters.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get input from either POST or GET (POST takes priority).
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post($key) ?? ($this->get($key) ?? $default);
    }

    /**
     * Get all input data.
     */
    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Check if request is POST.
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Check if request is GET.
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * Check if request has a specific key.
     */
    public function has(string $key): bool
    {
        return isset($_GET[$key]) || isset($_POST[$key]);
    }

    /**
     * Get server variable.
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Get request header.
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $default;
    }
}
