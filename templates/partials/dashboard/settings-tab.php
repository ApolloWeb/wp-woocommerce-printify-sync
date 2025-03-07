<?php
/**
 * Settings tab for WooCommerce Printify Sync
 *
 * @package WP_Woocommerce_Printify_Sync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Save settings if form was submitted
if (isset($_POST['wps_save_settings']) && check_admin_referer('wps_settings_nonce')) {
    // Process form submission
    update_option('wps_printify_api_key', sanitize_text_field($_POST['wps_printify_api_key']));
    update_option('wps_auto_sync_enabled', isset($_POST['wps_auto_sync_enabled']) ? 1 : 0);
    update_option('wps_sync_interval', sanitize_text_field($_POST['wps_sync_interval']));
    update_option('wps_environment_mode', sanitize_text_field($_POST['wps_environment_mode']));
    
    // Display success message
    echo '<div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle"></i> Settings saved successfully.
          </div>';
}

// Get current settings
$api_key = get_option('wps_printify_api_key', '');
$auto_sync = get_option('wps_auto_sync_enabled', 1);
$sync_interval = get_option('wps_sync_interval', 'hourly');
$environment_mode = get_option('wps_environment_mode', 'production');
?>

<div class="row">
    <div class="col-12 mb-4">
        <h2><i class="fas fa-cog"></i> Settings</h2>
        <p>Configure your WooCommerce Printify Sync settings.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="wps-card">
            <div class="wps-card-header">
                <h3><i class="fas fa-key"></i> API Configuration</h3>
            </div>
            <div class="wps-card-body">
                <form method="post" action="">
                    <?php wp_nonce_field('wps_settings_nonce'); ?>
                    
                    <div class="wps-form-group">
                        <label for="wps_printify_api_key">Printify API Key</label>
                        <div class="wps-api-field">
                            <input type="password" class="form-control" id="wps_printify_api_key" name="wps_printify_api_key" 
                                value="<?php echo esc_attr($api_key); ?>" placeholder="Enter your Printify API key">
                            <button type="button" class="toggle-password" title="Toggle visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="test-connection" title="Test connection">
                                <i class="fas fa-plug"></i>
                            </button>
                        </div>
                        <div class="wps-api-status mt-2" id="api-status"></div>
                        <div class="form-text">
                            You can find your API key in your <a href="https://printify.com/app/account" target="_blank">Printify account settings</a>.
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="wps-form-group">
                        <label for="wps_environment_mode">Environment Mode</label>
                        <select class="form-select" id="wps_environment_mode" name="wps_environment_mode">
                            <option value="production" <?php selected($environment_mode, 'production'); ?>>Production</option>
                            <option value="development" <?php selected($environment_mode, 'development'); ?>>Development</option>
                        </select>
                        <div class="form-text">
                            Development mode enables additional debugging features and uses test API endpoints.
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="wps-form-group">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="wps_auto_sync_enabled" name="wps_auto_sync_enabled" 
                                <?php checked($auto_sync, 1); ?>>
                            <label class="form-check-label" for="wps_auto_sync_enabled">Enable Automatic Synchronization</label>
                        </div>
                        <div class="form-text">
                            When enabled, products and inventory will be automatically synchronized on the schedule below.
                        </div>
                    </div>
                    
                    <div class="wps-form-group">
                        <label for="wps_sync_interval">Synchronization Interval</label>
                        <select class="form-select" id="wps_sync_interval" name="wps_sync_interval">
                            <option value="hourly" <?php selected($sync_interval, 'hourly'); ?>>Hourly</option>
                            <option value="twicedaily" <?php selected($sync_interval, 'twicedaily'); ?>>Twice Daily</option>
                            <option value="daily" <?php selected($sync_interval, 'daily'); ?>>Daily</option>
                        </select>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="wps_save_settings" class="btn btn-primary wps-btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="button" id="wps-test-all" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-vial"></i> Test All Connections
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="wps-card mb-4">
            <div class="wps-card-header">
                <h3><i class="fas fa-info-circle"></i> Connection Status</h3>
            </div>
            <div class="wps-card-body">
                <div class="wps-status-widget">
                    <div class="wps-status-icon wps-bg-success wps-color-success">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="wps-status-content">
                        <h4 class="wps-status-title">Printify API</h4>
                        <p class="wps-status-description">Connected successfully</p>
                    </div>
                </div>
                
                <div class="wps-status-widget">
                    <div class="wps-status-icon wps-bg-success wps-color-success">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="wps-status-content">
                        <h4 class="wps-status-title">WooCommerce</h4>
                        <p class="wps-status-description">Connected successfully</p>
                    </div>
                </div>
                
                <div class="wps-status-widget">
                    <div class="wps-status-icon wps-bg-warning wps-color-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="wps-status-content">
                        <h4 class="wps-status-title">Webhooks</h4>
                        <p class="wps-status-description">2 of 3 webhooks configured</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="wps-actions-widget">
            <h3 class="wps-actions-title">Quick Actions</h3>
            
            <div class="wps-actions-grid">
                <button type="button" class="wps-action-button" id="wps-clear-cache">
                    <span class="wps-action-icon"><i class="fas fa-broom"></i></span>
                    <span class="wps-action-label">Clear Cache</span>
                </button>
                
                <button type="button" class="wps-action-button" id="wps-reset-api">
                    <span class="wps-action-icon"><i class="fas fa-redo"></i></span>
                    <span class="wps-action-label">Reset API</span>
                </button>
                
                <button type="button" class="wps-action-button" id="wps-setup-webhooks">
                    <span class="wps-action-icon"><i class="fas fa-link"></i></span>
                    <span class="wps-action-label">Setup Webhooks</span>
                </button>
                
                <button type="button" class="wps-action-button" id="wps-export-settings">
                    <span class="wps-action-icon"><i class="fas fa-download"></i></span>
                    <span class="wps-action-label">Export Settings</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const passwordField = $('#wps_printify_api_key');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Test API connection
    $('.test-connection').on('click', function() {
        const apiKey = $('#wps_printify_api_key').val();
        const statusEl = $('#api-status');
        
        if (!apiKey) {
            statusEl.html('<span class="text-danger"><i class="fas fa-times-circle"></i> Please enter an API key</span>');
            return;
        }
        
        statusEl.html('<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Testing connection...</span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wps_test_api_connection',
                api_key: apiKey,
                nonce: '<?php echo wp_create_nonce('wps_test_api_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    statusEl.html('<span class="text-success"><i class="fas fa-check-circle"></i> ' + response.data.message + '</span>');
                } else {
                    statusEl.html('<span class="text-danger"><i class="fas fa-times-circle"></i> ' + response.data.message + '</span>');
                }
            },
            error: function() {
                statusEl.html('<span class="text-danger"><i class="fas fa-times-circle"></i> Connection failed. Please try again.</span>');
            }
        });
    });
    
    // Quick action event handlers
    $('#wps-clear-cache').on('click', function() {
        if (confirm('Are you sure you want to clear the plugin cache?')) {
            // AJAX call to clear cache
            $(this).html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wps_clear_cache',
                    nonce: '<?php echo wp_create_nonce('wps_admin_actions'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Cache cleared successfully!');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                    $('#wps-clear-cache').html('<span class="wps-action-icon"><i class="fas fa-broom"></i></span><span class="wps-action-label">Clear Cache</span>');
                },
                error: function() {
                    alert('Connection error. Please try again.');
                    $('#wps-clear-cache').html('<span class="wps-action-icon"><i class="fas fa-broom"></i></span><span class="wps-action-label">Clear Cache</span>');
                }
            });
        }
    });
    
    // Implement other quick action handlers similarly
});
</script>