<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyAPI;

class Tickets {
    private $template;
    private $printifyAPI;

    public function __construct(BladeTemplateEngine $template, PrintifyAPI $printifyAPI) {
        $this->template = $template;
        $this->printifyAPI = $printifyAPI;

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_get_tickets', [$this, 'getTickets']);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void {
        $data = [
            'tickets' => $this->getInitialTickets(),
        ];

        $this->template->render('wpwps-tickets', $data);
    }

    public function enqueueAssets(string $hook): void {
        if ($hook !== 'printify-sync_page_wpwps-tickets') {
            return;
        }

        wp_enqueue_style('wpwps-tickets');
        wp_enqueue_script('wpwps-tickets');
    }

    private function getInitialTickets(): array {
        // TODO: Implement actual ticket fetching logic
        return [];
    }

    public function getTickets(): void {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        // TODO: Implement actual ticket fetching logic
        wp_send_json_success([
            'tickets' => []
        ]);
    }
}