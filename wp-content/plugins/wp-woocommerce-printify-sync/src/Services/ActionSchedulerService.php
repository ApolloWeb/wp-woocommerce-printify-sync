<?php
/**
 * Action Scheduler Service
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Class ActionSchedulerService
 *
 * Handles scheduling tasks with Action Scheduler
 */
class ActionSchedulerService
{
    /**
     * Logger service
     *
     * @var LoggerService
     */
    private LoggerService $logger;

    /**
     * Constructor
     *
     * @param LoggerService $logger Logger service.
     */
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set up recurring tasks
     *
     * @return void
     */
    public function setupRecurringTasks(): void
    {
        // Ensure Action Scheduler is loaded
        if (!function_exists('as_schedule_recurring_action')) {
            $this->logger->error('Action Scheduler not available');
            return;
        }

        // Product sync task
        $this->setupProductSyncTask();

        // Email queue processing task
        $this->setupEmailQueueTask();

        // Log rotation task
        $this->setupLogRotationTask();
    }

    /**
     * Set up the product sync recurring task
     *
     * @return void
     */
    private function setupProductSyncTask(): void
    {
        $hook = 'wpwps_sync_products';
        $interval_hours = intval(get_option('wpwps_sync_interval', 6));

        // Convert hours to seconds
        $interval = $interval_hours * HOUR_IN_SECONDS;

        // Schedule the task if it's not already scheduled
        if (!as_next_scheduled_action($hook)) {
            as_schedule_recurring_action(time() + 300, $interval, $hook);
            $this->logger->info('Scheduled product sync task', ['interval' => $interval_hours . ' hours']);
        }
    }

    /**
     * Set up the email queue processing task
     *
     * @return void
     */
    private function setupEmailQueueTask(): void
    {
        $hook = 'wpwps_process_email_queue';
        $interval_minutes = intval(get_option('wpwps_email_queue_interval', 5));

        // Convert minutes to seconds
        $interval = $interval_minutes * MINUTE_IN_SECONDS;

        // Schedule the task if it's not already scheduled
        if (!as_next_scheduled_action($hook)) {
            as_schedule_recurring_action(time() + 120, $interval, $hook);
            $this->logger->info('Scheduled email queue task', ['interval' => $interval_minutes . ' minutes']);
        }
    }

    /**
     * Set up the log rotation task
     *
     * @return void
     */
    private function setupLogRotationTask(): void
    {
        $hook = 'wpwps_rotate_logs';

        // Run once a day
        $interval = DAY_IN_SECONDS;

        // Schedule the task if it's not already scheduled
        if (!as_next_scheduled_action($hook)) {
            as_schedule_recurring_action(time() + 3600, $interval, $hook);
            $this->logger->info('Scheduled log rotation task', ['interval' => '1 day']);
        }
    }

    /**
     * Schedule a single task
     *
     * @param string  $hook        Action hook name
     * @param array   $args        Arguments to pass to the hook's callback
     * @param int     $timestamp   When to run the action
     * @param boolean $unique      Whether to ensure this action is unique
     * @return int|bool The action ID or false if scheduling failed
     */
    public function scheduleTask(string $hook, array $args = [], int $timestamp = 0, bool $unique = false)
    {
        if (!function_exists('as_schedule_single_action')) {
            $this->logger->error('Action Scheduler not available');
            return false;
        }

        // Use current time if timestamp is 0
        if ($timestamp <= 0) {
            $timestamp = time();
        }

        // Check if we need to ensure this is a unique action
        if ($unique && function_exists('as_next_scheduled_action')) {
            $existing = as_next_scheduled_action($hook, $args);
            if ($existing) {
                // Action already scheduled
                return $existing;
            }
        }

        // Schedule the task
        $action_id = as_schedule_single_action($timestamp, $hook, $args);
        
        if ($action_id) {
            $this->logger->debug('Scheduled task', [
                'hook' => $hook,
                'args' => $args,
                'timestamp' => $timestamp,
                'action_id' => $action_id
            ]);
        } else {
            $this->logger->error('Failed to schedule task', [
                'hook' => $hook,
                'args' => $args,
                'timestamp' => $timestamp
            ]);
        }

        return $action_id;
    }

    /**
     * Clear all scheduled tasks
     *
     * @return void
     */
    public function clearScheduledTasks(): void
    {
        if (!function_exists('as_unschedule_all_actions')) {
            $this->logger->error('Action Scheduler not available');
            return;
        }

        // Get all our hooks
        $hooks = [
            'wpwps_sync_products',
            'wpwps_process_email_queue',
            'wpwps_rotate_logs',
            'wpwps_import_product',
            'wpwps_sync_product',
            'wpwps_process_order',
        ];

        // Unschedule all actions for each hook
        foreach ($hooks as $hook) {
            as_unschedule_all_actions($hook);
        }

        $this->logger->info('Cleared all scheduled tasks');
    }
}
