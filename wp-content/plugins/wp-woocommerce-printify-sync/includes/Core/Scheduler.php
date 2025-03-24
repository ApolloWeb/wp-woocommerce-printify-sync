<?php
/**
 * Action Scheduler integration.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Class Scheduler
 */
class Scheduler {
    /**
     * Initialize scheduler
     *
     * @return void
     */
    public function init() {
        // Make sure Action Scheduler is loaded
        if (!class_exists('ActionScheduler')) {
            add_action('plugins_loaded', [$this, 'loadActionScheduler'], 20);
        }
    }
    
    /**
     * Load Action Scheduler
     *
     * @return void
     */
    public function loadActionScheduler() {
        // WooCommerce includes Action Scheduler, but we can also include it manually if needed
        if (!class_exists('ActionScheduler')) {
            require_once WPWPS_PLUGIN_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
        }
    }
    
    /**
     * Schedule an action
     *
     * @param string $hook Action hook name.
     * @param array  $args Arguments to pass to the hook.
     * @param int    $timestamp When to run the action.
     * @param string $group Action group name.
     * @return int|bool Action ID or false on failure.
     */
    public function scheduleAction($hook, $args = [], $timestamp = 0, $group = '') {
        if (!function_exists('as_schedule_single_action')) {
            return false;
        }
        
        if (empty($timestamp)) {
            $timestamp = time();
        }
        
        return as_schedule_single_action($timestamp, $hook, $args, $group);
    }
    
    /**
     * Schedule a recurring action
     *
     * @param string $hook Action hook name.
     * @param array  $args Arguments to pass to the hook.
     * @param int    $timestamp When to run the action first.
     * @param int    $interval How often to run the action, in seconds.
     * @param string $group Action group name.
     * @return int|bool Action ID or false on failure.
     */
    public function scheduleRecurringAction($hook, $args = [], $timestamp = 0, $interval = 3600, $group = '') {
        if (!function_exists('as_schedule_recurring_action')) {
            return false;
        }
        
        if (empty($timestamp)) {
            $timestamp = time();
        }
        
        return as_schedule_recurring_action($timestamp, $interval, $hook, $args, $group);
    }
    
    /**
     * Cancel all scheduled actions for a hook
     *
     * @param string $hook Action hook name.
     * @param array  $args Optional args to match.
     * @param string $group Optional group to match.
     * @return int Number of actions cancelled.
     */
    public function cancelAction($hook, $args = [], $group = '') {
        if (!function_exists('as_unschedule_all_actions')) {
            return 0;
        }
        
        return as_unschedule_all_actions($hook, $args, $group);
    }
    
    /**
     * Get next scheduled action for a hook
     *
     * @param string $hook Action hook name.
     * @param array  $args Optional args to match.
     * @param string $group Optional group to match.
     * @return int|bool Next timestamp or false if not scheduled.
     */
    public function nextScheduled($hook, $args = [], $group = '') {
        if (!function_exists('as_next_scheduled_action')) {
            return false;
        }
        
        return as_next_scheduled_action($hook, $args, $group);
    }
    
    /**
     * Schedule recurring events
     *
     * @return void
     */
    public static function scheduleEvents() {
        // Make sure Action Scheduler is loaded
        if (!class_exists('ActionScheduler')) {
            require_once WPWPS_PLUGIN_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
        }
        
        // Stock sync (default: every 6 hours)
        $stock_interval = get_option('wpwps_stock_sync_interval', 6) * HOUR_IN_SECONDS;
        if (!as_next_scheduled_action('wpwps_stock_sync')) {
            as_schedule_recurring_action(time(), $stock_interval, 'wpwps_stock_sync', [], 'wpwps');
        }
        
        // Email queue (default: every 5 minutes)
        $email_interval = get_option('wpwps_email_queue_interval', 5) * MINUTE_IN_SECONDS;
        if (!as_next_scheduled_action('wpwps_process_email_queue')) {
            as_schedule_recurring_action(time(), $email_interval, 'wpwps_process_email_queue', [], 'wpwps');
        }
    }
    
    /**
     * Clear scheduled events
     *
     * @return void
     */
    public static function clearEvents() {
        // Make sure Action Scheduler is loaded
        if (!class_exists('ActionScheduler')) {
            return;
        }
        
        // Clear stock sync
        as_unschedule_all_actions('wpwps_stock_sync', [], 'wpwps');
        
        // Clear email queue
        as_unschedule_all_actions('wpwps_process_email_queue', [], 'wpwps');
    }
}
