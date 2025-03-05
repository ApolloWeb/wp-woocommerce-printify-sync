<?php
/**
 * Main Plugin Class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\API\Printify\PrintifyApiClient;
use ApolloWeb\WPWooCommercePrintifySync\API\WooCommerce\WooCommerceApiClient;
use ApolloWeb\WPWooCommercePrintifySync\API\Webhook\WebhookReceiver;
use ApolloWeb\WPWooCommercePrintifySync\Products\ProductManager;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderManager;
use ApolloWeb\WPWooCommercePrintifySync\Currency\CurrencyConverter;
use ApolloWeb\WPWooCommercePrintifySync\Geolocation\Geolocator;
use ApolloWeb\WPWooCommercePrintifySync\Analytics\VisitorTracker;
use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminManager;
use ApolloWeb\WPWooCommercePrintifySync\Frontend\PriceDisplayManager;
use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Plugin {
    /**
     * Singleton instance
     *
     * @var Plugin
     */
    private static $instance = null;
    
    /**
     * Current timestamp
     *
     * @var string
     */
    private $timestamp;
    
    /**
     * Current user
     *
     * @var string
     */
    private $user;
    
    /**
     * Get singleton instance
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->timestamp = '2025-03-05 19:44:17';
        $this->user = 'ApolloWeb';
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize logger first to capture any issues
        Logger::get_instance()->init();
        
        // Initialize API clients
        PrintifyApiClient::get_instance()->init();
        WooCommerceApiClient::get_instance()->init();
        
        // Initialize webhook receiver
        WebhookReceiver::get_instance()->init();
        
        // Initialize managers
        ProductManager::get_instance()->init();
        OrderManager::get_instance()->init();
        
        // Initialize currency and geolocation
        CurrencyConverter::get_instance()->init();
        Geolocator::get_instance()->init();
        
        // Initialize analytics
        VisitorTracker::get_instance()->init();
        
        // Initialize admin or frontend components based on context
        if (is_admin()) {
            AdminManager::get_instance()->init();
        } else {
            PriceDisplayManager::get_instance()->init();
        }
        
        // Log initialization
        Logger::get_instance()->info('Plugin initialized', [
            'timestamp' => $this->timestamp,
            'user' => $this->user,
            'version' => WPWPRINTIFYSYNC_VERSION
        ]);
    }
}