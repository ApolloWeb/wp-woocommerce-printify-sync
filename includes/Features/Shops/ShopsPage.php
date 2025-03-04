<?php
/**
 * Shops Page
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Features\Shops
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Features\Shops;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * ShopsPage class for managing Printify shops
 */
class ShopsPage {
    /**
     * Render the shops page
     */
    public function render() {
        $template_path = PRINTIFY_SYNC_PATH . 'templates/admin/shops-page.php';
        
        if (file_exists($template_path)) {
            // Prepare variables for template
            $current_user = function_exists('printify_sync_get_current_user') ? 
                printify_sync_get_current_user() : 'No user';
                
            $current_datetime = function_exists('printify_sync_get_current_datetime') ?
                printify_sync_get_current_datetime() : gmdate('Y-m-d H:i:s');
                
            include $template_path;
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1><i class="fas fa-store"></i> Printify Shops</h1>';
        echo '<p>Manage your connected Printify shops.</p>';
        
        echo '<div class="shops-container">';
        
        echo '<h2>Your Shops</h2>';
        echo '<div class="shop-cards">';
        
        // Sample shop for display
        echo '<div class="shop-card">';
        echo '<div class="shop-header">';
        echo '<h3>My Printify Shop</h3>';
        echo '<span class="shop-status connected">Connected</span>';
        echo '</div>';
        echo '<div class="shop-details">';
        echo '<p><strong>Shop ID:</strong> 12345</p>';
        echo '<p><strong>Products:</strong> 25</p>';
        echo '<p><strong>Last Sync:</strong> ' . esc_html($current_datetime) . '</p>';
        echo '</div>';
        echo '<div class="shop-actions">';
        echo '<a href="#" class="button">View Products</a>';
        echo '<a href="#" class="button button-primary">Sync Products</a>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // .shop-cards
        
        echo '<h2>Connect New Shop</h2>';
        echo '<form method="post" class="connect-shop-form">';
        
        echo '<div class="form-row">';
        echo '<label for="api_token">Printify API Token:</label>';
        echo '<input type="text" id="api_token" name="api_token" required>';
        echo '<p class="description">Get your API token from the <a href="https://printify.com/app/account" target="_blank">Printify dashboard</a>.</p>';
        echo '</div>';
        
        echo '<div class="form-actions">';
        echo '<button type="submit" class="button button-primary">Connect Shop</button>';
        echo '</div>';
        
        echo '</form>';
        
        echo '</div>'; // .shops-container
        echo '</div>'; // .wrap
    }
}