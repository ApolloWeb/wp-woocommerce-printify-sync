<?php
/**
 * Container Helper.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

/**
 * Service Container class.
 */
class Container {
    /**
     * Services registry.
     *
     * @var array
     */
    private $services = [];

    /**
     * Instantiated services.
     *
     * @var array
     */
    private $instances = [];

    /**
     * Register a service.
     *
     * @param string   $name    Service name.
     * @param callable $factory Service factory function.
     * @return void
     */
    public function register($name, callable $factory) {
        $this->services[$name] = $factory;
    }

    /**
     * Get a service.
     *
     * @param string $name Service name.
     * @return mixed
     *
     * @throws \Exception If service doesn't exist.
     */
    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new \Exception("Service '{$name}' not found in container.");
        }

        if (!isset($this->instances[$name])) {
            $this->instances[$name] = $this->services[$name]();
        }

        return $this->instances[$name];
    }

    /**
     * Check if service exists.
     *
     * @param string $name Service name.
     * @return bool
     */
    public function has($name) {
        return isset($this->services[$name]);
    }
}
