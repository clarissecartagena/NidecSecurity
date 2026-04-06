<?php

namespace App\Core;

/**
 * Response Class
 *
 * Handles HTTP responses with helper methods for JSON, redirects,
 * view rendering, and status codes.
 */
class Response
{
    /**
     * Redirect to a URL.
     */
    public function redirect(string $location, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header('Location: ' . $location);
        exit();
    }

    /**
     * Send a JSON response.
     */
    public function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * Render a view template.
     */
    public function view(string $viewPath, array $data = []): void
    {
        extract($data);
        $fullPath = __DIR__ . '/../../views/' . $viewPath;

        if (!file_exists($fullPath)) {
            throw new \Exception("View file not found: {$fullPath}");
        }

        require $fullPath;
        exit();
    }

    /**
     * Send a text response.
     */
    public function text(string $content, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/plain');
        echo $content;
        exit();
    }

    /**
     * Send an HTML response.
     */
    public function html(string $content, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
        exit();
    }

    /**
     * Set response status code.
     */
    public function status(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Set a response header.
     */
    public function header(string $name, string $value): self
    {
        header("{$name}: {$value}");
        return $this;
    }

    /**
     * Send a 404 Not Found response.
     */
    public function notFound(): void
    {
        http_response_code(404);
        // For backward compatibility, load the NotFoundController
        // In future, this could be replaced with a view
        if (class_exists('NotFoundController')) {
            (new \NotFoundController())->index();
        } elseif (class_exists('App\\Controllers\\NotFoundController')) {
            (new \App\Controllers\NotFoundController())->index();
        } else {
            echo '404 Not Found';
        }
        exit();
    }

    /**
     * Send back with data.
     */
    public function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }
}
