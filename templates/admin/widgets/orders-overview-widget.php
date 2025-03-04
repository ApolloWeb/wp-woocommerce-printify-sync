<?php
/**
 * Orders Overview Widget Template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>
<div class="data-card">
    <div class="card-header">
        <h3>Orders Overview</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
        </div>
    </div>
    <div class="card-content">
        <?php if (!isset($orders_today) && !isset($orders_week) && !isset($orders_month)): ?>
            <div class="empty-state">
                <p>No order data available.</p>
            </div>
        <?php else: ?>
            <div class="orders-stats">
                <div class="order-stat">
                    <div class="stat-label">Today</div>
                    <div class="stat-value"><?php echo esc_html($orders_today); ?></div>
                </div>
                <div class="order-stat">
                    <div class="stat-label">This Week</div>
                    <div class="stat-value"><?php echo esc_html($orders_week); ?></div>
                </div>
                <div class="order-stat">
                    <div class="stat-label">This Month</div>
                    <div class="stat-value"><?php echo esc_html($orders_month); ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>