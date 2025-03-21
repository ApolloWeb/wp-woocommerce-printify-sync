<?php
/**
 * Shipping Manager.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Shipping
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Main class for managing Printify shipping functionality.
 */
class ShippingManager {
    /**
     * PrintifyAPI instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * ShippingProfileManager instance.
     *
     * @var ShippingProfileManager
     */
    private $profile_manager;

    /**
     * ShippingZoneManager instance.
     *
     * @var ShippingZoneManager
     */
    private $zone_manager;

    /**
     * CartHandler instance.
     *
     * @var CartHandler
     */
    private $cart_handler;

    /**
     * Constructor.
     *
     * @param PrintifyAPI $api    PrintifyAPI instance.
     * @param Logger      $logger Logger instance.
     */
    public function __construct(PrintifyAPI $api, Logger $logger) {
        $this->api = $api;
        $this->logger = $logger;
        
        // Create component instances
        $this->profile_manager = new ShippingProfileManager($api, $logger);
        $this->zone_manager = new ShippingZoneManager($this->profile_manager, $logger);
        $this->cart_handler = new CartHandler($logger);
        
        // Make profile manager available globally
        global $wpwps_shipping_profile_manager;
        $wpwps_shipping_profile_manager = $this->profile_manager;
    }

    /**
     * Initialize shipping manager.
     *
     * @return void
     */
    public function init() {
        // Initialize components
        $this->profile_manager->init();
        $this->zone_manager->init();
        $this->cart_handler->init();
        
        // Schedule daily sync
        if (!wp_next_scheduled('wpwps_daily_shipping_sync')) {
            wp_schedule_event(time(), 'daily', 'wpwps_daily_shipping_sync');
        }
        
        // Register action for daily sync
        add_action('wpwps_daily_shipping_sync', [$this, 'syncShipping']);
        
        // Add geolocation support for shipping
        add_filter('woocommerce_geolocate_ip', [$this, 'enhanceGeolocation'], 10, 2);
    }

    /**
     * Sync shipping profiles and zones.
     *
     * @return void
     */
    public function syncShipping() {
        $this->logger->info('Starting daily shipping sync');
        
        // Sync shipping profiles
        $profiles = $this->profile_manager->syncShippingProfiles(true);
        
        if (is_wp_error($profiles)) {
            $this->logger->error(
                'Failed to sync shipping profiles',
                ['error' => $profiles->get_error_message()]
            );
            return;
        }
        
        // Sync shipping zones
        $zones = $this->zone_manager->syncShippingZones();
        
        if (is_wp_error($zones)) {
            $this->logger->error(
                'Failed to sync shipping zones',
                ['error' => $zones->get_error_message()]
            );
            return;
        }
        
        $this->logger->info(
            'Completed daily shipping sync',
            [
                'profiles_count' => count($profiles),
                'zones_count' => count($zones),
            ]
        );
    }

    /**
     * Enhance WooCommerce geolocation with MaxMind.
     *
     * @param array  $geo_data Geolocation data.
     * @param string $ip_address IP address.
     * @return array
     */
    public function enhanceGeolocation($geo_data, $ip_address) {
        // Only enhance if current data doesn't have enough info
        if (empty($geo_data['country']) || empty($geo_data['state'])) {
            // Check if MaxMind database exists
            $database_path = WP_CONTENT_DIR . '/uploads/maxmind/GeoLite2-City.mmdb';
            
            if (file_exists($database_path) && class_exists('\\MaxMind\\Db\\Reader')) {
                try {
                    $reader = new \MaxMind\Db\Reader($database_path);
                    $record = $reader->get($ip_address);
                    
                    if ($record) {
                        // Update country if it exists
                        if (empty($geo_data['country']) && isset($record['country']['iso_code'])) {
                            $geo_data['country'] = $record['country']['iso_code'];
                        }
                        
                        // Update state if it exists
                        if (empty($geo_data['state']) && isset($record['subdivisions'][0]['iso_code']) && isset($record['country']['iso_code'])) {
                            $geo_data['state'] = $record['subdivisions'][0]['iso_code'];
                        }
                    }
                    
                    $reader->close();
                } catch (\Exception $e) {
                    $this->logger->error(
                        'MaxMind geolocation error',
                        ['error' => $e->getMessage()]
                    );
                }
            }
        }
        
        return $geo_data;
    }

    /**
     * Get ShippingProfileManager instance.
     *
     * @return ShippingProfileManager
     */
    public function getProfileManager() {
        return $this->profile_manager;
    }

    /**
     * Get ShippingZoneManager instance.
     *
     * @return ShippingZoneManager
     */
    public function getZoneManager() {
        return $this->zone_manager;
    }

    /**
     * Get CartHandler instance.
     *
     * @return CartHandler
     */
    public function getCartHandler() {
        return $this->cart_handler;
    }
}
