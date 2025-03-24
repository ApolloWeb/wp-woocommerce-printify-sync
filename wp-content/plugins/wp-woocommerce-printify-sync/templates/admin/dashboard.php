<?php
/**
 * Dashboard template.
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
    <h1><?php echo esc_html__('WC Printify Sync Dashboard', 'wp-woocommerce-printify-sync'); ?></h1>
    
    <div class="wpwps-dashboard-wrapper">
        <?php if (empty($api_key)) : ?>
            <div class="wpwps-setup-prompt">
                <h2><?php echo esc_html__('Welcome to WC Printify Sync', 'wp-woocommerce-printify-sync'); ?></h2>
                <p><?php echo esc_html__('To get started, you need to connect to Printify by setting up your API key.', 'wp-woocommerce-printify-sync'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-settings')); ?>" class="button button-primary"><?php echo esc_html__('Set Up API Key', 'wp-woocommerce-printify-sync'); ?></a>
            </div>
        <?php else : ?>
            <div class="wpwps-dashboard-cards">
                <div class="wpwps-card">
                    <h2><?php echo esc_html__('Sync Products', 'wp-woocommerce-printify-sync'); ?></h2>
                    <p><?php echo esc_html__('Sync your products from Printify to WooCommerce.', 'wp-woocommerce-printify-sync'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-products')); ?>" class="button button-primary"><?php echo esc_html__('Sync Products', 'wp-woocommerce-printify-sync'); ?></a>
                </div>
                
                <div class="wpwps-card">
                    <h2><?php echo esc_html__('Settings', 'wp-woocommerce-printify-sync'); ?></h2>
                    <p><?php echo esc_html__('Configure how products are synced from Printify.', 'wp-woocommerce-printify-sync'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-woocommerce-printify-sync-settings')); ?>" class="button button-secondary"><?php echo esc_html__('Manage Settings', 'wp-woocommerce-printify-sync'); ?></a>
                </div>
            </div>
            
            <div class="wpwps-dashboard-info">
                <h2><?php echo esc_html__('Plugin Information', 'wp-woocommerce-printify-sync'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Plugin Version', 'wp-woocommerce-printify-sync'); ?></th>
                        <td><?php echo esc_html(WPWPS_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('WooCommerce Version', 'wp-woocommerce-printify-sync'); ?></th>
                        <td><?php echo esc_html(WC()->version); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Auto Sync', 'wp-woocommerce-printify-sync'); ?></th>
                        <td><?php echo $settings->getOption('auto_sync') ? esc_html__('Enabled', 'wp-woocommerce-printify-sync') : esc_html__('Disabled', 'wp-woocommerce-printify-sync'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Sync Interval', 'wp-woocommerce-printify-sync'); ?></th>
                        <td>
                            <?php
                            $interval = $settings->getOption('sync_interval');
                            $intervals = [
                                'hourly' => __('Hourly', 'wp-woocommerce-printify-sync'),
                                'twicedaily' => __('Twice Daily', 'wp-woocommerce-printify-sync'),
                                'daily' => __('Daily', 'wp-woocommerce-printify-sync'),
                            ];
                            echo esc_html($intervals[$interval] ?? $interval);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Next Scheduled Sync', 'wp-woocommerce-printify-sync'); ?></th>
                        <td>
                            <?php
                            $next_sync = wp_next_scheduled('wpwps_daily_sync');
                            echo $next_sync ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_sync)) : esc_html__('Not scheduled', 'wp-woocommerce-printify-sync');
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
