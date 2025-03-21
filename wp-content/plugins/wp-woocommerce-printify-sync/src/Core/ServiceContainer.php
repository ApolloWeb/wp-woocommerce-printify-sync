<?php
/**
 * Service Container for Dependency Injection.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Service Container class for dependency injection.
 */
class ServiceContainer
{
    /**
     * Services stored in the container.
     *
     * @var array
     */
    private $services = [];
    
    /**
     * Service definitions.
     *
     * @var array
     */
    private $definitions = [];
    
    /**
     * Register a service.
     *
     * @param string $id    Service ID.
     * @param string $class Fully qualified class name.
     * @return ServiceDefinition
     */
    public function register($id, $class)
    {
        $definition = new ServiceDefinition($class);
        $this->definitions[$id] = $definition;
        
        return $definition;
    }
    
    /**
     * Get a service.
     *
     * @param string $id Service ID.
     * @return object The service instance.
     * @throws \Exception If service is not found.
     */
    public function get($id)
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }
        
        if (!isset($this->definitions[$id])) {
            throw new \Exception("Service '{$id}' not found.");
        }
        
        $this->services[$id] = $this->createService($this->definitions[$id]);
        
        return $this->services[$id];
    }
    
    /**
     * Create a service from its definition.
     *
     * @param ServiceDefinition $definition Service definition.
     * @return object The service instance.
     */
    private function createService(ServiceDefinition $definition)
    {
        $class = $definition->getClass();
        $arguments = [];
        
        foreach ($definition->getArguments() as $argument) {
            $arguments[] = $argument;
        }
        
        return new $class(...$arguments);
    }
}

/**
 * Service Definition class.
 */
class ServiceDefinition
{
    /**
     * Class name.
     *
     * @var string
     */
    private $class;
    
    /**
     * Constructor arguments.
     *
     * @var array
     */
    private $arguments = [];
    
    /**
     * Constructor.
     *
     * @param string $class Class name.
     */
    public function __construct($class)
    {
        $this->class = $class;
    }
    
    /**
     * Add an argument.
     *
     * @param mixed $argument Argument value.
     * @return self
     */
    public function addArgument($argument)
    {
        $this->arguments[] = $argument;
        return $this;
    }
    
    /**
     * Get the class name.
     *
     * @return string Class name.
     */
    public function getClass()
    {
        return $this->class;
    }
    
    /**
     * Get arguments.
     *
     * @return array Arguments.
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
