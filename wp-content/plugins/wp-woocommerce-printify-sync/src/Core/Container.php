<?php
/**
 * Service Container for dependency injection.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use Closure;
use ReflectionClass;
use ReflectionParameter;
use Exception;

/**
 * Simple service container implementation for dependency injection.
 */
class Container
{
    /**
     * The container's bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Register a binding with the container.
     *
     * @param string $abstract Abstract type to bind.
     * @param mixed  $concrete Concrete implementation.
     * @param bool   $shared   Whether the binding should be shared.
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract Abstract type to bind.
     * @param mixed  $concrete Concrete implementation.
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract Abstract type to resolve.
     * @param array  $parameters Parameters to pass to the constructor.
     * @return mixed
     * @throws Exception If the type cannot be resolved.
     */
    public function make($abstract, array $parameters = [])
    {
        // If we have an instance in the container already, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete type for this abstract
        $concrete = $this->getConcrete($abstract);

        // If the type is actually a Closure, just execute it and return the result
        if ($concrete instanceof Closure) {
            $object = $concrete($this, $parameters);
        } else {
            $object = $this->build($concrete, $parameters);
        }

        // If this is a shared binding, store the instance
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param string $abstract Abstract type.
     * @return mixed
     */
    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Determine if a given type is shared.
     *
     * @param string $abstract Abstract type.
     * @return bool
     */
    protected function isShared($abstract)
    {
        return isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared'];
    }

    /**
     * Build a concrete type instance.
     *
     * @param string $concrete Concrete type.
     * @param array  $parameters Parameters to pass to the constructor.
     * @return mixed
     * @throws Exception If the type cannot be built.
     */
    protected function build($concrete, array $parameters = [])
    {
        // If the concrete type is actually a Closure, just execute it
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        // If the type is not instantiable, we can't build it
        if (!$reflector->isInstantiable()) {
            throw new Exception("Type {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        // If there is no constructor, just return a new instance
        if (is_null($constructor)) {
            return new $concrete();
        }

        // Get the constructor parameters
        $dependencies = $constructor->getParameters();

        // If we have no constructor dependencies, just return a new instance
        if (empty($dependencies)) {
            return new $concrete();
        }

        // Build the constructor arguments
        $instances = $this->resolveDependencies($dependencies, $parameters);

        // Create a new instance with the resolved dependencies
        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param array $dependencies Constructor dependencies.
     * @param array $parameters Parameters passed to make.
     * @return array
     * @throws Exception If a dependency cannot be resolved.
     */
    protected function resolveDependencies(array $dependencies, array $parameters)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If the dependency is in the parameters, use that
            if (array_key_exists($dependency->name, $parameters)) {
                $results[] = $parameters[$dependency->name];
                continue;
            }

            // If the parameter is a class, resolve it from the container
            $result = $this->resolveClass($dependency);

            if (!is_null($result)) {
                $results[] = $result;
            } elseif ($dependency->isDefaultValueAvailable()) {
                // If the dependency has a default value, use that
                $results[] = $dependency->getDefaultValue();
            } else {
                // We can't resolve the dependency
                throw new Exception("Unresolvable dependency: {$dependency->name}");
            }
        }

        return $results;
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param ReflectionParameter $parameter Parameter to resolve.
     * @return mixed
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        // If the parameter doesn't have a type hint, we can't resolve it
        if (!$type || $type->isBuiltin()) {
            return null;
        }

        // Get the class name from the type
        $class = $type->getName();

        // Try to resolve the class from the container
        try {
            return $this->make($class);
        } catch (Exception $e) {
            // If the class doesn't exist or can't be resolved, return null
            if ($parameter->isOptional()) {
                return null;
            }

            throw $e;
        }
    }

    /**
     * Determine if a given type has been resolved.
     *
     * @param string $abstract Abstract type.
     * @return bool
     */
    public function resolved($abstract)
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param string $abstract Abstract type.
     * @param mixed  $instance Instance to register.
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Get all registered instances.
     *
     * @return array
     */
    public function getInstances()
    {
        return $this->instances;
    }
}
