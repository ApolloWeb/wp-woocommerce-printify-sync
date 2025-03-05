<?php
/**
 * Log Helper - Compatibility wrapper for new logging system
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LogViewer;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LogExporter;
use ApolloWeb\WPWooCommercePrintifySync\Logging\LogCleaner;

/**
 * Wrapper class to maintain compatibility with existing code
 * while using the new modular logging system
 */
class LogHelper {
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {}
    
    /**
     * Log a debug message
     */
    public function debug($message, $context = []) {
        return Logger::getInstance()->debug($message, $context);
    }
    
    /**
     * Log an info message
     */
    public function info($message, $context = []) {
        return Logger::getInstance()->info($message, $context);
    }
    
    /**
     * Log a notice message
     */
    public function notice($message, $context = []) {
        return Logger::getInstance()->notice($message, $context);
    }
    
    /**
     * Log a warning message
     */
    public function warning($message, $context = []) {
        return Logger::getInstance()->warning($message, $context);
    }
    
    /**
     * Log an error message
     */
    public function error($message, $context = []) {
        return Logger::getInstance()->error($message, $context);
    }
    
    /**
     * Log a critical message
     */
    public function critical($message, $context = []) {
        return Logger::getInstance()->critical($message, $context);
    }
    
    /**
     * Log an alert message
     */
    public function alert($message, $context = []) {
        return Logger::getInstance()->alert($message, $context);
    }
    
    /**
     * Log an emergency message
     */
    public function emergency($message, $context = []) {
        return Logger::getInstance()->emergency($message, $context);
    }
    
    /**
     * Get logs with filtering
     */
    public function getLogs($level = '', $date_from = '', $date_to = '', $context = '', $search = '', $page = 1, $per_page = 30) {
        return LogViewer::getInstance()->getLogs($level, $date_from, $date_to, $context, $search, $page, $per_page);
    }
    
    /**
     * Get total logs count with filters
     */
    public function getLogsCount($level = '', $date_from = '', $date_to = '', $context = '', $search = '') {
        return LogViewer::getInstance()->getLogsCount($level, $date_from, $date_to, $context, $search);
    }
    
    /**
     * Get unique log contexts
     */
    public function getLogContexts() {
        return LogViewer::getInstance()->getLogContexts();
    }
    
    /**
     * Export logs to file
     */
    public function exportLogs($level = '', $date_from = '', $date_to = '', $context = '', $search = '') {
        return LogExporter::getInstance()->exportLogs($level, $date_from, $date_to, $context, $search);
    }
    
    /**
     * Clean logs older than specified days
     */
    public function cleanLogs($days = 14) {
        return LogCleaner::getInstance()->cleanLogs($days);
    }
    
    /**
     * Scheduled cleanup task
     */
    public function scheduledCleanup() {
        return LogCleaner::getInstance()->scheduledCleanup();
    }
}