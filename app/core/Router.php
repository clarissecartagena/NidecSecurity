<?php

namespace App\Core;

use Exception;

/**
 * Router Class
 *
 * Handles route registration and dispatching with support for:
 * - Closure handlers
 * - Controller@method notation
 * - Dependency injection via Container
 */
class Router
{
    /** @var array<string, array<string, callable|string>> */
    private array $routes = [];

    private Request $request;
    private Response $response;
    private ?Container $container;

    public function __construct(Request $request, Response $response, ?Container $container = null)
    {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
    }

    public function get(string $path, callable|string $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, callable|string $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    public function map(string $method, string $path, callable|string $handler): void
    {
        $method = strtoupper($method);
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = $this->request->method();
        $path = $this->request->path();

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            $this->response->notFound();
            return;
        }

        // Handle closure
        if (is_callable($handler)) {
            $handler($this->request, $this->response);
            return;
        }

        // Handle Controller@method notation
        if (is_string($handler)) {
            $this->dispatchControllerAction($handler);
            return;
        }

        $this->response->notFound();
    }

    /**
     * Dispatch a controller action in the format: Controller@method
     * or ControllerClass@method
     */
    private function dispatchControllerAction(string $handler): void
    {
        // Parse controller@method
        $parts = explode('@', $handler);
        if (count($parts) !== 2) {
            throw new Exception("Invalid controller action format: {$handler}. Expected: Controller@method");
        }

        [$controllerName, $methodName] = $parts;

        // Resolve controller class name
        // Support both: "AuthController@login" and "App\Controllers\AuthController@login"
        if (!str_contains($controllerName, '\\')) {
            // Add namespace if not present
            $controllerClass = "App\\Controllers\\{$controllerName}";

            // Fallback to legacy non-namespaced class for backward compatibility
            if (!class_exists($controllerClass) && class_exists($controllerName)) {
                $controllerClass = $controllerName;
            }
        } else {
            $controllerClass = $controllerName;
        }

        // Instantiate controller
        if ($this->container) {
            try {
                $controller = $this->container->resolve($controllerClass);
            } catch (Exception $e) {
                // Fallback to manual instantiation if DI fails
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                } else {
                    throw new Exception("Controller class not found: {$controllerClass}");
                }
            }
        } else {
            if (!class_exists($controllerClass)) {
                throw new Exception("Controller class not found: {$controllerClass}");
            }
            $controller = new $controllerClass();
        }

        // Call the method
        if (!method_exists($controller, $methodName)) {
            throw new Exception("Method {$methodName} not found in controller {$controllerClass}");
        }

        // Call with Request and Response if the controller accepts them
        $controller->$methodName();
    }
}
