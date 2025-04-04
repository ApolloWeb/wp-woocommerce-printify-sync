<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Security\Encryption;
use ApolloWeb\WPWooCommercePrintifySync\Database\Installer;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\View;

class Application {
    private array $bindings = [];
    private array $instances = [];
    private array $providers = [];

    public function __construct() 
    {
        $this->registerCoreServices();
    }

    private function registerCoreServices(): void 
    {
        // Register encryption service
        $this->bind('encryption', function() {
            return new Encryption();
        });

        // Register config factory as singleton
        $this->bind('config', function() {
            return \ApolloWeb\WPWooCommercePrintifySync\Factories\ConfigFactory::getInstance();
        });

        // Register rate limiter
        $this->singleton('rate_limiter', function() {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\RateLimiter();
        });

        // Register currency converter
        $this->singleton('currency_converter', function() {
            return new \ApolloWeb\WPWooCommercePrintifySync\Services\Shipping\CurrencyConverter();
        });

        // Register database installer
        $this->singleton('installer', function() {
            return new Installer();
        });

        // Register view helper
        $this->singleton('view', function() {
            return new View();
        });

        // Install database tables if needed
        $this->installDatabase();
    }

    /**
     * Install database tables if they don't exist
     */
    private function installDatabase(): void 
    {
        $installer = $this->make('installer');
        $installer->install();
    }

    public function bind(string $abstract, $concrete): void 
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, $concrete = null): void 
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bind($abstract, function() use ($concrete) {
            static $instance;
            
            if (is_null($instance)) {
                if ($concrete instanceof \Closure) {
                    $instance = $concrete($this);
                } else {
                    $instance = new $concrete();
                }
            }
            
            return $instance;
        });
    }

    public function make(string $abstract) 
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            throw new \RuntimeException("Service {$abstract} not found");
        }

        $concrete = $this->bindings[$abstract];
        
        if ($concrete instanceof \Closure) {
            $instance = $concrete($this);
        } else {
            $instance = new $concrete();
        }

        $this->instances[$abstract] = $instance;
        
        return $instance;
    }

    public function register(string $provider): void 
    {
        $provider = new $provider($this);
        
        if (method_exists($provider, 'register')) {
            $provider->register();
        }
        
        $this->providers[] = $provider;
    }

    public function boot(): void 
    {
        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }
}
