<?php
/**
 * Settings page template
 * 
 * @package WP WooCommerce Printify Sync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
?>

<div class="wrap wpwps-wrap">
    <!-- Header -->
    <div class="wpwps-header">
        <div class="wpwps-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M20 6H4V4H20V6ZM21 12V14H3V12H21ZM17 20H7V18H17V20Z"></path>
            </svg>
        </div>
        <div class="wpwps-header-title">
            <h1><?php esc_html_e('Printify Sync', 'wp-woocommerce-printify-sync'); ?></h1>
            <div class="wpwps-version">
                <?php esc_html_e('Version', 'wp-woocommerce-printify-sync'); ?> <?php echo esc_html(WPWPS_VERSION); ?>
                • <?php echo esc_html('2025-03-01 08:21:26'); ?>
                • <?php echo esc_html('ApolloWeb'); ?>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="wpwps-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync')); ?>" class="wpwps-nav-item active">
            <?php esc_html_e('Settings', 'wp-woocommerce-printify-sync'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-shops')); ?>" class="wpwps-nav-item">
            <?php esc_html_e('Shops', 'wp-woocommerce-printify-sync'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-products')); ?>" class="wpwps-nav-item">
            <?php esc_html_e('Products', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </div>
    
    <!-- API Connection Card -->
    <div class="wpwps-card">
        <div class="wpwps-card-header">
            <h2 class="wpwps-card-title"><?php esc_html_e('Printify API Connection', 'wp-woocommerce-printify-sync'); ?></h2>
        </div>
        <div class="wpwps-card-body">
            <form id="wpwps-settings-form" method="post">
                <div class="wpwps-form-row">
                    <label for="wpwps-api-key" class="wpwps-form-label">
                        <?php esc_html_e('Printify API Key', 'wp-woocommerce-printify-sync'); ?>
                    </label>
                    <input 
                        type="password"
                        id="wpwps-api-key"
                        name="api_key"
                        class="wpwps-form-input"
                        value="<?php echo esc_attr($api_key); ?>"
                        placeholder="<?php esc_attr_e('Enter your Printify API key', 'wp-woocommerce-printify-sync'); ?>"
                    />
                    <div class="wpwps-form-description">
                        <?php esc_html_e('Enter your Printify API key. You can find or generate your API key in your Printify dashboard under Account Settings > API.', 'wp-woocommerce-printify-sync'); ?>
                        <a href="https://printify.com/settings/api" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Get your API key', 'wp-woocommerce-printify-sync'); ?> →
                        </a>
                    </div>
                </div>
                
                <div class="wpwps-form-row wpwps-form-actions">
                    <button type="submit" class="wpwps-button wpwps-button-primary" id="wpwps-save-settings">
                        <span class="dashicons dashicons-saved wpwps-button-icon"></span>
                        <?php esc_html_e('Save API Key', 'wp-woocommerce-printify-sync'); ?>
                        <span class="wpwps-spinner" style="display:none;"></span>
                    </button>
                    
                    <button type="button" class="wpwps-button wpwps-button-secondary" id="wpwps-test-connection">
                        <span class="dashicons dashicons-laptop wpwps-button-icon"></span>
                        <?php esc_html_e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                        <span class="wpwps-spinner" style="display:none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- API Documentation Card -->
    <div class="wpwps-card">
        <div class="wpwps-card-header">
            <h2 class="wpwps-card-title"><?php esc_html_e('API Documentation', 'wp-woocommerce-printify-sync'); ?></h2>
        </div>
        <div class="wpwps-card-body wpwps-api-docs">
            <div class="wpwps-api-section">
                <h3><?php esc_html_e('Authentication', 'wp-woocommerce-printify-sync'); ?></h3>
                <p><?php esc_html_e('All API requests to Printify require authentication using a Bearer token in the Authorization header.', 'wp-woocommerce-printify-sync'); ?></p>
                <pre class="wpwps-code">
Authorization: Bearer YOUR_API_KEY</pre>
            </div>
            
            <div class="wpwps-api-section">
                <h3><?php esc_html_e('API Endpoints', 'wp-woocommerce-printify-sync'); ?></h3>
                <table class="wpwps-api-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Resource', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php esc_html_e('Endpoint', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php esc_html_e('Description', 'wp-woocommerce-printify-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Shops</td>
                            <td><code>GET /shops</code></td>
                            <td><?php esc_html_e('List all shops', 'wp-woocommerce-printify-sync'); ?></td>
                        </tr>
                        <tr>
                            <td>Products</td>
                            <td><code>GET /shops/{shop_id}/products.json</code></td>
                            <td><?php esc_html_e('List all products in a shop', 'wp-woocommerce-printify-sync'); ?></td>
                        </tr>
                        <tr>
                            <td>Product</td>
                            <td><code>GET /shops/{shop_id}/products/{product_id}.json</code></td>
                            <td><?php esc