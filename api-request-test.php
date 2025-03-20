<?php
/**
 * Direct Printify API test tool
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

$api_key = get_option('wpwps_printify_api_key', '');
$shop_id = get_option('wpwps_printify_shop_id', '');
$endpoint = get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1');

if (empty($api_key)) {
    die('API key is not configured in settings');
}

if (empty($shop_id)) {
    die('Shop ID is not configured in settings');
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html>
<head>
    <title>Printify API Request Test</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 1200px; margin: 0 auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 15px; overflow: auto; border-radius: 3px; }
        .success { color: green; }
        .error { color: red; }
        section { margin-bottom: 30px; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        h1 { color: #333; }
        h2 { color: #444; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Printify API Request Test</h1>
    
    <section>
        <h2>Configuration</h2>
        <table>
            <tr>
                <th>API Key</th>
                <td><?php echo substr($api_key, 0, 5) . '...' . substr($api_key, -5); ?></td>
            </tr>
            <tr>
                <th>Shop ID</th>
                <td><?php echo $shop_id; ?></td>
            </tr>
            <tr>
                <th>API Endpoint</th>
                <td><?php echo $endpoint; ?></td>
            </tr>
        </table>
    </section>
    
    <section>
        <h2>Orders Request Test (Direct API Call)</h2>
        <?php
        $url = rtrim($endpoint, '/') . "/shops/{$shop_id}/orders.json?limit=10&page=1";
        
        $args = [
            'method' => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        echo "<p>Making GET request to: {$url}</p>";
        echo "<p>Authorization header: Bearer " . substr($api_key, 0, 5) . '...' . substr($api_key, -5) . "</p>";
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            echo "<p class='error'>Error: " . $response->get_error_message() . "</p>";
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            echo "<p>Response Code: {$status_code}</p>";
            
            if ($status_code >= 200 && $status_code < 300) {
                echo "<p class='success'>Request successful!</p>";
                $data = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
                } else {
                    echo "<p class='error'>JSON Parse Error: " . json_last_error_msg() . "</p>";
                    echo "<pre>" . htmlspecialchars($body) . "</pre>";
                }
            } else {
                echo "<p class='error'>Request failed with status code {$status_code}</p>";
                echo "<pre>" . htmlspecialchars($body) . "</pre>";
                
                // Try to decode error response
                $error_data = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($error_data['error'])) {
                    echo "<p class='error'>Error message: " . $error_data['error'] . "</p>";
                }
            }
        }
        ?>
    </section>
    
    <section>
        <h2>Printify API Documentation</h2>
        <p>According to the <a href="https://developers.printify.com/#orders" target="_blank">Printify API documentation</a>, the correct endpoint for fetching orders is:</p>
        <pre>GET /shops/{shop_id}/orders.json</pre>
        <p>Required headers:</p>
        <ul>
            <li>Authorization: Bearer YOUR_API_KEY</li>
            <li>Content-Type: application/json</li>
            <li>Accept: application/json</li>
        </ul>
    </section>
</body>
</html>
