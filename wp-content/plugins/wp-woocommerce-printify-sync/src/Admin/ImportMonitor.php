<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ImportMonitor
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            'Printify Sync',
            'Printify Sync',
            'manage_woocommerce',
            'printify-sync',
            [$this, 'renderPage']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_printify-sync') {
            return;
        }

        wp_enqueue_style(
            'wpwps-monitor',
            plugins_url('assets/css/monitor.css', WPWPS_PLUGIN_FILE),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'wpwps-monitor',
            plugins_url('assets/js/monitor.js', WPWPS_PLUGIN_FILE),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('wpwps-monitor', 'wpwps', [
            'nonce' => wp_create_nonce('wpwps-monitor'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }

    public function renderPage(): void
    {
        ?>
        <div class="wrap wpwps-monitor">
            <div class="monitor-header">
                <h1>Printify Sync Monitor</h1>
                <div class="sync-status">
                    <span class="status-indicator"></span>
                    <span class="status-text">Monitoring imports...</span>
                </div>
            </div>

            <div class="monitor-grid">
                <div class="monitor-card stats-card">
                    <h2>Import Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-label">Total Products</span>
                            <span class="stat-value" id="total-products">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Successfully Imported</span>
                            <span class="stat-value" id="success-count">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Failed Imports</span>
                            <span class="stat-value" id="failed-count">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">In Progress</span>
                            <span class="stat-value" id="in-progress">0</span>
                        </div>
                    </div>
                </div>

                <div class="monitor-card recent-imports">
                    <h2>Recent Imports</h2>
                    <div class="imports-list" id="recent-imports"></div>
                </div>

                <div class="monitor-card error-log">
                    <h2>Error Log</h2>
                    <div class="error-list" id="error-log"></div>
                </div>
            </div>
        </div>
        <?php
    }
}