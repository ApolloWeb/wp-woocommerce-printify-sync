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
        echo '<input type="
#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added:         echo '<input type="
#
#
# Commit Hash 16c804f
#
