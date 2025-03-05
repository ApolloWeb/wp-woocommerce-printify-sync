<?php
/**
 * Currency Switcher Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$currencies = \ApolloWeb\WPWooCommercePrintifySync\Currency\CurrencyConverter::get_instance()->get_supported_currencies();
$current_currency = \ApolloWeb\WPWooCommercePrintifySync\Currency\CurrencyConverter::get_instance()->get_current_currency();
?>

<div class="printify-currency-switcher">
    <div class="currency-switcher-container">
        <span class="currency-switcher-label"><?php _e('Currency:', 'wp-woocommerce-printify-sync'); ?></span>
        <div class="currency-switcher-select">
            <select id="printify-currency-select">
                <?php foreach ($currencies as $code => $info): ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($code, $current_currency); ?>>
                        <?php echo esc_html($code . ' - ' . $info['symbol'] . ' ' . $info['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#printify-currency-select').on('change', function() {
            var currency = $(this).val();
            
            // Store selection in cookie
            document.cookie = "wpwprintifysync_currency=" + currency + "; path=/; max-age=86400";
            
            // Reload page
            location.reload();
        });
    });
</script>

<style type="text/css">
    .printify-currency-switcher {
        position: fixed;
        bottom: 15px;
        right: 15px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        padding: 8px 12px;
        z-index: 999;
        font-size: 12px;
    }
    
    .currency-switcher-container {
        display: flex;
        align-items: center;
    }
    
    .currency-switcher-label {
        margin-right: 8px;
        font-weight: bold;
    }
    
    .currency-switcher-select select {
        padding: 4px;
        border: 1px solid #ddd;
        border-radius: 3px;
        background-color: #f9f9f9;
        font-size: 12px;
    }
</style>