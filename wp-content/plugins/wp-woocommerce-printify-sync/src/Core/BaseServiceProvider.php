<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

abstract class BaseServiceProvider implements ServiceProvider
{
    /**
     * Bootstrap any application services when the plugin is activated.
     * 
     * This method will be called when the plugin is activated.
     * Use it to set up things like scheduled tasks, create tables, etc.
     * 
     * @return void
     */
    public function bootActivation(): void
    {
        // Default implementation is empty - override this method in child classes if needed
    }
    
    /**
     * Bootstrap any application services when the plugin is deactivated.
     * 
     * This method will be called when the plugin is deactivated.
     * Use it to clean up things like scheduled tasks, etc.
     * 
     * @return void
     */
    public function bootDeactivation(): void
    {
        // Default implementation is empty - override this method in child classes if needed
    }
}