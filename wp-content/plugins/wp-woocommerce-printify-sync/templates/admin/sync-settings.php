<div class="wrap wpwps-admin-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php" class="wpwps-settings-form">
        <?php settings_fields('wpwps_sync_settings'); ?>
        
        <div class="card">
            <h2><?php _e('API Settings', 'wp-woocommerce-printify-sync'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Daily API Limit', 'wp-woocommerce-printify-sync'); ?></th>
                    <td>
                        <input type="number" name="wpwps_api_daily_limit" value="<?php echo esc_attr(get_option('wpwps_api_daily_limit', 5000)); ?>" min="100" max="10000" step="100" />
                        <p class="description"><?php _e('Maximum number of API calls allowed per day. Printify\'s standard limit is 5000 calls per day.', 'wp-woocommerce-printify-sync'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Per-Minute API Limit', 'wp-woocommerce-printify-sync'); ?></th>
                    <td>
                        <input type="number" name="wpwps_api_per_minute_limit" value="<?php echo esc_attr(get_option('wpwps_api_per_minute_limit', 60)); ?>" min="10" max="120" step="1" />
                        <p class="description"><?php _e('Maximum number of API calls allowed per minute. Printify\'s standard limit is 60 calls per minute.', 'wp-woocommerce-printify-sync'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Enable Rate Limit Protection', 'wp-woocommerce-printify-sync'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpwps_enable_rate_limiting" value="yes" <?php checked('yes', get_option('wpwps_enable_rate_limiting', 'yes')); ?> />
                            <?php _e('Automatically manage API rate limits and retry failed requests', 'wp-woocommerce-printify-sync'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, the plugin will automatically queue and retry requests when API rate limits are reached.', 'wp-woocommerce-printify-sync'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2><?php _e('Stock Sync Settings', 'wp-woocommerce-printify-sync'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Automatic Stock Sync', 'wp-woocommerce-printify-sync'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpwps_enable_stock_sync" value="yes" <?php checked('yes', get_option('wpwps_enable_stock_sync', 'yes')); ?> />
                            <?php _e('Automatically sync stock levels from Printify', 'wp-woocommerce-printify-sync'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, the plugin will periodically check and update product stock status from Printify.', 'wp-woocommerce-printify-sync'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Stock Sync Frequency', 'wp-woocommerce-printify-sync'); ?></th>
                    <td>
                        <select name="wpwps_stock_sync_frequency">
                            <option value="wpwps_six_hours" <?php selected('wpwps_six_hours', get_option('wpwps_stock_sync_frequency', 'wpwps_six_hours')); ?>><?php _e('Every 6 Hours', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="wpwps_twelve_hours" <?php selected('wpwps_twelve_hours', get_option('wpwps_stock_sync_frequency', 'wpwps_six_hours')); ?>><?php _e('Every 12 Hours', 'wp-woocommerce-printify-sync'); ?></option>
                            <option value="daily" <?php selected('daily', get_option('wpwps_stock_sync_frequency', 'wpwps_six_hours')); ?>><?php _e('Once Daily', 'wp-woocommerce-printify-sync'); ?></option>
                        </select>
                        <p class="description"><?php _e('How often should stock levels be synced from Printify.', 'wp-woocommerce-printify-sync'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Last Stock Sync', 'wp-woocommerce-printify-sync'); ?></th>
                    <td>
                        <?php 
                        $last_sync = get_option('wpwps_stock_sync_last', '');
                        if (!empty($last_sync)) {
                            echo sprintf(
                                __('Last synced on %s (%s ago)', 'wp-woocommerce-printify-sync'),
                                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync)),
                                human_time_diff(strtotime($last_sync), current_time('timestamp'))
                            );
                        } else {
                            echo __('Never synced', 'wp-woocommerce-printify-sync');
                        }
                        ?>
                        <p>
                            <button type="button" id="wpwps-sync-stock-now" class="button button-secondary">
                                <?php _e('Sync Stock Now', 'wp-woocommerce-printify-sync'); ?>
                            </button>
                            <span id="wpwps-sync-stock-message" style="display:none; margin-left:10px;"></span>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#wpwps-sync-stock-now').on('click', function() {
        const $button = $(this);
        const $message = $('#wpwps-sync-stock-message');
        
        $button.prop('disabled', true);
        $message.text('<?php _e('Scheduling sync...', 'wp-woocommerce-printify-sync'); ?>').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpwps_sync_stock_manually',
                nonce: '<?php echo wp_create_nonce('wpwps_admin'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $message.text(response.data.message).css('color', 'green');
                } else {
                    $message.text(response.data.message || '<?php _e('Error scheduling sync', 'wp-woocommerce-printify-sync'); ?>').css('color', 'red');
                }
            },
            error: function() {
                $message.text('<?php _e('Error scheduling sync', 'wp-woocommerce-printify-sync'); ?>').css('color', 'red');
            },
            complete: function() {
                $button.prop('disabled', false);
                setTimeout(function() {
                    $message.fadeOut();
                }, 5000);
            }
        });
    });
});
</script>
