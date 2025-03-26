<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Services\ApiService;
use ApolloWeb\WPWooCommercePrintifySync\Services\LoggerService;

class AdminBar {
    private $api_service;
    private $logger_service;

    public function __construct() {
        $this->api_service = new ApiService();
        $this->logger_service = new LoggerService();

        add_action('admin_bar_menu', [$this, 'addApiHealthIndicator'], 100);
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('wp_ajax_wpwps_get_api_health', [$this, 'getApiHealth']);
    }

    public function addApiHealthIndicator(\WP_Admin_Bar $admin_bar): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $stats = $this->logger_service->getStats();
        $rate_info = $this->api_service->getRateLimitInfo();

        $status_class = 'wpwps-status-unknown';
        if ($rate_info['remaining'] !== null) {
            $status_class = $rate_info['remaining'] > 100 ? 'wpwps-status-good' : 
                          ($rate_info['remaining'] > 20 ? 'wpwps-status-warning' : 'wpwps-status-critical');
        }

        $admin_bar->add_node([
            'id' => 'wpwps-api-health',
            'title' => sprintf(
                '<span class="ab-icon %s"></span><span class="ab-label">%s</span>',
                $status_class,
                __('Printify API', 'wp-woocommerce-printify-sync')
            ),
            'href' => admin_url('admin.php?page=wpwps-settings'),
            'meta' => [
                'title' => __('View API Settings', 'wp-woocommerce-printify-sync')
            ]
        ]);

        $admin_bar->add_node([
            'id' => 'wpwps-api-stats',
            'parent' => 'wpwps-api-health',
            'title' => sprintf(
                __('API Calls: %d (24h) | Errors: %d', 'wp-woocommerce-printify-sync'),
                $stats['last_24h'],
                $stats['errors']
            )
        ]);

        if ($rate_info['remaining'] !== null) {
            $admin_bar->add_node([
                'id' => 'wpwps-rate-limit',
                'parent' => 'wpwps-api-health',
                'title' => sprintf(
                    __('Rate Limit: %d remaining', 'wp-woocommerce-printify-sync'),
                    $rate_info['remaining']
                )
            ]);
        }
    }

    public function enqueueStyles(): void {
        wp_add_inline_style('admin-bar', '
            #wpadminbar .wpwps-status-unknown .ab-icon:before { content: "\f463"; color: #777; }
            #wpadminbar .wpwps-status-good .ab-icon:before { content: "\f147"; color: #46b450; }
            #wpadminbar .wpwps-status-warning .ab-icon:before { content: "\f534"; color: #ffb900; }
            #wpadminbar .wpwps-status-critical .ab-icon:before { content: "\f534"; color: #dc3232; }
        ');
    }

    public function getApiHealth(): void {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'wp-woocommerce-printify-sync'));
        }

        $stats = $this->logger_service->getStats();
        $rate_info = $this->api_service->getRateLimitInfo();

        wp_send_json_success([
            'stats' => $stats,
            'rate_limit' => $rate_info
        ]);
    }
}