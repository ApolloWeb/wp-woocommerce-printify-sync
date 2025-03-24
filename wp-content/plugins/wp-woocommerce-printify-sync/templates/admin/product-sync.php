<?php
/**
 * Product sync template.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

$settings = new Settings();
$api_key = $settings->getOption('api_key');
?>

<div class="wrap">
    <h1><?php echo esc_html__('Sync Products from Printify', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <?php if (empty($api_key)) : ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo sprintf(
                    /* translators: %s: Settings page URL */
                    esc_html__('Printify API key is not set. Please set it in the %s.', 'wp-woocommerce-printify-sync'),
                    '<a href="' . esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-settings')) . '">' . esc_html__('settings page', 'wp-woocommerce-printify-sync') . '</a>'
                );
                ?>
            </p>
        </div>
    <?php elseif (empty($shops)) : ?>
        <div class="notice notice-error">
            <p><?php echo esc_html__('No shops found. Please check your API key.', 'wp-woocommerce-printify-sync'); ?></p>
        </div>
    <?php else : ?>
        <div class="wpwps-sync-container">
            <div class="wpwps-sync-options">
                <form id="wpwps-sync-form">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php echo esc_html__('Shop', 'wp-woocommerce-printify-sync'); ?></th>
                            <td>
                                <select name="shop_id" id="wpwps-shop-id" required>
                                    <option value=""><?php echo esc_html__('Select a shop', 'wp-woocommerce-printify-sync'); ?></option>
                                    <?php foreach ($shops as $shop) : ?>
                                        <option value="<?php echo esc_attr($shop['id']); ?>"><?php echo esc_html($shop['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" id="wpwps-sync-products" class="button button-primary">
                            <?php echo esc_html__('Sync Products', 'wp-woocommerce-printify-sync'); ?>
                        </button>
                        <span class="spinner" id="wpwps-sync-spinner"></span>
                    </p>
                </form>
            </div>
            
            <div class="wpwps-sync-results" style="display: none;">
                <h2><?php echo esc_html__('Sync Results', 'wp-woocommerce-printify-sync'); ?></h2>
                <div id="wpwps-sync-status" class="notice is-dismissible" style="display: none;"></div>
                
                <div id="wpwps-sync-products-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Product ID', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php echo esc_html__('Name', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php echo esc_html__('Price', 'wp-woocommerce-printify-sync'); ?></th>
                                <th><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="wpwps-products-tbody">
                            <!-- Products will be added here via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
