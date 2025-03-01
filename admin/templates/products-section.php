<?php
/**
 * Products section template
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
                <path d="M15.55 13c.75 0 1.41-.41 1.75-1.03l3.58-6.49A.996.996 0 0 0 20.01 4H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7l1.1-2h7.45zM6.16 6h12.15l-2.76 5H8.53L6.16 6zM7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
        </div>
        <div class="wpwps-header-title">
            <h1><?php esc_html_e('Printify Products', 'wp-woocommerce-printify-sync'); ?></h1>
            <div class="wpwps-version">
                <?php esc_html_e('Version', 'wp-woocommerce-printify-sync'); ?> <?php echo esc_html(WPWPS_VERSION); ?>
                • <?php echo esc_html('2025-03-01 08:44:05'); ?>
                • <?php echo esc_html('ApolloWeb'); ?>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="wpwps-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync')); ?>" class="wpwps-nav-item">
            <?php esc_html_e('Settings', 'wp-woocommerce-printify-sync'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-shops')); ?>" class="wpwps-nav-item">
            <?php esc_html_e('Shops', 'wp-woocommerce-printify-sync'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-products')); ?>" class="wpwps-nav-item active">
            <?php esc_html_e('Products', 'wp-woocommerce-printify-sync'); ?>
        </a>
    </div>
    
    <?php if (empty($shop_id)) : ?>
    <!-- No Shop Selected Message -->
    <div class="wpwps-message wpwps-message-warning">
        <div class="wpwps-message-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <div class="wpwps-message-content">
            <div class="wpwps-message-title"><?php esc_html_e('No Shop Selected', 'wp-woocommerce-printify-sync'); ?></div>
            <div class="wpwps-message-body">
                <?php esc_html_e('Please select a shop from the Shops page before importing products.', 'wp-woocommerce-printify-sync'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-shops')); ?>" class="wpwps-link">
                    <?php esc_html_e('Go to Shops', 'wp-woocommerce-printify-sync'); ?> →
                </a>
            </div>
        </div>
    </div>
    <?php else : ?>
    
    <!-- Product Import Card -->
    <div class="wpwps-card">
        <div class="wpwps-card-header">
            <h2 class="wpwps-card-title"><?php esc_html_e('Import Products', 'wp-woocommerce-printify-sync'); ?></h2>
            <div class="wpwps-card-actions">
                <span class="wpwps-status-badge wpwps-status-active">
                    <?php esc_html_e('Shop ID:', 'wp-woocommerce-printify-sync'); ?> <?php echo esc_html($shop_id); ?>
                </span>
            </div>
        </div>
        <div class="wpwps-card-body">
            <div class="wpwps-message wpwps-message-info">
                <div class="wpwps-message-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                </div>
                <div class="wpwps-message-content">
                    <div class="wpwps-message-body">
                        <?php esc_html_e('Import products from your selected Printify shop to WooCommerce. This process may take several minutes depending on the number of products.', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                </div>
            </div>
            
            <div class="wpwps-form-actions">
                <button type="button" id="wpwps-import-products" class="wpwps-button wpwps-button-primary">
                    <span class="wpwps-button-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </span>
                    <?php esc_html_e('Import Products', 'wp-woocommerce-printify-sync'); ?>
                    <span class="wpwps-spinner" style="display:none;"></span>
                </button>
                
                <button type="button" id="wpwps-clear-products" class="wpwps-button wpwps-button-danger">
                    <span class="wpwps-button-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </span>
                    <?php esc_html_e('Clear Products', 'wp-woocommerce-printify-sync'); ?>
                    <span class="wpwps-spinner" style="display:none;"></span>
                </button>
            </div>
            
            <!-- Progress Section - Hidden by default -->
            <div id="wpwps-import-progress" class="wpwps-progress" style="display:none;">
                <h3><?php esc_html_e('Import Progress', 'wp-woocommerce-printify-sync'); ?></h3>
                
                <div class="wpwps-progress-container">
                    <div id="wpwps-progress-bar" class="wpwps-progress-bar" style="width:0%"></div>
                </div>
                
                <div class="wpwps-progress-stats">
                    <div id="wpwps-progress-percent" class="wpwps-progress-percent">0%</div>
                    <div id="wpwps-progress-count" class="wpwps-progress-count">0 / 0</div>
                </div>
                
                <div class="wpwps-progress-details">
                    <div class="wpwps-progress-stat">
                        <div class="wpwps-progress-stat-label"><?php esc_html_e('Processed', 'wp-woocommerce-printify-sync'); ?></div>
                        <div id="wpwps-processed" class="wpwps-progress-stat-value">0</div>
                    </div>
                    <div class="wpwps-progress-stat">
                        <div class="wpwps-progress-stat-label"><?php esc_html_e('Created', 'wp-woocommerce-printify-sync'); ?></div>
                        <div id="wpwps-created" class="wpwps-progress-stat-value">0</div>
                    </div>
                    <div class="wpwps-progress-stat">
                        <div class="wpwps-progress-stat-label"><?php esc_html_e('Updated', 'wp-woocommerce-printify-sync'); ?></div>
                        <div id="wpwps-updated" class="wpwps-progress-stat-value">0</div>
                    </div>
                    <div class="wpwps-progress-stat">
                        <div class="wpwps-progress-stat-label"><?php esc_html_e('Skipped', 'wp-woocommerce-printify-sync'); ?></div>
                        <div id="wpwps-skipped" class="wpwps-progress-stat-value">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Products List Card -->
    <div class="wpwps-card" id="wpwps-products-list-card">
        <div class="wpwps-card-header">
            <h2 class="wpwps-card-title"><?php esc_html_e('Imported Products', 'wp-woocommerce-printify-sync'); ?></h2>
            <div class="wpwps-card-actions">
                <div class="wpwps-search-box">
                    <input type="text" id="wpwps-search-products" class="wpwps-form-input" placeholder="<?php esc_attr_e('Search products...', 'wp-woocommerce-printify-sync'); ?>">
                </div>
            </div>
        </div>
        <div class="wpwps-card-body wpwps-table-container">
            <table class="wpwps-table" id="wpwps-products-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Image', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Title', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('SKU', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Price', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Status', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Last Updated', 'wp-woocommerce-printify-sync'); ?></th>
                        <th><?php esc_html_e('Actions', 'wp-woocommerce-printify-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded by JavaScript -->
                    <tr class="wpwps-no-products">
                        <td colspan="7" class="wpwps-empty-table">
                            <?php esc_html_e('No products imported yet. Click "Import Products" to start.', 'wp-woocommerce-printify-sync'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="wpwps-pagination">
                <div class="wpwps-pagination-info">
                    <?php esc_html_e('Showing', 'wp-woocommerce-printify-sync'); ?> <span id="wpwps-pagination-showing">0</span> <?php esc_html_e('of', 'wp-woocommerce-printify-sync'); ?> <span id="wpwps-pagination-total">0</span> <?php esc_html_e('products', 'wp-woocommerce-printify-sync'); ?>
                </div>
                <div class="wpwps-pagination-controls">
                    <button type="button" id="wpwps-pagination-prev" class="wpwps-button wpwps-button-secondary wpwps-pagination-button" disabled>
                        <?php esc_html_e('Previous', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                    <button type="button" id="wpwps-pagination-next" class="wpwps-button wpwps-button-secondary wpwps-pagination-button" disabled>
                        <?php esc_html_e('Next', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Message Container -->
    <div id="wpwps-products-message"></div>
</div>