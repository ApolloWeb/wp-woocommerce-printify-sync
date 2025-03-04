<?php
/**
 * Stock Levels Widget Template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>
<div class="data-card">
    <div class="card-header">
        <h3>Stock Levels</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($stock_levels)): ?>
            <div class="empty-state">
                <p>No stock data available.</p>
            </div>
        <?php else: ?>
            <div class="stock-chart">
                <div class="stock-bar">
                    <div class="stock-segment in-stock" style="width: <?php echo esc_attr(($stock_levels['in_stock'] / ($stock_levels['in_stock'] + $stock_levels['low_stock'] + $stock_levels['out_of_stock'])) * 100); ?>%">
                        <span class="stock-label">In Stock</span>
                        <span class="stock-count"><?php echo esc_html($stock_levels['in_stock']); ?></span>
                    </div>
                    <div class="stock-segment low-stock" style="width: <?php echo esc_attr(($stock_levels['low_stock'] / ($stock_levels['in_stock'] + $stock_levels['low_stock'] + $stock_levels['out_of_stock'])) * 100); ?>%">
                        <span class="stock-label">Low Stock</span>
                        <span class="stock-count"><?php echo esc_html($stock_levels['low_stock']); ?></span>
                    </div>
                    <div class="stock-segment out-of-stock" style="width: <?php echo esc_attr(($stock_levels['out_of_stock'] / ($stock_levels['in_stock'] + $stock_levels['low_stock'] + $stock_levels['out_of_stock'])) * 100); ?>%">
                        <span class="stock-label">Out of Stock</span>
                        <span class="stock-count"><?php echo esc_html($stock_levels['out_of_stock']); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>