<?php
/**
 * Product Sync Summary Widget Template
 * 
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>
<div class="data-card">
    <div class="card-header">
        <h3>Product Sync Summary</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($sync_summary)): ?>
            <div class="empty-state">
                <p>No sync data available.</p>
            </div>
        <?php else: ?>
            <div class="sync-stats">
                <div class="stat-circle success">
                    <div class="stat-number"><?php echo esc_html($sync_summary['synced']); ?></div>
                    <div class="stat-label">Synced</div>
                </div>
                <div class="stat-circle warning">
                    <div class="stat-number"><?php echo esc_html($sync_summary['pending']); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-circle danger">
                    <div class="stat-number"><?php echo esc_html($sync_summary['failed']); ?></div>
                    <div class="stat-label">Failed</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>