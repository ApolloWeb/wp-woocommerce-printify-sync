<?php
/**
 * Settings Page Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get current user info
$current_user = function_exists('printify_sync_get_current_user') ? 
    printify_sync_get_current_user() : 'User';
    
$current_datetime = function_exists('printify_sync_get_current_datetime') ?
    printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');

// Get saved API settings (already masked if needed)
$printify_api_key = get_option('printify_sync_api_key_masked', '');
$printify_endpoint = get_option('printify_sync_endpoint', 'https://api.printify.com');
$geolocation_api_key = get_option('printify_sync_geolocation_api_key_masked', '');
$currency_api_key = get_option('printify_sync_currency_api_key_masked', '');
$postman_api_key = get_option('printify_sync_postman_api_key_masked', '');

// Get environment setting
$environment = get_option('printify_sync_environment', 'production');

// Nonce for security
$settings_nonce = wp_create_nonce('printify_sync_settings_nonce');
?>

<div class="printify-dashboard-page">
    
    <?php 
    // Include the navigation
    if (file_exists(PRINTIFY_SYNC_PATH . 'templates/admin/navigation.php')) {
        include PRINTIFY_SYNC_PATH . 'templates/admin/navigation.php';
    }
    ?>
    
    <div class="printify-content">
        <div class="settings-header">
            <h1><i class="fas fa-cog"></i> Plugin Settings</h1>
            <p>Configure your Printify Sync plugin settings and API connections.</p>
        </div>
        
        <div class="settings-notifications">
            <div id="settings-notification" class="settings-notification hidden"></div>
        </div>
        
        <!-- Environment Mode Settings -->
        <div class="settings-card" id="environment-settings">
            <div class="settings-card-header">
                <h2>Environment Mode</h2>
                <span class="settings-card-description">Toggle between Production and Development modes</span>
            </div>
            
            <div class="settings-card-content">
                <div class="settings-form-row">
                    <div class="settings-form-label">
                        <label for="environment_mode">Environment Mode</label>
                        <span class="tooltip-icon" data-tippy-content="Production mode disables debugging and hides development features. Development mode enables additional testing tools.">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </div>
                    <div class="settings-form-input">
                        <div class="toggle-switch-container">
                            <label class="toggle-switch">
                                <input type="checkbox" id="environment_mode" name="environment_mode" <?php checked($environment, 'development'); ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <span id="environment_mode_label" class="toggle-label <?php echo $environment === 'production' ? 'production' : 'development'; ?>">
                                <?php echo ucfirst($environment); ?> Mode
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="environment-info">
                    <?php if ($environment === 'development'): ?>
                    <div class="environment-warning development">
                        <i class="fas fa-code"></i>
                        <div>
                            <strong>Development Mode Active</strong>
                            <p>Debug features are enabled. The Postman menu item is visible. Do not use in production.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="environment-warning production">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <strong>Production Mode Active</strong>
                            <p>Debug features are disabled. The Postman menu item is hidden. Ready for live use.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="settings-form-actions">
                    <button type="button" id="save-environment-settings" class="printify-btn" data-section="environment">
                        <i class="fas fa-save"></i> Save Environment Settings
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Printify API Settings -->
        <div class="settings-card" id="printify-api-settings">
            <div class="settings-card-header">
                <h2>Printify API Settings</h2>
                <span class="settings-card-description">Configure your Printify API connection</span>
            </div>
            
            <div class="settings-card-content">
                <div class="settings-form-row">
                    <div class="settings-form-label">
                        <label for="printify_api_key">API Key</label>
                        <span class="tooltip-icon" data-tippy-content="Your Printify API key from the developer dashboard.">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </div>
                    <div class="settings-form-input">
                        <input type="password" id="printify_api_key" name="printify_api_key" 
                               placeholder="Enter your Printify API key" value="<?php echo esc_attr($printify_api_key); ?>">
                        <button type="button" class="toggle-password" data-target="printify_api_key">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="settings-form-row">
                    <div class="settings-form-label">
                        <label for="printify_endpoint">API Endpoint</label>
                        <span class="tooltip-icon" data-tippy-content="Enter the Printify API endpoint.">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </div>
                    <div class="settings-form-input">
                        <input type="text" id="printify_endpoint" name="printify_endpoint" 
                               placeholder="Enter the API endpoint" value="<?php echo esc_attr($printify_endpoint); ?>">
                    </div>
                </div>
                
                <div class="settings-form-actions">
                    <button type="button" id="save-printify-settings" class="printify-btn" data-section="printify">
                        <i class="fas fa-save"></i> Save Printify Settings
                    </button>
                    <button type="button" id="test-printify-api" class="printify-btn btn-outline">
                        <i class="fas fa-plug"></i> Test Connection
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Geolocation API Settings -->
        <div class="settings-card" id="geolocation-api-settings">
            <div class="settings-card-header">
                <h2>Geolocation API Settings</h2>
                <span class="settings-card-description">Configure your IPGeolocation.io API connection</span>
            </div>
            
            <div class="settings-card-content">
                <div class="settings-form-row">
                    <div class="settings-form-label">
                        <label for="geolocation_api_key">API Key</label>
                        <span class="tooltip-icon" data-tippy-content="Your IPGeolocation.io API key for location services.">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </div>
                    <div class="settings-form-input">
                        <input type="password" id="geolocation_api_key" name="geolocation_api_key" 
                               placeholder="Enter your IPGeolocation.io API key" value="<?php echo esc_attr($geolocation_api_key); ?>">
                        <button type="button" class="toggle-password" data-target="geolocation_api_key">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="settings-form-actions">
                    <button type="button" id="save-geolocation-settings" class="printify-btn" data-section="geolocation">
                        <i class="fas fa-save"></i> Save Geolocation Settings
                    </button>
                    <button type="button" id="test-geolocation-api" class="printify-btn btn-outline">
                        <i class="fas fa-plug"></i> Test Connection
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Currency API Settings -->
        <div class="settings-card" id="currency-api-settings">
            <div class="settings-card-header">
                <h2>Currency Conversion API Settings</h2>
                <span class="settings-card-description">Configure your FreeCurrencyAPI.com API connection</span>
            </div>
            
            <div class="settings-card-content">
                <div class="settings-form-row">
                    <div class="settings-form-label">
                        <label for="currency_api_key">API Key</label>
                        <span class="tooltip-icon" data-tippy-content="Your FreeCurrencyAPI.com API key for currency conversion.">
                            <i class="fas fa-question-circle"></i>
                        </span>
                    </div>
                    <div class="settings-form-input">
                        <input type="password" id="currency_api_key" name="currency_api_key" 
                               placeholder="Enter your FreeCurrencyAPI.com API key" value="<?php echo esc_attr($currency_api_key); ?>">
                        <button type="button" class="toggle-password" data-target="currency_api_key">
                            <i class="fas fa-eye"></i>