<?php
defined('ABSPATH') || exit;
?>
<div class="wrap wpwps-wrapper">
    <h1 class="wp-heading-inline">Printify Sync Dashboard</h1>
    
    <div class="wpwps-timestamp">
        <i class="material-icons">access_time</i>
        Last Updated: <?php echo esc_html($this->currentTime); ?>
    </div>
    
    <div class="wpwps-user-badge">
        <i class="material-icons">person</i>
        <?php echo esc_html($this->currentUser); ?>
    </div>

    <div class="wpwps-alerts-container"></div>

    <div class="row mt-4">
        <!-- Stats Cards -->
        <div class="col-md-3">
            <div class="wpwps-stat-card">
                <div class="wpwps-stat-icon">
                    <i class="material-icons">inventory_2</i>
                </div>
                <div class="wpwps-stat-content">
                    <h3><?php echo esc_html($this->getProductCount()); ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="wpwps-stat-card">
                <div class="wpwps-stat-icon">
                    <i class="material-icons">sync</i>
                </div>
                <div class="wpwps-stat-content">
                    <h3><?php echo esc_html($this->getSyncCount()); ?></h3>
                    <p>Syncs Today</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="wpwps-stat-card">
                <div class="wpwps-stat-icon">
                    <i class="material-icons">error_outline</i>
                </div>
                <div class="wpwps-stat-content">
                    <h3><?php echo esc_html($this->getErrorCount()); ?></h3>
                    <p>Errors Today</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="wpwps-stat-card">
                <div class="wpwps-stat-icon">
                    <i class="material-icons">schedule</i>
                </div>
                <div class="wpwps-stat-content">
                    <h3><?php echo esc_html($this->getNextSyncTime()); ?></h3>
                    <p>Next Sync</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="wpwps-card">
                <div class="wpwps-card-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="wpwps-card-body">
                    <button class="wpwps-btn wpwps-btn-primary" id="sync-all">
                        <i class="material-icons">sync</i> Sync All Products
                    </button>
                    
                    <button class="wpwps-btn wpwps-btn-secondary" id="check-api">
                        <i class="material-icons">network_check</i> Check API Connection
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-md-6">
            <div class="wpwps-card">
                <div class="wpwps-card-header">
                    <h2>Recent Activity</h2>
                </div>
                <div class="wpwps-card-body">
                    <?php $this->renderRecentActivity(); ?>
                </div>
            </div>
        </div>
    </div>
</div>