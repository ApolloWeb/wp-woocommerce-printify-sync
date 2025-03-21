<?php
/**
 * Shipping admin page.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Pages
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\TemplateRenderer;
use ApolloWeb\WPWooCommercePrintifySync\Shipping\ShippingManager;

/**
 * Shipping admin page class.
 */
class Shipping {
    /**
     * PrintifyAPI instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * TemplateRenderer instance.
     *
     * @var TemplateRenderer
     */
    private $template;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * ShippingManager instance.
     *
     * @var ShippingManager
     */
    private $shipping_manager;

    /**
     * Constructor.
     *
     * @param PrintifyAPI      $api             PrintifyAPI instance.
     * @param TemplateRenderer $template        TemplateRenderer instance.
     * @param Logger           $logger          Logger instance.
     * @param ShippingManager  $shipping_manager ShippingManager instance.
     */
    public function __construct(PrintifyAPI $api, TemplateRenderer $template, Logger $logger, ShippingManager $shipping_manager) {
        $this->api = $api;
        $this->template = $template;
        $this->logger = $logger;
        $this->shipping_manager = $shipping_manager;
    }

    /**
     * Initialize shipping admin page.
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_wpwps_get_shipping_profiles', [$this, 'getShippingProfiles']);
        add_action('wp_ajax_wpwps_get_shipping_zones', [$this, 'getShippingZones']);
    }

    /**
     * Render shipping admin page.
     *
     * @return void
     */
    public function render() {
        $settings = get_option('wpwps_settings', []);
        $shop_id = isset($settings['shop_id']) ? $settings['shop_id'] : '';
        $shop_name = isset($settings['shop_name']) ? $settings['shop_name'] : '';
        
        // Get shipping profiles and zones
        $shipping_profiles = get_option('wpwps_shipping_profiles', []);
        $shipping_zones = \WC_Shipping_Zones::get_zones();
        
        $this->template->render('shipping', [
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
            'shipping_profiles' => $shipping_profiles,
            'shipping_zones' => $shipping_zones,
            'nonce' => wp_create_nonce('wpwps_shipping_nonce'),
            'has_maxmind' => $this->hasMaxMindDatabase(),
        ]);
    }

    /**
     * Check if MaxMind database exists.
     *
     * @return bool
     */
    private function hasMaxMindDatabase() {
        $database_path = WP_CONTENT_DIR . '/uploads/maxmind/GeoLite2-City.mmdb';
        return file_exists($database_path);
    }

    /**
     * Get shipping profiles.
     *
     * @return void
     */
    public function getShippingProfiles() {
        check_ajax_referer('wpwps_shipping_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }
        
        $profiles = $this->shipping_manager->getProfileManager()->getShippingProfiles();
        
        wp_send_json_success([
            'profiles' => $profiles,
        ]);
    }

    /**
     * Get shipping zones.
     *
     * @return void
     */
    public function getShippingZones() {
        check_ajax_referer('wpwps_shipping_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }
        
        $zones = \WC_Shipping_Zones::get_zones();
        $formatted_zones = [];
        
        foreach ($zones as $zone) {
            $zone_obj = new \WC_Shipping_Zone($zone['id']);
            $shipping_methods = $zone_obj->get_shipping_methods();
            $has_printify_method = false;
            
            foreach ($shipping_methods as $method) {
                if ($method->id === 'printify_shipping') {
                    $has_printify_method = true;
                    break;
                }
            }
            
            $formatted_zones[] = [
                'id' => $zone['id'],
                'name' => $zone['zone_name'],
                'locations' => $zone['formatted_zone_location'],
                'has_printify_method' => $has_printify_method,
            ];
        }
        
        wp_send_json_success([
            'zones' => $formatted_zones,
        ]);
    }
}
