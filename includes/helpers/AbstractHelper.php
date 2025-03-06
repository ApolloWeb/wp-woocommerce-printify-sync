<?php
/**
 * Abstract Helper class - Base for all helper classes
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */
 
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

abstract class AbstractHelper {
    /**
     * Get the current timestamp in MySQL format
     *
     * @return string Current timestamp in Y-m-d H:i:s format
     */
    protected function getCurrentTimestamp() {
        return current_time('mysql', true); // Returns Y-m-d H:i:s in UTC
    }
    
    /**
     * Get the current WordPress user login
     *
     *