<?php
/**
 * Check the service container for PrintifyAPI registration and configuration
 */

// Make sure only administrators can run this script
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Exit if not an administrator
if (!current_user_can('administrator')) {
    die('Unauthorized access. Only administrators can run this tool.');
}

// Include the plugin file to ensure everything is loaded
include_once(WP_PLUGIN_DIR . '/wp-woocommerce-printify-sync/wp-woocommerce-printify-sync.php');

// Get the container from the plugin
global $wpwps_plugin;
if (!isset($wpwps_plugin) || !method_exists($wpwps_plugin, 'getContainer')) {
    die('Could not find plugin container instance');
}

$container = $wpwps_plugin->getContainer();

// Check for the PrintifyAPI service
echo "<h1>Printify API Service Verification</h1>";

// Check if the container has the service registered
echo "<h2>Service Registrations:</h2>";
echo "<ul>";
echo "<li>Has printify_api: " . ($container->has('printify_api') ? 'Yes' : 'No') . "</li>";
echo "<li>Has printify_http_client: " . ($container->has('printify_http_client') ? 'Yes' : 'No') . "</li>";
echo "</ul>";

// Get the API settings
$api_key = get_option('wpwps_printify_api_key', '');
$shop_id = get_option('wpwps_printify_shop_id', '');
$endpoint = get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1');

echo "<h2>API Settings:</h2>";
echo "<ul>";
echo "<li>API Key: " . (empty($api_key) ? 'Not set' : substr($api_key, 0, 5) . '...' . substr($api_key, -5)) . "</li>";
echo "<li>Shop ID: " . (empty($shop_id) ? 'Not set' : $shop_id) . "</li>";
echo "<li>Endpoint: " . $endpoint . "</li>";
echo "</ul>";

// Try to get the API instance
echo "<h2>Test API Client:</h2>";
try {
    $api = $container->get('printify_api');
    echo "<p style='color: green;'>Successfully retrieved PrintifyAPI instance from container</p>";
    
    // Check if it's the correct class
    echo "<p>Class: " . get_class($api) . "</p>";
    
    // Try to access a method
    echo "<p>Testing connection method: ";
    $result = $api->testConnection();
    echo $result ? "Connection successful" : "Connection failed";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error retrieving PrintifyAPI: " . $e->getMessage() . "</p>";
}

echo "<p><a href='admin.php?page=wpwps-settings'>Return to Settings</a></p>";
