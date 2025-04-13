<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Service Container for managing dependencies
 */
class ServiceContainer {
    /**
     * @var array
     */
    private $services = [];
    
    /**
     * @var array
     */
    private $instances = [];
    
    /**
     * Register a service
     *
     * @param string $name
     * @param callable $factory
     * @param bool $shared
     * @return void
     */
    public function register($name, callable $factory, $shared = true) {
        $this->services[$name] = [
            'factory' => $factory,
            'shared' => $shared
        ];
    }
    
    /**
     * Get a service
     *
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function get($name) {
        if (!isset($this->services[$name])) {
            throw new \Exception(sprintf('Service "%s" not found', $name));
        }
        
        $service = $this->services[$name];
        
        // Return cached instance if shared
        if ($service['shared'] && isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        
        $instance = call_user_func($service['factory'], $this);
        
        // Cache instance if shared
        if ($service['shared']) {
            $this->instances[$name] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Check if a service exists
     *
     * @param string $name
     * @return bool
     */
    public function has($name) {
        return isset($this->services[$name]);
    }
}
