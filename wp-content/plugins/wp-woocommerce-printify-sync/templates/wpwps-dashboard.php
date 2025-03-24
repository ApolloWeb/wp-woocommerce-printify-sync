<?php
/**
 * Dashboard template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Header
$this->template_service->partial('dashboard/header', [
    'title' => __('Dashboard', 'wp-woocommerce-printify-sync'),
    'subtitle' => __('Overview of your Printify sync status', 'wp-woocommerce-printify-sync')
]);

// Product Stats
echo $this->template_service->partial('dashboard/stats-card', [
    'title' => __('Products', 'wp-woocommerce-printify-sync'),
    'stats' => [
        [
            'value' => $product_stats['total'],
            'label' => __('Total', 'wp-woocommerce-printify-sync')
        ],
        [
            'value' => $product_stats['synced'],
            'label' => __('Synced', 'wp-woocommerce-printify-sync')
        ],
        [
            'value' => $product_stats['out_of_sync'],
            'label' => __('Out of Sync', 'wp-woocommerce-printify-sync')
        ]
    ],
    'actions' => [
        [
            'id' => 'wpwps-sync-products',
            'text' => __('Sync Products', 'wp-woocommerce-printify-sync'),
            'icon' => 'fas fa-sync',
            'class' => 'button button-primary'
        ]
    ]
]);

// Queue Status
echo $this->template_service->partial('dashboard/queue-status', [
    'import_queue' => $import_queue_stats,
    'email_queue' => $email_queue_stats
]);

// Recent Activity
echo $this->template_service->partial('dashboard/recent-activity', [
    'logs' => $recent_logs,
    'tickets' => $recent_tickets
]);
?>
