<?php
/**
 * Dependency Injection Container
 *
 * Manages class dependencies and service instantiation.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 * @updated 2025-03-09 13:43:45
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Container Class
 */
class Container {
    // ...existing code...

    /**
     * Bootstrap the plugin
     *
     * @return void
     */
    public function bootstrap() {
        // Initialize a simple logger first without using the container
        $this->initLogger();
        
        // Initialize other components - REMOVED bootstrap log line
        $this->initComponents();
        
        // Initialize admin components
        $this->initAdminComponents();
        
        // Register hooks
        $this->registerHooks();
    }
    
    // ...rest of the class remains the same...
}