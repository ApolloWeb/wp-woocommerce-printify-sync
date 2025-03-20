<?php
/**
 * Direct API Test Tool for Printify
 * 
 * This script makes direct requests to the Printify API to help diagnose issues
 * without going through the plugin's abstraction layers.
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

// Get API credentials
$api_key = get_option('wpwps_printify_api_key', '');
$shop_id = get_option('wpwps_printify_shop_id', '');
$endpoint = get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1');

if (empty($api_key) || empty($shop_id)) {
    die('API key or shop ID not configured');
}

// Handle form submission
$test_endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'orders';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Determine the URL based on the selected endpoint
switch ($test_endpoint) {
    case 'products':
        $url = rtrim($endpoint, '/') . "/shops/{$shop_id}/products.json?limit={$limit}&page={$page}";
        break;
    case 'orders':
        // Ensure limit is no more than 10 for orders
        $order_limit = min(10, $limit);
        $url = rtrim($endpoint, '/') . "/shops/{$shop_id}/orders.json?limit={$order_limit}&page={$page}";
        break;
    case 'shops':
        $url = rtrim($endpoint, '/') . "/shops.json";
        break;
    default:
        $url = rtrim($endpoint, '/') . "/shops/{$shop_id}/{$test_endpoint}.json?limit={$limit}&page={$page}";
}

// Set up the request
$args = [
    'method' => 'GET',
    'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],
    'timeout' => 30,
];

// Make the request
$response = wp_remote_request($url, $args);

// Prepare the output
$result = [
    'url' => $url,
    'args' => $args,
    'response' => $response
];

// HTML output
?><!DOCTYPE html>
<html>
<head>
    <title>Printify API Test</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 1200px; margin: 0 auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 15px; overflow: auto; border-radius: 3px; max-height: 400px; }
        .success { color: green; }
        .error { color: red; }
        form { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 3px; }
        select, input, button { padding: 8px; margin-right: 10px; }
        h1, h2, h3 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Printify API Test</h1>
    
    <form method="get">
        <div>
            <label for="endpoint">API Endpoint:</label>
            <select name="endpoint" id="endpoint">
                <option value="products" <?php echo $test_endpoint === 'products' ? 'selected' : ''; ?>>Products</option>
                <option value="orders" <?php echo $test_endpoint === 'orders' ? 'selected' : ''; ?>>Orders</option>
                <option value="shops" <?php echo $test_endpoint === 'shops' ? 'selected' : ''; ?>>Shops</option>
            </select>
            
            <label for="page">Page:</label>
            <input type="number" name="page" id="page" value="<?php echo $page; ?>" min="1" style="width: 60px;">
            
            <label for="limit">Per Page:</label>
            <input type="number" name="limit" id="limit" value="<?php echo $limit; ?>" min="1" max="50" style="width: 60px;">
            
            <button type="submit">Test API</button>
        </div>
    </form>
    
    <h2>Request Details</h2>
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
            <th>URL</th>
            <td><?php echo $url; ?></td>
        </tr>
        <tr>
            <th>Method</th>
            <td>GET</td>
        </tr>
    </table>
    
    <h2>Response</h2>
    <?php if (is_wp_error($response)): ?>
        <div class="error">
            <h3>WP Error</h3>
            <p><?php echo $response->get_error_message(); ?></p>
        </div>
    <?php else: ?>
        <h3>Status Code: <?php echo wp_remote_retrieve_response_code($response); ?></h3>
        
        <?php
        $headers = wp_remote_retrieve_headers($response);
        $body = wp_remote_retrieve_body($response);
        $json_data = json_decode($body, true);
        $status_code = wp_remote_retrieve_response_code($response);
        ?>
        
        <h3>Headers</h3>
        <pre><?php print_r($headers); ?></pre>
        
        <h3>Body</h3>
        <?php if ($status_code >= 200 && $status_code < 300): ?>
            <div class="success">Request Successful</div>
            <?php if (json_last_error() === JSON_ERROR_NONE): ?>
                <pre><?php echo json_encode($json_data, JSON_PRETTY_PRINT); ?></pre>
                
                <h3>Structure Analysis</h3>
                <pre>
Response keys: <?php echo implode(', ', array_keys($json_data)); ?>

<?php if (isset($json_data['data']) && is_array($json_data['data'])): ?>
Data is an array with <?php echo count($json_data['data']); ?> items.
First item keys: <?php echo !empty($json_data['data']) ? implode(', ', array_keys($json_data['data'][0])) : 'None'; ?>
<?php else: ?>
No 'data' array found in response.
<?php endif; ?>

<?php if (isset($json_data['current_page'])): ?>
Pagination info:
<?php print_r($json_data['current_page']); ?>
<?php else: ?>
No pagination information found.
<?php endif; ?>
                </pre>
            <?php else: ?>
                <div class="error">JSON Parse Error: <?php echo json_last_error_msg(); ?></div>
                <pre><?php echo htmlspecialchars($body); ?></pre>
            <?php endif; ?>
        <?php else: ?>
            <div class="error">Request Failed: Status Code <?php echo $status_code; ?></div>
            <pre><?php echo htmlspecialchars($body); ?></pre>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
