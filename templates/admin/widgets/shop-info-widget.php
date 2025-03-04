<?php
/**
 * Shop Info Widget Template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>
<div class="data-card">
    <div class="card-header">
        <h3>Shop Information</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($shop_info)): ?>
            <div class="empty-state">
                <p>No shop information available.</p>
            </div>
        <?php else: ?>
            <div class="shop-details">
                <div class="detail-row">
                    <span class="detail-label">Default Shop:</span>
                    <span class="detail-value"><?php echo esc_html($shop_info['default_shop']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">API Endpoint:</span>
                    <span class="detail-value"><?php echo esc_html($shop_info['api_endpoint']); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>