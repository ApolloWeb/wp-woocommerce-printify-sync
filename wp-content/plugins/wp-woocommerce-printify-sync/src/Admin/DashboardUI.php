<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class DashboardUI
{
    private string $currentTime = '2025-03-15 19:56:28';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenuPage(): void
    {
        add_menu_page(
            'Printify Sync',
            'Printify Sync',
            'manage_woocommerce',
            'printify-sync',
            [$this, 'renderDashboard'],
            'dashicons-migrate',
            58
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_printify-sync') {
            return;
        }

        // Enqueue Alpine.js for reactivity
        wp_enqueue_script(
            'alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            null,
            true
        );

        // Custom styles and scripts
        wp_enqueue_style(
            'wpwps-dashboard',
            plugins_url('assets/css/dashboard.css', WPWPS_PLUGIN_FILE),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'wpwps-dashboard',
            plugins_url('assets/js/dashboard.js', WPWPS_PLUGIN_FILE),
            ['alpinejs'],
            '1.0.0',
            true
        );

        wp_localize_script('wpwps-dashboard', 'wpwps', [
            'nonce' => wp_create_nonce('wpwps-dashboard'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'currentTime' => $this->currentTime,
            'currentUser' => $this->currentUser
        ]);
    }

    public function renderDashboard(): void
    {
        ?>
        <div class="wpwps-dashboard" x-data="dashboard">
            <!-- Header -->
            <div class="dashboard-header">
                <div class="header-content">
                    <h1>Printify Sync Dashboard</h1>
                    <div class="sync-status" :class="status.state">
                        <span class="status-indicator"></span>
                        <span class="status-text" x-text="status.message"></span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="dashboard-grid">
                <!-- Live Activity Card -->
                <div class="dashboard-card activity-card">
                    <div class="card-header">
                        <h2>Live Activity</h2>
                        <span class="badge" x-text="activeImports + ' Active'"></span>
                    </div>
                    <div class="activity-stream">
                        <template x-for="activity in activities" :key="activity.id">
                            <div class="activity-item" :class="activity.type">
                                <div class="activity-icon">
                                    <span :class="getActivityIcon(activity.type)"></span>
                                </div>
                                <div class="activity-content">
                                    <h3 x-text="activity.title"></h3>
                                    <p x-text="activity.message"></p>
                                    <span class="activity-time" x-text="formatTime(activity.time)"></span>
                                </div>
                                <div class="activity-status">
                                    <span class="status-badge" :class="activity.status"
                                          x-text="activity.status"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="dashboard-card stats-card">
                    <div class="card-header">
                        <h2>Import Statistics</h2>
                        <button @click="refreshStats" 
                                class="refresh-button" 
                                :class="{ 'rotating': isRefreshing }">
                            ↻
                        </button>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item success">
                            <span class="stat-number" x-text="stats.successful"></span>
                            <span class="stat-label">Successful</span>
                        </div>
                        <div class="stat-item pending">
                            <span class="stat-number" x-text="stats.pending"></span>
                            <span class="stat-label">Pending</span>
                        </div>
                        <div class="stat-item processing">
                            <span class="stat-number" x-text="stats.processing"></span>
                            <span class="stat-label">Processing</span>
                        </div>
                        <div class="stat-item failed">
                            <span class="stat-number" x-text="stats.failed"></span>
                            <span class="stat-label">Failed</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Products Card -->
                <div class="dashboard-card products-card">
                    <div class="card-header">
                        <h2>Recent Products</h2>
                        <div class="card-actions">
                            <select x-model="productFilter">
                                <option value="all">All Products</option>
                                <option value="success">Successful</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </div>
                    <div class="products-grid">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <div class="product-card" :class="product.status">
                                <div class="product-image">
                                    <img :src="product.image" :alt="product.title">
                                </div>
                                <div class="product-info">
                                    <h3 x-text="product.title"></h3>
                                    <p class="product-meta">
                                        <span x-text="product.sku"></span>
                                        <span x-text="formatPrice(product.price)"></span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}