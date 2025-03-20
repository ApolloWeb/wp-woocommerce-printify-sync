<?php
/**
 * Printify API Debug Tool
 * Access this file directly to test API connectivity
 * URL: /wp-content/plugins/wp-woocommerce-printify-sync/debug-printify-api.php
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

// Setup header
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printify API Debug Tool</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
        pre { background: #f5f5f5; padding: 15px; overflow: auto; border-radius: 3px; }
        .success { color: green; }
        .error { color: red; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 3px; }
        .hidden { display: none; }
        button { padding: 8px 16px; background: #0073aa; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #005177; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Printify API Debug Tool</h1>
    
    <div class="panel">
        <h2>Configuration</h2>
        <table>
            <tr>
                <th>Plugin Version</th>
                <td><?php echo defined('WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION') ? WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION : 'Unknown'; ?></td>
            </tr>
            <tr>
                <th>PHP Version</th>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <th>WordPress Version</th>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <th>API Key</th>
                <td>
                    <?php 
                    $api_key = get_option('wpwps_printify_api_key', '');
                    echo !empty($api_key) ? substr($api_key, 0, 5) . '...' . substr($api_key, -5) : '<span class="error">Not set</span>'; 
                    ?>
                </td>
            </tr>
            <tr>
                <th>API Endpoint</th>
                <td><?php echo get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1'); ?></td>
            </tr>
            <tr>
                <th>Shop ID</th>
                <td><?php echo get_option('wpwps_printify_shop_id', '') ?: '<span class="error">Not set</span>'; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="panel">
        <h2>Test API Connection</h2>
        <button id="testConnection">Test Connection</button>
        <div id="connectionResult" class="hidden"></div>
    </div>
    
    <div class="panel">
        <h2>Get Orders</h2>
        <p>Fetch the first page of orders from Printify.</p>
        <button id="fetchOrders">Fetch Orders (Page 1)</button>
        <div id="ordersResult" class="hidden"></div>
    </div>
    
    <div class="panel">
        <h2>Raw API Request</h2>
        <p>Make a raw API request to the Printify API.</p>
        <form id="rawRequestForm">
            <div style="margin-bottom: 10px;">
                <label for="endpoint">Endpoint (relative to base URL):</label><br>
                <input type="text" id="endpoint" style="width: 100%;" value="shops.json">
            </div>
            <button type="submit">Make Request</button>
        </form>
        <div id="rawRequestResult" class="hidden"></div>
    </div>
    
    <script>
        document.getElementById('testConnection').addEventListener('click', function() {
            const resultDiv = document.getElementById('connectionResult');
            resultDiv.innerHTML = '<p>Testing connection...</p>';
            resultDiv.classList.remove('hidden');
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'printify_sync',
                    action_type: 'test_connection',
                    nonce: '<?php echo wp_create_nonce('wpwps_nonce'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<p class="success">Connection successful!</p>';
                } else {
                    resultDiv.innerHTML = '<p class="error">Connection failed: ' + (data.data.message || 'Unknown error') + '</p>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">Network error: ' + error.message + '</p>';
            });
        });
        
        document.getElementById('fetchOrders').addEventListener('click', function() {
            const resultDiv = document.getElementById('ordersResult');
            resultDiv.innerHTML = '<p>Fetching orders...</p>';
            resultDiv.classList.remove('hidden');
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'printify_sync',
                    action_type: 'fetch_printify_orders',
                    nonce: '<?php echo wp_create_nonce('wpwps_nonce'); ?>',
                    page: 1,
                    per_page: 10,
                    refresh_cache: true
                })
            })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text.substring(0, 1000));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    let html = '<p class="success">Successfully fetched ' + data.data.orders.length + ' orders!</p>';
                    html += '<pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = '<p class="error">Failed to fetch orders: ' + (data.data.message || 'Unknown error') + '</p>' +
                                          '<pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">Error: ' + error.message + '</p>';
            });
        });
        
        document.getElementById('rawRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const endpoint = document.getElementById('endpoint').value;
            const resultDiv = document.getElementById('rawRequestResult');
            
            resultDiv.innerHTML = '<p>Making request to ' + endpoint + '...</p>';
            resultDiv.classList.remove('hidden');
            
            // Make a manual request to the Printify API
            const apiKey = '<?php echo esc_js(get_option('wpwps_printify_api_key', '')); ?>';
            const baseUrl = '<?php echo esc_js(get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1')); ?>';
            
            if (!apiKey) {
                resultDiv.innerHTML = '<p class="error">API key not configured</p>';
                return;
            }
            
            // Need to use a server-side proxy to avoid CORS issues
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'printify_sync',
                    action_type: 'debug_raw_request',
                    nonce: '<?php echo wp_create_nonce('wpwps_nonce'); ?>',
                    endpoint: endpoint
                })
            })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Invalid JSON response: ' + text.substring(0, 1000));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<p class="success">Request successful!</p>' +
                                          '<pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                } else {
                    resultDiv.innerHTML = '<p class="error">Request failed: ' + (data.data.message || 'Unknown error') + '</p>' +
                                          '<pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">Error: ' + error.message + '</p>';
            });
        });
        
        // Define ajaxurl if it's not already defined
        if (typeof ajaxurl === 'undefined') {
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        }
    </script>
</body>
</html>
<?php
exit;
