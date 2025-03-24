<?php
/**
 * Settings page handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Plugin;
use ApolloWeb\WPWooCommercePrintifySync\Core\TemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Core\Encryption;

/**
 * Class Settings
 */
class Settings {
    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private $plugin;
    
    /**
     * Template engine
     *
     * @var TemplateEngine
     */
    private $template;

    /**
     * Constructor
     *
     * @param Plugin $plugin Plugin instance.
     */
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
        $this->template = new TemplateEngine();
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function render() {
        // Get current settings
        $encryption = new Encryption();
        
        $printify_api_key = get_option('wpwps_printify_api_key', '');
        $printify_api_key = !empty($printify_api_key) ? $encryption->decrypt($printify_api_key) : '';
        $printify_api_endpoint = get_option('wpwps_printify_api_endpoint', 'https://api.printify.com/v1');
        $printify_shop_id = get_option('wpwps_printify_shop_id', 0);
        
        $gpt_api_key = get_option('wpwps_gpt_api_key', '');
        $gpt_api_key = !empty($gpt_api_key) ? $encryption->decrypt($gpt_api_key) : '';
        $gpt_temperature = get_option('wpwps_gpt_temperature', 0.7);
        $gpt_monthly_cap = get_option('wpwps_gpt_monthly_cap', 0);
        
        $stock_sync_interval = get_option('wpwps_stock_sync_interval', 6);
        $email_queue_interval = get_option('wpwps_email_queue_interval', 5);
        
        // Render template
        $this->template->render('wpwps-admin/settings', [
            'printify_api_key' => $printify_api_key,
            'printify_api_endpoint' => $printify_api_endpoint,
            'printify_shop_id' => $printify_shop_id,
            'gpt_api_key' => $gpt_api_key,
            'gpt_temperature' => $gpt_temperature,
            'gpt_monthly_cap' => $gpt_monthly_cap,
            'stock_sync_interval' => $stock_sync_interval,
            'email_queue_interval' => $email_queue_interval,
        ]);
    }
}
