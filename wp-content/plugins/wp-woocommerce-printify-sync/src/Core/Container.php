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
     * @param string  $abstract Abstract type to bind.
     * @param mixed   $concrete Concrete implementation.
     * @param boolean $shared   Whether the binding should be shared.
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
     * Determine if a given type has been bound.
     *
     * @param string $abstract Type to check.
     * @return bool
     */
    public function has($abstract)
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract   Abstract type to resolve.
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
     * @param string $concrete   Concrete type.
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

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new Exception("Class {$concrete} does not exist.");
        }

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface or Abstract Class and we cannot
        // resolve those types. So, we will throw an exception for them.
        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances without any dependencies or parameters
        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // If we have provided parameters, replace the auto-resolved dependencies
        // with the provided ones
        $instances = $this->getDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Get all dependencies for a given method's parameters.
     *
     * @param array $parameters Method parameters.
     * @param array $primitives Primitive values to use.
     * @return array Array of dependencies.
     * @throws Exception If a dependency cannot be resolved.
     */
    protected function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            // If the parameter has a type hint, we will try to resolve it from the container
            $dependency = $parameter->getClass();

            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            } elseif (is_null($dependency)) {
                // If there is no type hint, we will assume it's a primitive type such as a string
                // or an integer, and we will resolve it from the primitive parameters.
                $dependencies[] = $this->resolvePrimitive($parameter);
            } else {
                // If the class exists, we will try to resolve it from the container
                $dependencies[] = $this->make($dependency->name);
            }
        }

        return $dependencies;
    }

    /**
     * Resolve a primitive parameter.
     *
     * @param ReflectionParameter $parameter Parameter to resolve.
     * @return mixed
     * @throws Exception If the parameter cannot be resolved.
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception("Unresolvable dependency: parameter [{$parameter->name}] has no default value.");
    }

    /**
     * Register an existing instance in the container.
     *
     * @param string $abstract Abstract type to bind.
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
