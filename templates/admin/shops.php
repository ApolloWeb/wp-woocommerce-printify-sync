<?php
/**
 * Admin shops page template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

defined( 'ABSPATH' ) || exit;

// Current time and user info
$current_time = '2025-03-06 01:34:35';
$current_user = 'ApolloWeb';
?>

<div class="wrap printify-sync-shops">
    <h1><?php esc_html_e( 'Printify Shops', 'wp-woocommerce-printify-sync' ); ?></h1>
    
    <div class="printify-sync-meta">
        <p>
            <span class="dashicons dashicons-clock"></span> 
            <?php esc_html_e( 'Current Time (UTC):', 'wp-woocommerce-printify-sync' ); ?> 
            <strong><?php echo esc_html( $current_time ); ?></strong>
        </p>
        <p>
            <span class="dashicons dashicons-admin-users"></span> 
            <?php esc_html_e( 'Logged in as:', 'wp-woocommerce-printify-sync' ); ?> 
            <strong><?php echo esc_html( $current_user ); ?></strong>
        </p>
    </div>
    
    <?php if ( isset( $api_connected ) && ! $api_connected ) : ?>
        <div class="notice notice-error">
            <p>
                <?php esc_html_e( 'Printify API connection not configured. Please configure API credentials in the settings page.', 'wp-woocommerce-printify-sync' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=printify-sync-settings' ) ); ?>" class="button button-small">
                    <?php esc_html_e( 'Go to Settings', 'wp-woocommerce-printify-sync' ); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="printify-sync-shops-actions">
        <button id="fetch-shops" class="button button-primary">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e( 'Fetch Available Shops', 'wp-woocommerce-printify-sync' ); ?>
        </button>
    </div>
    
    <div id="printify-sync-shops-container">
        <?php if ( isset( $shops ) && ! empty( $shops ) ) : ?>
            <table class="widefat printify-sync-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Shop ID', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Shop Name', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Sales Channel', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Connected', 'wp-woocommerce-printify-sync' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wp-woocommerce-printify-sync' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $shops as $shop ) : 
                        $is_connected = isset( $connected_shop['id'] ) && $connected_shop['id'] == $shop['id'];
                    ?>
                        <tr>
                            <td><?php echo esc_html( $shop['id'] ); ?></td>
                            <td><?php echo esc_html( $shop['title'] ); ?></td>
                            <td><?php echo esc_html( $shop['sales_channel'] ); ?></td>
                            <td>
                                <?php if ( $is_connected ) : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-no-alt" style="color: red;"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $is_connected ) : ?>
                                    <button class="button disconnect-shop" data-shop-id="<?php echo esc_attr( $shop['id'] ); ?>">
                                        <?php esc_html_e( 'Disconnect', 'wp-woocommerce-printify-sync' ); ?>
                                    </button>
                                <?php else : ?>
                                    <button class="button button-primary connect-shop" data-shop-id="<?php echo esc_attr( $shop['id'] ); ?>">
                                        <?php esc_html_e( 'Connect', 'wp-woocommerce-printify-sync' ); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="printify-sync-notice notice-info">
                <p><?php esc_html_e( 'No shops found. Click "Fetch Available Shops" to retrieve shops from Printify.', 'wp-woocommerce-printify-sync' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Fetch shops button
        $('#fetch-shops').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true);
            $button.html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Fetching...', 'wp-woocommerce-printify-sync' ); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'printify_sync_shops',
                    action_type: 'fetch_shops',
                    nonce: PrintifySyncAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show the shops
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php esc_html_e( 'Error fetching shops.', 'wp-woocommerce-printify-sync' ); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e( 'Error connecting to server.', 'wp-woocommerce-printify-sync' ); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.html('<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Fetch Available Shops', 'wp-woocommerce-printify-sync' ); ?>');
                }
            });
        });

        // Connect shop button
        $('.connect-shop').on('click', function() {
            var $button = $(this);
            var shopId = $button.data('shop-id');
            
            $button.prop('disabled', true);
            $button.text('<?php esc_html_e( 'Connecting...', 'wp-woocommerce-printify-sync' ); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'printify_sync_shops',
                    action_type: 'connect_shop',
                    shop_id: shopId,
                    nonce: PrintifySyncAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php esc_html_e( 'Error connecting shop.', 'wp-woocommerce-printify-sync' ); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e( 'Error connecting to server.', 'wp-woocommerce-printify-sync' ); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.text('<?php esc_html_e( 'Connect', 'wp-woocommerce-printify-sync' ); ?>');
                }
            });
        });

        // Disconnect shop button
        $('.disconnect-shop').on('click', function() {
            if (!confirm('<?php esc_html_e( 'Are you sure you want to disconnect this shop? This will remove all associated data.', 'wp-woocommerce-printify-sync' ); ?>')) {
                return;
            }
            
            var $button = $(this);
            var shopId = $button.data('shop-id');
            
            $button.prop('disabled', true);
            $button.text('<?php esc_html_e( 'Disconnecting...', 'wp-woocommerce-printify-sync' ); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'printify_sync_shops',
                    action_type: 'disconnect_shop',
                    shop_id: shopId,
                    nonce: PrintifySyncAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php esc_html_e( 'Error disconnecting shop.', 'wp-woocommerce-printify-sync' ); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e( 'Error connecting to server.', 'wp-woocommerce-printify-sync' ); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.text('<?php esc_html_e( 'Disconnect', 'wp-woocommerce-printify-sync' ); ?>');
                }
            });
        });
    });
</script>

<style>
.printify-sync-shops {
    margin-top: 20px;
}

.printify-sync-meta {
    background-color: #fff;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.printify-sync-shops-actions {
    margin-bottom: 20px;
}

.printify-sync-notice {
    padding: 15px;
    margin-bottom: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spin {
    animation: spin 2s linear infinite;
}
</style>