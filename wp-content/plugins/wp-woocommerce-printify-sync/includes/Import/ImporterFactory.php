<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Import;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

/**
 * Importer Factory - Creates importer instances with proper dependency injection
 * Follows Dependency Inversion Principle by abstracting creation of complex objects
 */
class ImporterFactory
{
    /**
     * Create a new ProductImporter instance
     * 
     * @return ProductImporter
     */
    public static function createProductImporter(): ProductImporter
    {
        $settings = new Settings();
        $api = new PrintifyAPI($settings);
        $priceConverter = new PriceConverter();
        
        return new ProductImporter($api, $settings, $priceConverter);
    }
    
    /**
     * Initialize Action Scheduler
     */
    public static function initActionScheduler(): void
    {
        // Check if the class exists before calling its methods
        if (class_exists('ApolloWeb\\WPWooCommercePrintifySync\\Import\\ActionSchedulerIntegration')) {
            ActionSchedulerIntegration::init();
        } else {
            // Log an error
            error_log('WP WooCommerce Printify Sync: ActionSchedulerIntegration class not found.');
            
            // Add an admin notice
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p>
                        <strong>WP WooCommerce Printify Sync:</strong> 
                        <?php _e('ActionSchedulerIntegration class not found. Some functionality may be limited.', 'wp-woocommerce-printify-sync'); ?>
                    </p>
                </div>
                <?php
            });
        }
    }
}
