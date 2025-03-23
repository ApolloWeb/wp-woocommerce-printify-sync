<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class CronManager {
    public function init(): void {
        add_filter('cron_schedules', [$this, 'addSchedules']);
        $this->registerTasks();
    }

    public function addSchedules(array $schedules): array {
        $schedules['every_five_minutes'] = [
            'interval' => 300,
            'display' => __('Every 5 minutes', 'wp-woocommerce-printify-sync')
        ];
        return $schedules;
    }

    private function registerTasks(): void {
        if (!wp_next_scheduled('wpps_sync_products')) {
            wp_schedule_event(time(), 'hourly', 'wpps_sync_products');
        }
    }
}
