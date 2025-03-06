<?php
/**
 * Product Sync Status Dashboard Widget Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Get product sync stats - HPOS compatible
$synced_products = get_option('wpwprintifysync_synced_products', 0);
$failed_syncs = get_option('wpwprintifysync_failed_syncs', 0);
$sync_queue = get_option('wpwprintifysync_sync_queue', 0);

// Get recent sync errors
global $wpdb;
$table_name = $wpdb->prefix . 'wpwprintifysync_logs';

$recent_errors = [];
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
    $recent_errors = $wpdb->get_results(
        "SELECT message, context, created_at 
        FROM $table_name 
        WHERE level = 'error' AND context LIKE '%product%'
        ORDER BY created_at DESC 
        LIMIT 3"
    );
}

// Check if sync is in progress
$sync_in_progress = false;
$scheduled_syncs = 0;
if (function_exists('as_get_scheduled_actions')) {
    $scheduled_syncs = as_get_scheduled_actions([
        'hook' => 'wpwprintifysync_import_products_batch',
        'status' => \ActionScheduler_Store::STATUS_PENDING
    ]);
    
    $sync_in_progress = !empty($scheduled_syncs);
}

// Current timestamp for display
$current_timestamp = '2025-03-05 19:24:55';
?>

<div class="wpwprintifysync-product-sync-widget">
    <div class="wpwprintifysync-widget-header">
        <h3><?php _e('Product Sync Status', 'wp-woocommerce-printify-sync'); ?></h3>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wpwprintifysync-products')); ?>" class="wpwprintifysync-view-all">
            <?php _e('Manage Products', 'wp-woocommerce-printify-sync'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
    </div>
    
    <div class="wpwprintifysync-sync-summary">
        <div class="wpwprintifysync-sync-stats">
            <div class="wpwprintifysync-sync-stat-item">
                <div class="wpwprintifysync-sync-stat-number"><?php echo number_format_i18n($synced_products); ?></div>
                <div class="wpwprintifysync-sync-stat-label"><?php _e('Synced Products', 'wp-woocommerce-printify-sync'); ?></div>
            </div>
            
            <div class="wpwprintifysync-sync-stat-item">
                <div class="wpwprintifysync-sync-stat-number"><?php echo number_format_i18n($failed_syncs); ?></div>
                <div class="wpwprintifysync-sync-stat-label"><?php _e('Failed Syncs', 'wp-woocommerce-printify-sync'); ?></div>
            </div>
            
            <div class="wpwprintifysync-sync-stat-item">
                <div class="wpwprintifysync-sync-stat-number"><?php echo number_format_i18n(count($scheduled_syncs)); ?></div>
                <div class="wpwprintifysync-sync-stat-label"><?php _e('Pending Syncs', 'wp-woocommerce-printify-sync'); ?></div>
            </div>
        </div>
        
        <?php if ($sync_in_progress): ?>
            <div class="wpwprintifysync-sync-progress">
                <div class="wpwprintifysync-progress-label">
                    <span class="spinner is-active"></span>
                    <?php _e('Sync in progress', 'wp-woocommerce-printify-sync'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($recent_errors)): ?>
        <div class="wpwprintifysync-sync-errors">
            <h4><?php _e('Recent Sync Errors', 'wp-woocommerce-printify-sync'); ?></h4>
            <ul>
                <?php foreach ($recent_errors as $error): ?>
                    <?php 
                    $context = json_decode($error->context, true);
                    $printify_id = isset($context['printify_id']) ? $context['printify_id'] : '';
                    $product_id = isset($context['product_id']) ? $context['product_id'] : '';
                    ?>
                    <li>
                        <?php echo esc_html($error->message); ?>
                        <?php if ($printify_id || $product_id): ?>
                            <small>
                                <?php 
                                if ($printify_id) {
                                    echo sprintf(__('Printify ID: %s', 'wp-woocommerce-printify-sync'), esc_html($printify_id));
                                }
                                if ($product_id) {
                                    echo $printify_id ? ' | ' : '';
                                    echo sprintf(__('Product ID: %s', 'wp-woocommerce-printify-sync'), esc_html($product_id));
                                }
                                ?>
                            </small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="wpwprintifysync-sync-actions">
        <a href="<?php echo esc_url(admin_url('admin.php