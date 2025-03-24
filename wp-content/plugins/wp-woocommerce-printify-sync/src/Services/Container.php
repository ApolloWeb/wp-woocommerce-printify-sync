<?php
/**
 * Service container
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Class Container
 *
 * Simple service container for dependency injection
 */
class Container
{
    /**
     * Service definitions
     *
     * @var array<string, callable>
     */
    private array $definitions = [];

    /**
     * Service instances
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Register a service definition
     *
     * @param string   $id        Service ID
     * @param callable $definition Service definition callback
     * @return void
     */
    public function register(string $id, callable $definition): void
    {
        $this->definitions[$id] = $definition;
    }

    /**
     * Get a service instance
     *
     * @param string $id Service ID
     * @return mixed
     * @throws \Exception If service is not found
     */
    public function get(string $id)
    {
        // Return existing instance if available
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Check if definition exists
        if (!isset($this->definitions[$id])) {
            throw new \Exception(sprintf('Service "%s" not found in container', $id));
        }

        // Create instance
        $this->instances[$id] = $this->definitions[$id]();

        return $this->instances[$id];
    }

    /**
     * Check if a service is registered
     *
     * @param string $id Service ID
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    /**
     * Remove a service from the container
     *
     * @param string $id Service ID
     * @return void
     */
    public function remove(string $id): void
    {
        unset($this->definitions[$id]);
        unset($this->instances[$id]);
    }
}
