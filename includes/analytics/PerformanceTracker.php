<?php
/**
 * Performance Tracker
 *
 * Tracks plugin performance and provides analytics.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Analytics
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Analytics;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PerformanceTracker {
    /**
     * Singleton instance
     *
     * @var PerformanceTracker
     */
    private static $instance = null;
    
    /**
     * Performance data
     *
     * @var array
     */
    private $data = array();
    
    /**
     * Get singleton instance
     *
     * @return PerformanceTracker
     */
    public static function get_instance() {
        if (self::$instance === null) {