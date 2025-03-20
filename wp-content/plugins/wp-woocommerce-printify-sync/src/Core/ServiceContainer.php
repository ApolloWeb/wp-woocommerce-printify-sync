<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Simple service container for dependency injection
 */
class ServiceContainer
{
    private $services = [];
    
    /**
     * Register a service in the container
     *
     * @param string $id Service identifier
     * @param mixed $service Service instance or factory callback
     * @return void
     */
    public function set(string $id, $service): void
    {
        $this->services[$id] = $service;
    }
    
    /**
     * Get a service from the container
     *
     * @param string $id Service identifier
     * @return mixed The service instance
     * @throws \Exception If service not found
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new \Exception("Service '{$id}' not found in the container");
        }
        
        $service = $this->services[$id];
        
        // If service is a factory callback, execute it
        if (is_callable($service)) {
            $service = $service($this);
            $this->services[$id] = $service; // Cache the instance
        }
        
        return $service;
    }
    
    /**
     * Check if a service exists in the container
     *
     * @param string $id Service identifier
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
