<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Admin Controller - Single responsibility for initializing admin components
 */
class AdminController
{
    /**
     * Initialize the admin components
     * 
     * @return void
     */
    public function init(): void
    {
        // Check if the class exists before initializing it
        if (class_exists('ApolloWeb\\WPWooCommercePrintifySync\\Admin\\AdminMenu')) {
            // Initialize admin menu
            $menu = new AdminMenu();
            if (method_exists($menu, 'register')) {
                $menu->register();
            } else if (method_exists($menu, 'registerMenuPages')) {
                $menu->registerMenuPages();
            }
        }
        
        // Add admin notices for missing dependencies
        add_action('admin_notices', [$this, 'checkDependencies']);
    }
    
    /**
     * Check for plugin dependencies
     * 
     * @return void
     */
    public function checkDependencies(): void
    {
        // Check for WooCommerce
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>WP WooCommerce Printify Sync:</strong> 
                    <?php _e('WooCommerce is required for this plugin to work. Please install and activate WooCommerce.', 'wp-woocommerce-printify-sync'); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')); ?>" class="button button-primary">
                        <?php _e('Install WooCommerce', 'wp-woocommerce-printify-sync'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
        
        // Check for Action Scheduler
        if (!class_exists('ActionScheduler') && !function_exists('as_enqueue_async_action') && class_exists('WooCommerce')) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>WP WooCommerce Printify Sync:</strong> 
                    <?php _e('Action Scheduler is not available. Product import functionality may be limited.', 'wp-woocommerce-printify-sync'); ?>
                </p>
            </div>
            <?php
        }
    }
}
