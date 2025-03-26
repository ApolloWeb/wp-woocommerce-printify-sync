<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ActionScheduler {
    /**
     * Initialize the action scheduler
     */
    public function __construct() {
        add_action('init', [$this, 'registerSchedules']);
    }

    /**
     * Register all scheduled actions
     */
    public function registerSchedules(): void {
        if (!class_exists('ActionScheduler_Store')) {
            return;
        }

        $this->cleanupOldSchedules();
        $this->scheduleRecurringTasks();
    }

    /**
     * Clean up old schedules to prevent duplicates
     */
    private function cleanupOldSchedules(): void {
        as_unschedule_all_actions('wpwps_sync_products');
        as_unschedule_all_actions('wpwps_sync_orders');
        as_unschedule_all_actions('wpwps_cleanup_logs');
    }

    /**
     * Schedule recurring tasks
     */
    private function scheduleRecurringTasks(): void {
        if (!as_next_scheduled_action('wpwps_sync_products')) {
            as_schedule_recurring_action(time(), HOUR_IN_SECONDS, 'wpwps_sync_products');
        }

        if (!as_next_scheduled_action('wpwps_sync_orders')) {
            as_schedule_recurring_action(time(), 15 * MINUTE_IN_SECONDS, 'wpwps_sync_orders');
        }

        if (!as_next_scheduled_action('wpwps_cleanup_logs')) {
            as_schedule_recurring_action(time(), DAY_IN_SECONDS, 'wpwps_cleanup_logs');
        }
    }
}