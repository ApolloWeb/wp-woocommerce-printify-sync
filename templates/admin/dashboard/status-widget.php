<?php
/**
 * Printify Status Dashboard Widget Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

use ApolloWeb\WPWooCommercePrintifySync\API\Printify\PrintifyApiClient;

// Get API status
$api_status = get_transient('wpwprintifysync_api_status');
if ($api_status === false) {
    $response = PrintifyApiClient::getInstance()->request('shops.json');
    $api_status = $response['success'] ? 'connected' : 'error';
    set_transient('wpwprintifysync_api_status', $api_status, HOUR_IN_SECONDS);
}

// Get webhook status
$webhook_id = get_option('wpwprintifysync_webhook_id');
$webhook_status = get_transient('wpwprintifysync_webhook_status');
if ($webhook_status === false) {
    if (empty($webhook_id)) {
        $webhook_status = 'not_configured';
    } else {
        $response = PrintifyApiClient::getInstance()->request('webhooks.json');
        if ($response['success']) {
            $webhooks = $response['body'];
            $found = false;
            
            foreach ($webhooks as $webhook) {
                if ($webhook['id'] == $webhook_id) {
                    $found = true;
                    $webhook_status = $webhook['enabled'] ? 'active' : 'inactive';
                    break;
                }
            }
            
            if (!$found) {
                $webhook_status = 'missing';
            }
        } else {
            $webhook_status = 'error';
        }
    }
    
    set_transient('wpwprintifysync_webhook_status', $webhook_status, HOUR_IN_SECONDS);
}

// Get shop info
$shop_id = get_option('wpwprintifysync_shop_id', 0);
$shop_info = get_transient('wpwprintifysync_shop_info');
if ($shop_info === false && $shop_id > 0) {
    $response = PrintifyApiClient::getInstance()->request("shops/{$shop_id}.json");
    if ($response['success']) {
        $shop_info = $response['body'];
        set_transient('wpwprintifysync_shop_info', $shop_info, 12 * HOUR_IN_SECONDS);
    }
}

// Get sync stats
$last_sync = get_option('wpwprintifysync_last_sync', '');
$synced_products = get_option('wpwprintifysync_synced_products', 0);
$synced_orders = get_option('wpwprintifysync_synced_orders', 0);
$pending_orders = get_option('wpwprintifysync_pending_orders', 0);

// Current timestamp for display
$current_timestamp = '2025-03-05 19:24:55';
?>

<div class="wpwprintifysync-status-widget">
    <div class="wpwprintifysync-widget-header">
        <h3><?php _e('Printify Integration Status', 'wp-woocommerce-printify-sync'); ?></h3>
        <div class="wpwprintifysync-refresh-status">
            <a href="#" class="wpwprintifysync-refresh-button" data-nonce="<?php echo wp_create_nonce('wpwprintifysync-refresh-status'); ?>">
                <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'wp-woocommerce-printify-sync'); ?>
            </a>
        </div>
    </div>
    
    <div class="wpwprintifysync-status-grid">
        <div class="wpwprintifysync-status-item">
            <span class="wpwprintifysync-status-label"><?php _e('API Connection:', 'wp-woocommerce-printify-sync'); ?></span>
            <span class="wpwprintifysync-status-badge status-<?php echo esc_attr($api_status); ?>">
                <?php 
                switch ($api_status) {
                    case 'connected':
                        echo '<span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Connected', 'wp-woocommerce-printify-sync');
                        break;
                    case 'error':
                        echo '<span class="dashicons dashicons-warning"></span> ' . esc_html__('Error', 'wp-woocommerce-printify-sync');
                        break;
                    default:
                        echo '<span class="dashicons dashicons-no-alt"></span> ' . esc_html__('Disconnected', 'wp-woocommerce-printify-sync');
                }
                ?>
            </span>
        </div>
        
        <div class="wpwprintifysync-status-item">
            <span class="wpwprintifysync-status-label"><?php _e('Webhook Status:', 'wp-woocommerce-printify-sync'); ?></span>
            <span class="wpwprintifysync-status-badge status-<?php echo esc_attr($webhook_status); ?>">
                <?php 
                switch ($webhook_status) {
                    case 'active':
                        echo '<span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Active', 'wp-woocommerce-printify-sync');
                        break;
                    case 'inactive':
                        echo '<span class="dashicons dashicons-marker"></span> ' . esc_html__('Inactive', 'wp-woocommerce-printify-sync');
                        break;
                    case 'missing':
                        echo '<span class="dashicons dashicons-warning"></span> ' . esc_html__('Missing', 'wp-woocommerce-printify-sync');
                        break;
                    case 'not_configured':
                        echo '<span class="dashicons dashicons-no-alt"></span> ' . esc_html__('Not Configured', 'wp-woocommerce-printify-sync');
                        break;
                    default:
                        echo '<span class="dashicons dashicons-warning"></span> ' . esc_html__('Error', 'wp-woocommerce-printify-sync');
                }
                ?>
            </span>
        </div>
        
        <div class="wpwprintifysync-status-item">
            <span class="wpwprintifysync-status-label"><?php _e('Current Shop:', 'wp-woocommerce-printify-sync'); ?></span>
            <span class="wpwprintifysync-status-value">
                <?php 
                if (!empty($shop_info)) {
                    echo esc_html($shop_info['title']);
                    echo ' <small>(ID: ' . esc_html($shop_id) . ')</small>';
                } else {
                    echo esc_html__('Not selected', 'wp-woocommerce-printify-sync');
                }
                ?>
            </span>
        </div>
        
        <div class="wpwprintifysync-status-item">
            <span class="wpwprintifysync-status-label"><?php _e('Last Sync:', 'wp-woocommerce-printify-sync'); ?></span>
            <span class="wpwprintifysync-status-value">
                <?php 
                if (!empty($last_sync)) {
                    echo esc_html(human_time_diff(strtotime($last_sync), current_time('timestamp'))) . ' ' . esc_html__('ago', 'wp-woocommerce-printify-sync');
                } else {
                    echo esc_html__('Never', 'wp-woocommerce-printify-sync');
                }
                ?>
            </span>
        </div>
    </div>
    
    <div class="wpwprintifysync-stats-summary">
        <div class="wpwprintifysync-stat-box">
            <span class="wpwprintifysync-stat-number"><?php echo number_format_i18n($synced_products); ?></span>
            <span class="wpwprintifysync-stat-text"><?php _e('Synced Products', 'wp-woocommerce-printify-sync'); ?></span>
        </div>
        
        <div class="wpwprintifysync-stat-box">
            <span class="wpwprintifysync-stat-number"><?php echo number_format_i18n($synced_orders); ?></span>
            <span class="wpwprintifysync-stat-text"><?php _e('Synced Orders', 'wp-woocommerce-printify-sync'); ?></span>
        </div>
        
        <div class="wpwprintifysync-stat-box <?php echo $pending_orders > 0 ? 'has-pending' : ''; ?>">
            <span class="wpwprintifysync-stat-number"><?php echo number_format_i18n($pending_orders); ?></span>
            <span class="wpwprintifysync-stat-text"><?php _e('Pending Orders', 'wp-woocommerce-printify-sync'); ?></span>
        </div>
    </div>
    
    <div class="wpwprintifysync-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-settings')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-admin-settings"></span> <?php _e('Settings', 'wp-woocommerce-printify-sync'); ?>
        </a>
        
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-products')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-products"></span> <?php _e('Products', 'wp-woocommerce-printify-sync'); ?>
        </a>
        
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-orders')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-cart"></span> <?php _e('Orders', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </div>
</div>

<style>
    .wpwprintifysync-status-widget {
        position: relative;
    }
    .wpwprintifysync-widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .wpwprintifysync-widget-header h3 {
        margin: 0;
        padding: 0;
    }
    .wpwprintifysync-status-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 15px;
    }
    .wpwprintifysync-status-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .wpwprintifysync-status-badge {
        font-weight: 500;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
    }
    .wpwprintifysync-status-badge.status-connected,
    .wpwprintifysync-status-badge.status-active {
        background-color: #e6f9e6;
        color: #1e7e1e;
    }
    .wpwprintifysync-status-badge.status-error,
    .wpwprintifysync-status-badge.status-missing {
        background-color: #fbe9e7;
        color: #c53929;
    }
    .wpwprintifysync-status-badge.status-inactive,
    .wpwprintifysync-status-badge.status-not_configured {
        background-color: #fff8e1;
        color: #ff8f00;
    }
    .wpwprintifysync-stats-summary {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin: 15px 0;
    }
    .wpwprintifysync-stat-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 4px;
    }
    .wpwprintifysync-stat-number {
        font-size: 28px;
        font-weight: bold;
        line-height: 1.2;
    }
    .wpwprintifysync-stat-text {
        font-size: 12px;
        color: #555;
    }
    .wpwprintifysync-stat-box.has-pending {
        background-color: #fff8e1;
    }
    .wpwprintifysync-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 15px;
    }
    .wpwprintifysync-actions .button {
        display: flex;
        align-items: center;
    }
    .wpwprintifysync-actions .button .dashicons {
        margin-right: 5px;
    }
    .wpwprintifysync-refresh-button .dashicons {
        vertical-align: middle;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        $('.wpwprintifysync-refresh-button').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $icon = $button.find('.dashicons');
            
            // Add spinning class
            $icon.addClass('dashicons-update-spin');
            
            // AJAX request to refresh status
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpwprintifysync_refresh_status',
                    nonce: $button.data('nonce')
                },
                success: function(response) {
                    if (response.success) {
                        // Reload widget content
                        window.location.reload();
                    } else {
                        alert(response.data.message || 'Error refreshing status.');
                        $icon.removeClass('dashicons-update-spin');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $icon.removeClass('dashicons-update-spin');
                }
            });
        });
    });
</script>