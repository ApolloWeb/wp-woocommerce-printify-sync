<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class R2Settings
{
    private string $currentTime = '2025-03-15 18:49:35';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function registerSettings(): void
    {
        register_setting('wpwps_r2_settings', 'wpwps_r2_enabled');
        register_setting('wpwps_r2_settings', 'wpwps_r2_account_id');
        register_setting('wpwps_r2_settings', 'wpwps_r2_access_key');
        register_setting('wpwps_r2_settings', 'wpwps_r2_secret_key');
        register_setting('wpwps_r2_settings', 'wpwps_r2_bucket');
        register_setting('wpwps_r2_settings', 'wpwps_r2_cdn_url');
    }

    public function renderSettings(): void
    {
        ?>
        <div class="wrap wpwps-wrapper">
            <h1>R2 Storage Settings</h1>

            <?php if ($this->testConnection()): ?>
                <div class="notice notice-success">
                    <p>R2 connection successful!</p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('wpwps_r2_settings'); ?>

                <div class="wpwps-card">
                    <div class="wpwps-card-header">
                        <h2>Cloudflare R2 Configuration</h2>
                    </div>
                    <div class="wpwps-card-body">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable R2 Storage</th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="wpwps_r2_enabled" 
                                               value="1" 
                                               <?php checked(get_option('wpwps_r2_enabled')); ?>>
                                        Enable Cloudflare R2 storage
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Account ID</th>
                                <td>
                                    <input type="text" 
                                           name="wpwps_r2_account_id" 
                                           value="<?php echo esc_attr(get_option('wpwps_r2_account_id')); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Access Key</th>
                                <td>
                                    <input type="password" 
                                           name="wpwps_r2_access_key" 
                                           value="<?php echo esc_attr(get_option('wpwps_r2_access_key')); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Secret Key</th>
                                <td>
                                    <input type="password" 
                                           name="wpwps_r2_secret_key" 
                                           value="<?php echo esc_attr(get_option('wpwps_r2_secret_key')); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Bucket Name</th>
                                <td>
                                    <input type="text" 
                                           name="wpwps_r2_bucket" 
                                           value="<?php echo esc_attr(get_option('wpwps_r2_bucket')); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">CDN URL (Optional)</th>
                                <td>
                                    <input type="url" 
                                           name="wpwps_r2_cdn_url" 
                                           value="<?php echo esc_attr(get_option('wpwps_r2_cdn_url')); ?>" 
                                           class="regular-text">
                                    <p class="description">
                                        If you're using Cloudflare CDN, enter the URL here.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php submit_button('Save R2 Settings'); ?>
            </form>
        </div>
        <?php
    }

    private function testConnection(): bool
    {
        if (!get_option('wpwps_r2_enabled')) {
            return false;
        }

        try {
            $r2 = new \ApolloWeb\WPWooCommercePrintifySync\Services\R2StorageService();
            
            // Test connection by listing objects
            $r2->client->listObjects([
                'Bucket' => get_option('wpwps_r2_bucket'),
                'MaxKeys' => 1
            ]);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}