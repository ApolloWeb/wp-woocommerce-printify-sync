<?php
/**
 * Shop Info Widget Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Initialize shop info with default values if not provided
$shop_info = isset($shop_info) ? $shop_info : [
    'name' => 'My Printify Shop',
    'id' => '123456',
    'status' => 'active',
    'products_count' => 248,
    'last_sync' => '2025-03-04 06:30:15',
    'connection_status' => 'connected'
];
?>

<div class="shop-info-container">
    <div class="shop-status-header">
        <h4><?php echo esc_html($shop_info['name']); ?></h4>
        <span class="status-badge <?php echo $shop_info['connection_status'] === 'connected' ? 'success' : 'error'; ?>">
            <?php echo ucfirst(esc_html($shop_info['connection_status'])); ?>
        </span>
    </div>
    
    <table class="printify-table">
        <tr>
            <td><strong>Shop ID:</strong></td>
            <td><?php echo esc_html($shop_info['id']); ?></td>
        </tr>
        <tr>
            <td><strong>Products:</strong></td>
            <td><?php echo esc_html($shop_info['products_count']); ?></td>
        </tr>
        <tr>
            <td><strong>Last Sync:</strong></td>
            <td><?php echo esc_html(human_time_diff(strtotime($shop_info['last_sync']), time()) . ' ago'); ?></td>
        </tr>
        <tr>
            <td><strong>Status:</strong></td>
            <td>
                <span class="status-badge <?php echo $shop_info['status'] === 'active' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst(esc_html($shop_info['status'])); ?>
                </span>
            </td>
        </tr>
    </table>
    
    <div class="shop-actions" style="margin-top: 15px;">
        <a href="<?php echo admin_url('admin.php?page=printify-shops'); ?>" class="printify-btn btn-outline btn-sm">
            <i class="fas fa-cog"></i> Manage Shop
        </a>
    </div>
</div>

<style>
.shop-status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.shop-status-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.shop-actions {
    display: flex;
    gap: 10px;
}
</style>