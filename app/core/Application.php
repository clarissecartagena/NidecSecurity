<?php

namespace App\Core;

/**
 * Main Application Class
 *
 * Manages the application lifecycle, bootstraps the DI container,
 * and handles request/response flow.
 */
class Application
{
    private Container $container;
    private Router $router;

    public function __construct()
    {
        $this->container = new Container();
        $this->bootstrap();
    }

    /**
     * Bootstrap the application and register core services.
     */
    private function bootstrap(): void
    {
        // Register Request as a singleton
        $this->container->singleton(Request::class, function ($container) {
            return new Request();
        });

        // Register Response as a singleton
        $this->container->singleton(Response::class, function ($container) {
            return new Response();
        });

        // Register Router
        $this->container->singleton(Router::class, function ($container) {
            return new Router($container->resolve(Request::class), $container->resolve(Response::class), $container);
        });

        // Get router instance
        $this->router = $this->container->resolve(Router::class);
    }

    /**
     * Get the DI container instance.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get the Router instance.
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Run the application by dispatching the current request.
     */
    public function run(): void
    {
        $this->router->dispatch();
    }
}
