<?php
/**
 * Troubleshooting tool for AJAX requests
 * Can be accessed at https://yoursite.com/wp-content/plugins/wp-woocommerce-printify-sync/troubleshoot-ajax.php
 * (Only works when WP_DEBUG is true)
 */

// Ensure WordPress is loaded
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
    <title>AJAX Troubleshooter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow: auto; }
        .result { margin-top: 15px; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        .error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1>AJAX Troubleshooter</h1>
        <div class="card mb-4">
            <div class="card-header">
                WordPress Configuration
            </div>
            <div class="card-body">
                <ul>
                    <li><strong>WP Version:</strong> <?php echo get_bloginfo('version'); ?></li>
                    <li><strong>Plugin Version:</strong> <?php echo defined('WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION') ? WP_WOOCOMMERCE_PRINTIFY_SYNC_VERSION : 'Unknown'; ?></li>
                    <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                    <li><strong>WP_DEBUG:</strong> <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled'; ?></li>
                    <li><strong>AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></li>
                    <li><strong>Printify API Key:</strong> <?php echo get_option('wpwps_printify_api_key') ? 'Set' : 'Not set'; ?></li>
                    <li><strong>Printify Shop ID:</strong> <?php echo get_option('wpwps_printify_shop_id') ?: 'Not set'; ?></li>
                </ul>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Test Orders Fetch
            </div>
            <div class="card-body">
                <button id="test-fetch-orders" class="btn btn-primary">Test Fetch Orders</button>
                <div id="orders-result" class="result mt-3" style="display:none;"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                AJAX Handler Debug
            </div>
            <div class="card-body">
                <p>Testing if the AJAX handler is properly registered:</p>
                <pre id="ajax-hooks"><?php
                    global $wp_filter;
                    $ajax_hooks = array_filter(array_keys($wp_filter), function($hook) {
                        return strpos($hook, 'wp_ajax_') === 0;
                    });
                    sort($ajax_hooks);
                    echo "AJAX Hooks:\n\n";
                    foreach ($ajax_hooks as $hook) {
                        if ($hook === 'wp_ajax_printify_sync') {
                            echo "✓ ";
                        }
                        echo $hook . "\n";
                    }
                    ?></pre>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#test-fetch-orders').on('click', function() {
            const button = $(this);
            button.prop('disabled', true).html('Testing...');
            
            $('#orders-result').html('Sending request...').show().removeClass('error success');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'printify_sync',
                    action_type: 'fetch_printify_orders',
                    nonce: '<?php echo wp_create_nonce('wpwps_nonce'); ?>',
                    page: 1,
                    per_page: 5,
                    refresh_cache: true
                },
                success: function(response) {
                    console.log('Response:', response);
                    
                    let resultHTML = '<h5>Test Result:</h5>';
                    resultHTML += '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                    
                    if (response.success) {
                        $('#orders-result').html(resultHTML).addClass('success').removeClass('error');
                    } else {
                        $('#orders-result').html(resultHTML).addClass('error').removeClass('success');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', { xhr, status, error });
                    
                    let resultHTML = '<h5>Error:</h5>';
                    resultHTML += '<p>' + error + '</p>';
                    resultHTML += '<pre>' + xhr.responseText + '</pre>';
                    
                    $('#orders-result').html(resultHTML).addClass('error').removeClass('success');
                },
                complete: function() {
                    button.prop('disabled', false).html('Test Fetch Orders');
                }
            });
        });
    });
    </script>
</body>
</html>
