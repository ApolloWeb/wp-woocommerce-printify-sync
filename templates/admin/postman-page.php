<?php
/**
 * Postman API Test Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap printify-postman-page">
    <h1><i class="fas fa-paper-plane"></i> API Postman</h1>
    <p>Test API requests to Printify or other endpoints.</p>

    <div class="postman-info-box">
        <p><strong>Current User:</strong> <?php echo esc_html($current_user); ?></p>
        <p><strong>Current Time (UTC):</strong> <?php echo esc_html($current_datetime); ?></p>
    </div>
    
    <div class="postman-container">
        <form id="postman-form" method="post">
            <div class="postman-row method-row">
                <label for="request-method">Method:</label>
                <select id="request-method" name="method">
                    <option value="GET">GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="DELETE">DELETE</option>
                </select>
            </div>
            
            <div class="postman-row url-row">
                <label for="request-url">URL:</label>
                <input type="text" id="request-url" name="url" placeholder="https://api.printify.com/v1/" value="https://api.printify.com/v1/" />
            </div>
            
            <div class="postman-row headers-row">
                <label>Headers:</label>
                <div class="headers-container">
                    <div class="header-row">
                        <input type="text" name="header_keys[]" value="Authorization" placeholder="Header Name" />
                        <input type="text" name="header_values[]" placeholder="Bearer YOUR_API_TOKEN" />
                    </div>
                    <div class="header-row">
                        <input type="text" name="header_keys[]" value="Content-Type" placeholder="Header Name" />
                        <input type="text" name="header_values[]" value="application/json" placeholder="Header Value" />
                    </div>
                </div>
                <button type="button" class="add-header button button-secondary">+ Add Header</button>
            </div>
            
            <div class="postman-row body-row">
                <label for="request-body">Request Body:</label>
                <textarea id="request-body" name="body" placeholder='{"key": "value"}'></textarea>
            </div>
            
            <div class="postman-row actions-row">
                <button type="submit" class="button button-primary">Send Request</button>
                <button type="button" class="button button-secondary" id="clear-form">Clear Form</button>
            </div>
            
            <div class="postman-row response-row">
                <label for="response-display">Response:</label>
                <div class="response-container">
                    <div class="response-headers">
                        <h3>Response Headers</h3>
                        <pre id="response-headers-display"></pre>
                    </div>
                    <div class="response-body">
                        <h3>Response Body</h3>
                        <pre id="response-display"></pre>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="postman-documentation">
        <h3>API Documentation</h3>
        <p>For more information about the Printify API, please refer to the <a href="https://developers.printify.com" target="_blank">official documentation</a>.</p>
    </div>
</div>