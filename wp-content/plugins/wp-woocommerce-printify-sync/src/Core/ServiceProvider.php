<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

interface ServiceProvider
{
    /**
     * Register the service provider.
     * 
     * @return void
     */
    public function register(): void;
    
    /**
     * Bootstrap any application services when the plugin is activated.
     *
     * @return void
     */
    public function bootActivation(): void;
    
    /**
     * Bootstrap any application services when the plugin is deactivated.
     *
     * @return void
     */
    public function bootDeactivation(): void;
}