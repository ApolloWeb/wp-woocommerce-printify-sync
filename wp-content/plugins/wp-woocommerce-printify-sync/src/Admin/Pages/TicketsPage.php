<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\Container;

class TicketsPage {
    private Container $container;
    
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    public function render(): void {
        $template = $this->container->get('template');
        $tickets = $this->getTickets();
        $template->render('admin/tickets', ['tickets' => $tickets]);
    }
    
    private function getTickets(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'wpwps_tickets';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");
    }
}
