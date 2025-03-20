<?php
/**
 * AJAX Debug Tool
 * 
 * This file helps identify issues with AJAX handlers
 */

// Make sure only administrators can run this script
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once dirname(dirname(dirname(__FILE__))) . '/wp-load.php';
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
    <title>AJAX Debug Tool</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
        pre { background: #f5f5f5; padding: 15px; overflow: auto; border-radius: 3px; }
        .success { color: green; }
        .error { color: red; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 3px; }
        .hidden { display: none; }
        button { padding: 8px 16px; background: #0073aa; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #005177; }
    </style>
</head>
<body>
    <h1>AJAX Debug Tool</h1>
    
    <div class="panel">
        <h2>AJAX Configuration</h2>
        <ul>
            <li><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></li>
            <li><strong>Nonce:</strong> <?php echo wp_create_nonce('wpwps_nonce'); ?></li>
            <li><strong>Plugin Version:</strong> <?php echo defined('WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION') ? WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION : 'Unknown'; ?></li>
        </ul>
        
        <h3>Registered AJAX Hooks</h3>
        <pre><?php
            global $wp_filter;
            $ajax_hooks = array_filter(array_keys($wp_filter), function($hook) {
                return strpos($hook, 'wp_ajax_') === 0;
            });
            sort($ajax_hooks);
            foreach ($ajax_hooks as $hook) {
                if ($hook === 'wp_ajax_printify_sync') {
                    echo "✓ ";
                }
                echo htmlspecialchars($hook) . "\n";
            }
        ?></pre>
    </div>
    
    <div class="panel">
        <h2>Test AJAX Request</h2>
        <button id="testAjax">Test Fetch Products AJAX</button>
        <div id="ajaxResult" class="hidden"></div>
    </div>
    
    <script>
        document.getElementById('testAjax').addEventListener('click', function() {
            const resultDiv = document.getElementById('ajaxResult');
            resultDiv.innerHTML = '<p>Sending request...</p>';
            resultDiv.classList.remove('hidden');
            
            const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
            const nonce = '<?php echo wp_create_nonce('wpwps_nonce'); ?>';
            
            fetch(ajaxUrl, {
                method: 'GET',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: new URLSearchParams({
                    action: 'printify_sync',
                    action_type: 'fetch_printify_products',
                    nonce: nonce,
                    refresh_cache: true
                })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    resultDiv.innerHTML = '<h3>Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    
                    if (data.success) {
                        resultDiv.innerHTML += '<p class="success">Success!</p>';
                    } else {
                        resultDiv.innerHTML += '<p class="error">Error: ' + (data.data?.message || 'Unknown error') + '</p>';
                    }
                } catch (e) {
                    resultDiv.innerHTML = '<h3>Invalid JSON Response:</h3><pre>' + text + '</pre>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">Network Error: ' + error.message + '</p>';
            });
        });
    </script>
</body>
</html>
