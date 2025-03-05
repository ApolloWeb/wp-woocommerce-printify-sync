<?php
/**
 * Admin Dashboard Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
$products_count = count_posts('product');
$connected_shop = \ApolloWeb\WPWooCommercePrintifySync\Admin\SettingsManager::get_instance()->get_setting('printify_shop_id');
$total_orders_synced = get_option('wpwprintifysync_total_orders_synced', 0);
$last_sync = get_option('wpwprintifysync_last_sync', '');
?>

<div class="wrap wpwprintifysync-admin">
    <h1><?php _e('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <?php if (empty($connected_shop)): ?>
    <div class="notice notice-warning inline">
        <p><?php printf(
            __('You need to configure your Printify API credentials to start syncing. <a href="%s">Configure Settings</a>', 'wp-woocommerce-printify-sync'),
            admin_url('admin.php?page=printify-sync-settings')
        ); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="metabox-holder">
        <div class="postbox-container" style="width: 100%;">
            <div class="postbox">
                <h2 class="hndle"><?php _e('Overview', 'wp-woocommerce-printify-sync'); ?></h2>
                
                <div class="inside">
                    <div class="wpwprintifysync-stats-grid">
                        <div class="wpwprintifysync-stat-box">
                            <div class="stat-value"><?php echo $products_count->publish ?? 0; ?></div>
                            <div class="stat-label"><?php _e('Published Products', 'wp-woocommerce-printify-sync'); ?></div>
                        </div>
                        
                        <div class="wpwprintifysync-stat-box">
                            <div class="stat-value"><?php echo $total_orders_synced; ?></div>
                            <div class="stat-label"><?php _e('Orders Synced', 'wp-woocommerce-printify-sync'); ?></div>
                        </div>
                        
                        <div class="wpwprintifysync-stat-box">
                            <div class="stat-value"><?php echo $last_sync ? human_time_diff(strtotime($last_sync)) : __('Never', 'wp-woocommerce-printify-sync'); ?></div>
                            <div class="stat-label"><?php _e('Last Sync', 'wp-woocommerce-printify-sync'); ?></div>
                        </div>
                        
                        <div class="wpwprintifysync-stat-box">
                            <div class="stat-value">
                                <?php if ($connected_shop): ?>
                                    <span class="dashicons dashicons-yes" style="color: green;"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no" style="color: red;"></span>
                                <?php endif; ?>
                            </div>
                            <div class="stat-label"><?php _e('API Connected', 'wp-woocommerce-printify-sync'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('Quick Actions', 'wp-woocommerce-printify-sync'); ?></h2>
                
                <div class="inside">
                    <div class="wpwprintifysync-actions">
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-products&action=sync_all'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Sync All Products', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-orders'); ?>" class="button">
                            <span class="dashicons dashicons-cart"></span>
                            <?php _e('Manage Orders', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-settings'); ?>" class="button">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Settings', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=printify-sync-logs'); ?>" class="button">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php _e('View Logs', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('Recent Activity', 'wp-woocommerce-printify-sync'); ?></h2>
                
                <div class="inside">
                    <?php 
                    $recent_logs = \ApolloWeb\WPWooCommercePrintifySync\Logging\LogViewer::get_instance()->get_recent_logs(5);
                    
                    if (!empty($recent_logs)): 
                    ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Time', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php _e('Level', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php _e('Message', 'wp-woocommerce-printify-sync'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo human_time_diff(strtotime($log->created_at)); ?> ago</td>
                                <td>
                                    <span class="log-level log-level-<?php echo esc_attr($log->level); ?>">
                                        <?php echo esc_html(ucfirst($log->level)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log->message); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p><?php _e('No recent activity found.', 'wp-woocommerce-printify-sync'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>