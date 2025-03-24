<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\Container;

class LogsPage {
    private Container $container;
    
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    public function render(): void {
        $template = $this->container->get('template');
        $logs = $this->getLogs();
        $template->render('admin/logs', ['logs' => $logs]);
    }
    
    private function getLogs(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'wpwps_logs';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 1000");
    }
}
