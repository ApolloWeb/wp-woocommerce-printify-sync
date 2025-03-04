<?php
/**
 * Exchange Rates Page
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Features\ExchangeRates
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Features\ExchangeRates;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * ExchangeRatesPage class
 */
class ExchangeRatesPage {
    /**
     * Render the exchange rates page
     */
    public function render() {
        $template_path = PRINTIFY_SYNC_PATH . 'templates/admin/exchange-rates-page.php';
        
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
        echo '<h1><i class="fas fa-exchange-alt"></i> Exchange Rates</h1>';
        echo '<p>Manage currency exchange rates for product pricing.</p>';
        
        echo '<div class="exchange-rates-container">';
        
        echo '<h2>Current Exchange Rates</h2>';
        echo '<p>Last updated: ' . esc_html($current_datetime) . '</p>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Currency</th>';
        echo '<th>Rate (USD)</th>';
        echo '<th>Custom Rate</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // Sample currency data
        $currencies = [
            'EUR' => 0.85,
            'GBP' => 0.75,
            'CAD' => 1.25,
            'AUD' => 1.35
        ];
        
        foreach ($currencies as $currency => $rate) {
            echo '<tr>';
            echo '<td>' . esc_html($currency) . '</td>';
            echo '<td>' . esc_html($rate) . '</td>';
            echo '<td>';
            echo '<input type="number" step="0.01" min="0" name="custom_rate_' . esc_attr($currency) . '" value="' . esc_attr($rate) . '">';
            echo '</td>';
            echo '<td>';
            echo '<button class="button button-small update-rate" data-currency="' . esc_attr($currency) . '">Update</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        echo '<div class="exchange-actions">';
        echo '<button class="button button-primary refresh-rates">Refresh All Rates</button>';
        echo '</div>';
        
        echo '</div>'; // .exchange-rates-container
        echo '</div>'; // .wrap
    }
<<<<<<< HEAD
}
=======
}
#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: }
#
#
# Commit Hash 16c804f
#
>>>>>>> bc14d86262cd5ad94e1edb2b5c005569542963c4
