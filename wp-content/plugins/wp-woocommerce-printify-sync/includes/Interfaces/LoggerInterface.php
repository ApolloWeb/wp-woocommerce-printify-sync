<?php
/**
 * Logger Interface
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Interfaces
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Interfaces;

/**
 * LoggerInterface Interface
 */
interface LoggerInterface {
    /**
     * Log a message
     *
     * @param string $message Log message
     * @param string $level Log level
     * @param array  $context Additional context
     * @return void
     */
    public function log($message, $level = 'info', $context = []);
    
    /**
     * Log an error
     *
     * @param string $message Error message
     * @param array  $context Additional context
     * @return void
     */
    public function error($message, $context = []);
    
    /**
     * Log a warning
     *
     * @param string $message Warning message
     * @param array  $context Additional context
     * @return void
     */
    public function warning($message, $context = []);
    
    /**
     * Log info
     *
     * @param string $message Info message
     * @param array  $context Additional context
     * @return void
     */
    public function info($message, $context = []);
    
    /**
     * Log debug information
     *
     * @param string $message Debug message
     * @param array  $context Additional context
     * @return void
     */
    public function debug($message, $context = []);
}