<?php
/**
 * PostmanPage Class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Integration
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Integration;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class PostmanPage
 */
class PostmanPage {
    /**
     * Render the Postman API interface
     */
    public function render() {
        $template_path = PRINTIFY_SYNC_PATH . 'templates/admin/postman-page.php';
        
        if (file_exists($template_path)) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            // Get the API instance if needed
            $api = null;
            if (class_exists('ApolloWeb\WPWooCommercePrintifySync\API\PostmanApi')) {
                $api = new \ApolloWeb\WPWooCommercePrintifySync\API\PostmanApi();
            }
            
            include $template_path;
            return;
        }
        
        // Fallback if template is missing
        echo '<div class="wrap">';
        echo '<h1><i class="fas fa-paper-plane"></i> API Postman</h1>';
        echo '<p>This tool allows you to test API requests to the Printify API or other endpoints.</p>';
        
        // Simple form for testing API requests
        echo '<form id="postman-form" method="post">';
        echo '<div class="postman-container">';
        
        // Method selection
        echo '<div class="postman-row">';
        echo '<label for="request-method">Method:</label>';
        echo '<select id="request-method" name="method">';
        echo '<option value="GET">GET</option>';
        echo '<option value="POST">POST</option>';
        echo '<option value="PUT">PUT</option>';
        echo '<option value="DELETE">DELETE</option>';
        echo '</select>';
        echo '</div>';
        
        // Endpoint URL
        echo '<div class="postman-row">';
        echo '<label for="request-url">URL:</label>';
        echo '<input type="text" id="request-url" name="url" placeholder="https://api.printify.com/v1/" value="https://api.printify.com/v1/" />';
        echo '</div>';
        
        // Headers
        echo '<div class="postman-row">';
        echo '<label>Headers:</label>';
        echo '<div class="headers-container">';
        echo '<div class="header-row">';
        echo '<input type="text" name="header_keys[]" placeholder="Authorization" value="Authorization" />';
        echo '<input type="text" name="header_values[]" placeholder="Bearer YOUR_TOKEN" />';
        echo '</div>';
        echo '<button type="button" class="add-header button button-secondary">+ Add Header</button>';
        echo '</div>';
        echo '</div>';
        
        // Request body
        echo '<div class="postman-row">';
        echo '<label for="request-body">Body:</label>';
        echo '<textarea id="request-body" name="body" placeholder=\'{"key": "value"}\'></textarea>';
        echo '</div>';
        
        // Submit button
        echo '<div class="postman-row">';
        echo '<button type="submit" class="button button-primary">Send Request</button>';
        echo '</div>';
        
        // Response display area
        echo '<div class="postman-row">';
        echo '<label>Response:</label>';
        echo '<pre id="response-display" class="response"></pre>';
        echo '</div>';
        
        echo '</div>'; // .postman-container
        echo '</form>';
        
        echo '</div>'; // .wrap
    }
}