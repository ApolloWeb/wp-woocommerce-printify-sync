<?php
/**
 * Debug tool for Printify Orders API
 * This file can be accessed directly at: /wp-content/plugins/wp-woocommerce-printify-sync/debug-printify-orders.php
 */

// Ensure this is only accessible by admins
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Exit if not an admin or if debug mode is off
if (!current_user_can('manage_options') || !defined('WP_DEBUG') || !WP_DEBUG) {
    die('Unauthorized access');
}

?><!DOCTYPE html>
<html>
<head>
    <title>Printify Orders API Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
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
    <h1>Printify Orders API Debug</h1>
    
    <div class="panel">
        <h2>Direct Orders API Request</h2>
        <p>This uses the Printify API directly with your configured credentials to fetch orders.</p>
        
        <form id="ordersForm">
            <div style="margin-bottom: 10px;">
                <label for="page">Page:</label>
                <input type="number" id="page" value="1" min="1" style="width: 60px;">
                <label for="limit">Per Page (max 10):</label>
                <input type="number" id="limit" value="10" min="1" max="10" style="width: 60px;">
            </div>
            <button type="submit">Fetch Orders</button>
        </form>
        <div id="ordersResult" class="hidden"></div>
    </div>
    
    <div class="panel">
        <h2>API Configuration</h2>
        <table>
            <tr>
                <th>API Key</th>
                <td><?php echo get_option('wpwps_printify_api_key') ? '<span class="success">Set</span>' : '<span class="error">Not set</span>'; ?></td>
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
    
    <script>
        const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const apiKey = '<?php echo get_option('wpwps_printify_api_key', ''); ?>';
        const shopId = '<?php echo get_option('wpwps_printify_shop_id', ''); ?>';
        const endpoint = '<?php echo get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1'); ?>';
        
        document.getElementById('ordersForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const page = document.getElementById('page').value;
            const limit = document.getElementById('limit').value;
            const resultDiv = document.getElementById('ordersResult');
            
            resultDiv.innerHTML = '<p>Fetching orders...</p>';
            resultDiv.classList.remove('hidden');
            
            // Make direct request to Printify API endpoint via our AJAX proxy
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'printify_sync',
                    action_type: 'debug_direct_orders',
                    nonce: '<?php echo wp_create_nonce('wpwps_nonce'); ?>',
                    page: page,
                    limit: limit
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
                    let html = '<p class="success">Successfully fetched orders!</p>';
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
        
        // Define ajaxurl if it's not already defined
        if (typeof ajaxurl === 'undefined') {
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        }
    </script>
</body>
</html>
<?php
exit;
