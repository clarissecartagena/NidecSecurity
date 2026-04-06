<?php

namespace App\Core;

use ReflectionClass;
use ReflectionException;
use Exception;

/**
 * Dependency Injection Container
 *
 * Provides dependency injection with auto-resolution via reflection,
 * singleton pattern support, and factory registrations.
 */
class Container
{
    /** @var array<string, callable> Factory definitions */
    private array $bindings = [];

    /** @var array<string, object> Singleton instances */
    private array $instances = [];

    /**
     * Bind a factory callable for a class or interface.
     *
     * @param string $abstract The class or interface name
     * @param callable $factory Factory callable that returns an instance
     * @return void
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Register a singleton factory. The factory will only be called once.
     *
     * @param string $abstract The class or interface name
     * @param callable $factory Factory callable that returns an instance
     * @return void
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->bind($abstract, function () use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($this);
            }
            return $this->instances[$abstract];
        });
    }

    /**
     * Resolve a class from the container with auto-resolution.
     *
     * @param string $abstract The class or interface name
     * @return object The resolved instance
     * @throws Exception If the class cannot be resolved
     */
    public function resolve(string $abstract): object
    {
        // Check if we have a binding
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]($this);
        }

        // Check if we already have a singleton instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Auto-resolve via reflection
        return $this->build($abstract);
    }

    /**
     * Build a class instance with automatic dependency injection.
     *
     * @param string $concrete The class name to build
     * @return object The built instance
     * @throws Exception If the class cannot be built
     */
    private function build(string $concrete): object
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("Target class [{$concrete}] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target class [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // No constructor, just instantiate
        if (is_null($constructor)) {
            return new $concrete();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // Handle nullable or no type hint
            if ($type === null || $parameter->allowsNull()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    $dependencies[] = null;
                }
                continue;
            }

            // Get the type name
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if ($typeName === null || $type->isBuiltin()) {
                // Primitive type - use default value if available
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception(
                        "Cannot resolve primitive parameter [{$parameter->getName()}] in class [{$concrete}]",
                    );
                }
            } else {
                // Class type - resolve it recursively
                $dependencies[] = $this->resolve($typeName);
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Check if a binding or instance exists.
     *
     * @param string $abstract The class or interface name
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Register an existing instance as a singleton.
     *
     * @param string $abstract The class or interface name
     * @param object $instance The instance to register
     * @return void
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }
}
