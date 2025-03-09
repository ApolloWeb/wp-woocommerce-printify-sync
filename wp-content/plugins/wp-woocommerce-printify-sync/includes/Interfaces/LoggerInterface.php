<?php
/**
 * Logger Interface
 *
 * Defines methods that must be implemented by any logger class.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Interfaces
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 * @updated 2025-03-09 12:51:21
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Interfaces;

/**
 * LoggerInterface Interface
 */
interface LoggerInterface {
    /**
     * Log a message at the specified level
     *
     * @param string $message The log message
     * @param string $level The log level
     * @param array $context Additional context
     * @return void
     */
    public function log($message, $level = 'info', $context = []);
    
    /**
     * Log an emergency message
     *
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    public function emergency($message, $context = []);
    
    /**
     * Log an alert message
     *
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    public function alert($message, $context = []);
    
    /**
     * Log a critical message
     *
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    public function critical($message, $context = []);
    
    /**
     * Log an error message
     *
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    public function error($message, $context = []);
    
    /**
     * Log a warning message
     *
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    public function warning($message, $context = []);
    
    /**
     * Log a notice message
     *
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    public function notice($message, $context = []);
    
    /**
     * Log an info message
     *
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    public function info($message, $context = []);
    
    /**
     * Log a debug message
     *
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    public function debug($message, $context = []);
}