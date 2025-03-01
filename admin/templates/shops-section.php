<?php
/**
 * Shops section template
 * 
 * @package WP WooCommerce Printify Sync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Current date/time for display
$current_date = date('Y-m-d H:i:s');
$current_user = wp_get_current_user()->user_login;
?>

<div class="wrap wpwps-wrap">
    <!-- Header -->
    <div class="wpwps-header">
        <div class="wpwps-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M22 8.52V3.98C22 2.57 21.36 2 19.77 2H15.73C14.14 2 13.5 2.57 13.5 3.98V8.51C13.5 9.93 14.14 10.49 15.73 10.49H19.77C21.36 10.5 22 9.93 22 8.52Z"></path>
                <path d="M22 19.77V15.73C22 14.14 21.36 13.5 19.77 13.5H15.73C14.14 13.5 13.5 14.14 13.5 15.73V19.77C13.5 21.36 14.14 22 15.73 22H19.77C21.36 22 22 21.36 22 19.77Z"></path>
                <path d="M10.5 8.52V3.98C10.5 2.57 9.86 2 8.27 2H4.23C2.64 2 2 2.57 2 3.98V8.51C2 9.93 2.64 10.49 4.23 10.49H8.27C9.86 10.5 10.5 9.93 10.5 8.52Z"></path>
                <path d="M10.5 19.77V15.73C10.5 14.14 9.86 13.5 8.27 13.5H4.23C2.64 13.5 2 14.14 2 15.73V19.77C2 21.36 2.64 22 4.23 22H8.27C9.86 22 10.5 21.36 10.5 19.77Z"></path>
            </svg>
        </div>
        <div class="wpwps-header-title">
            <h1><?php esc_html_e('Printify Shops', 'wp-woocommerce-printify-sync'); ?></h1>
            <div class="wpwps-version">
                <?php esc_html_e('Version', 'wp-woocommerce-printify-sync'); ?> <?php echo esc_html(WPWPS_VERSION); ?>
                • <?php echo esc_html($current_date); ?>
                • <?php echo esc_html($current_user); ?>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="wpwps-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync')); ?>" class="wpwps-nav-item">
            <?php esc_html_e('Settings', 'wp-woocommerce-printify-sync'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-shops')); ?>" class="wpwps-nav-item active">
            <?php esc_html_e('Shops', 'wp-woocommerce-printify-sync'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-products')); ?>" class="wpwps-nav-item">
            <?php esc_html_e('Products', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </div>
    
    <!-- Description -->
    <div class="wpwps-message wpwps-message-info">
        <div class="wpwps-message-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="