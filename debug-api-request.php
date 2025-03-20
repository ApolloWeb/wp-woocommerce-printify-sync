<?php
/**
 * Debug tool for making direct API requests to Printify
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

// Set up the request
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

// Make the direct request
echo "<h1>Making Direct GET Request to Printify API</h1>";
echo "<pre>URL: {$url}</pre>";
echo "<pre>Method: GET</pre>";
echo "<pre>Headers: " . json_encode($args['headers'], JSON_PRETTY_PRINT) . "</pre>";

// Perform the request
$response = wp_remote_request($url, $args);

// Display the result
if (is_wp_error($response)) {
    echo "<h2>Error</h2>";
    echo "<pre>" . $response->get_error_message() . "</pre>";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $headers = wp_remote_retrieve_headers($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "<h2>Response</h2>";
    echo "<p>Status Code: {$status_code}</p>";
    
    echo "<h3>Headers</h3>";
    echo "<pre>" . print_r($headers, true) . "</pre>";
    
    echo "<h3>Body</h3>";
    if (!empty($body)) {
        $json = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<pre>" . htmlspecialchars($body) . "</pre>";
        }
    } else {
        echo "<p>Empty response body</p>";
    }
}
