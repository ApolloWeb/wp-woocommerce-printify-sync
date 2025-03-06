<?php
/**
 * Autoloader Class
 *
 * A simple autoloader that follows WordPress naming conventions and can work with
 * both WordPress-style prefixed classes and modern namespaced classes.
 *
 * @package WP_WooCommerce_Printify_Sync
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPWPRINTIFYSYNC_Autoloader
 */
class WPWPRINTIFYSYNC_Autoloader {
    /**
     * Singleton instance
     *
     * @var WPWPRINTIFYSYNC_Autoloader
     */
    private static $instance = null;
    
    /**
     * Prefix mappings array
     *
     * @var array
     */
    private $prefix_mappings = array();
    
    /**
     * Namespace mappings array
     *
     * @var array
     */
    private $namespace_mappings = array();
    
    /**
     * Current timestamp for logging
     *
     * @var string
     */
    private $timestamp;
    
    /**
     * Current user for logging
     *
     * @var string
     */
    private $user;
    
    /**
     * Get singleton instance
     *
     * @return WPWPRINTIFYSYNC_Autoloader
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
        $this->timestamp = '2025-03-05 19:43:30';
        $this->user = 'ApolloWeb';
        
        // Add default mappings for plugin
        $this->add_prefix('WPWPRINTIFYSYNC_', WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/');
        
        // Add mappings for specific directories
        $this->add_prefix('WPWPRINTIFYSYNC_Admin_', WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/admin/');
        $this->add_prefix('WPWPRINTIFYSYNC_API_', WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/api/');
        $this->add_prefix('WPWPRINTIFYSYNC_Products_', WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/products/');
        $this->add_prefix('WPWPRINTIFYSYNC_Orders_', WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/orders/');
        $this->add_prefix('WPWPRINTIFYSYNC_Currency_', WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/currency/');
        
        // Add namespaced mappings for modern code
        $this->add_namespace('ApolloWeb\\WPWooCommercePrintifySync\\', WPWPRINTIFYSYNC_PLUGIN_DIR);
    }
    
    /**
     * Register the autoloader
     */
    public function register() {
        spl_autoload_register(array($this, 'load_class'));
    }
    
    /**
     * Add a class prefix mapping
     *
     * @param string $prefix Class prefix (e.g., 'WPWPRINTIFYSYNC_')
     * @param string $dir Directory where the classes are located
     */
    public function add_prefix($prefix, $dir) {
        // Normalize directory separators
        $dir = rtrim(trailingslashit($dir), '/\\');
        
        // Add mapping
        $this->prefix_mappings[$prefix] = $dir;
    }
    
    /**
     * Add a namespace mapping
     *
     * @param string $namespace Namespace prefix (e.g., 'ApolloWeb\\WPWooCommercePrintifySync\\')
     * @param string $dir Directory where the classes are located
     */
    public function add_namespace($namespace, $dir) {
        // Normalize directory separators and namespace
        $namespace = trim($namespace, '\\') . '\\';
        $dir = rtrim(trailingslashit($dir), '/\\');
        
        // Add mapping
        $this->namespace_mappings[$namespace] = $dir;
    }
    
    /**
     * Load class file
     *
     * @param string $class The fully-qualified class name
     * @return bool True if loaded, false otherwise
     */
    public function load_class($class) {
        // Try to load using namespace mappings first
        if ($this->load_namespaced_class($class)) {
            return true;
        }
        
        // Then try to load using prefix mappings
        return $this->load_prefixed_class($class);
    }
    
    /**
     * Load a namespaced class
     *
     * @param string $class The fully-qualified class name
     * @return bool True if loaded, false otherwise
     */
    private function load_namespaced_class($class) {
        // Loop through each namespace mapping
        foreach ($this->namespace_mappings as $namespace => $dir) {