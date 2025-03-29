<?php
/**
 * Service Provider Abstract Class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Abstract ServiceProvider class for plugin component registration
 */
abstract class ServiceProvider
{
    /**
     * Register the service provider
     *
     * @return void
     */
    abstract public function register();
    
    /**
     * Get the plugin instance
     *
     * @return Plugin
     */
    protected function getPluginInstance()
    {
        global $wpwps_plugin_instance;
        return $wpwps_plugin_instance;
    }
    
    /**
     * Check if the current user has the specified capability
     *
     * @param string $capability The capability to check for
     * @return bool
     */
    protected function userCan($capability = 'manage_options')
    {
        return current_user_can($capability);
    }
    
    /**
     * Get the URL for a view
     *
     * @param string $view The view name
     * @return string
     */
    protected function getViewUrl($view)
    {
        return admin_url('admin.php?page=' . $view);
    }
}