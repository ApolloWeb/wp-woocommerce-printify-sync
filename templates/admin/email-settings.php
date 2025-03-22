<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-admin-wrap">
    <h1><?php esc_html_e('Email Settings', 'wp-woocommerce-printify-sync'); ?></h1>

    <div class="wpwps-email-dashboard">
        <div class="wpwps-grid">
            <div class="wpwps-card">
                <h3><?php esc_html_e('Queue Status', 'wp-woocommerce-printify-sync'); ?></h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="label"><?php esc_html_e('Pending', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html($queue_stats['pending']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="label"><?php esc_html_e('Failed', 'wp-woocommerce-printify-sync'); ?></span>
                        <span class="value"><?php echo esc_html($queue_stats['failed']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="options.php" class="wpwps-settings-form">
        <?php settings_fields('wpwps_email_settings'); ?>
        
        <div class="wpwps-settings-grid">
            <!-- SMTP Settings -->
            <div class="wpwps-card">
                <h3><?php esc_html_e('SMTP Settings', 'wp-woocommerce-printify-sync'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('SMTP Host', 'wp-woocommerce-printify-sync'); ?></th>
                        <td>
                            <input type="text" name="wpwps_smtp_settings[host]" 
                                   value="<?php echo esc_attr($smtp_settings['host'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Encryption', 'wp-woocommerce-printify-sync'); ?></th>
                        <td>
                            <select name="wpwps_smtp_settings[encryption]">
                                <option value="tls" <?php selected(($smtp_settings['encryption'] ?? ''), 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected(($smtp_settings['encryption'] ?? ''), 'ssl'); ?>>SSL</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- POP3 Settings -->
            <div class="wpwps-card">
                <h3><?php esc_html_e('POP3 Settings', 'wp-woocommerce-printify-sync'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Poll Interval', 'wp-woocommerce-printify-sync'); ?></th>
                        <td>
                            <select name="wpwps_pop3_settings[poll_interval]">
                                <option value="300">5 <?php esc_html_e('minutes', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="600">10 <?php esc_html_e('minutes', 'wp-woocommerce-printify-sync'); ?></option>
                                <option value="900">15 <?php esc_html_e('minutes', 'wp-woocommerce-printify-sync'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>
