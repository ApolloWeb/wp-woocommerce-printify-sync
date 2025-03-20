<?php
/**
 * Debug tool for price display issues
 */

// Ensure this is only accessible by admins
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Exit if not an admin
if (!current_user_can('manage_options')) {
    die('Unauthorized access');
}

?><!DOCTYPE html>
<html>
<head>
    <title>Printify Sync - Price Display Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 15px; overflow: auto; border-radius: 3px; }
        .success { color: green; }
        .error { color: red; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Price Display Debug Utility</h1>
    
    <div class="panel">
        <h2>Test Price Formatting</h2>
        <form id="priceTestForm">
            <div>
                <label>Enter raw price value:</label>
                <input type="text" id="rawPrice" value="1022" style="width: 100px;">
                <button type="submit">Format Price</button>
            </div>
        </form>
        <div id="priceResult" style="margin-top: 15px;"></div>
    </div>
    
    <div class="panel">
        <h2>Currency Settings</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <tr>
                <th>Currency</th>
                <td><?php echo get_option('wpwps_currency', 'GBP'); ?></td>
            </tr>
            <tr>
                <th>WooCommerce Currency</th>
                <td><?php echo function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'WooCommerce not active'; ?></td>
            </tr>
        </table>
    </div>
    
    <script>
        document.getElementById('priceTestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const rawValue = document.getElementById('rawPrice').value;
            const resultDiv = document.getElementById('priceResult');
            const numValue = parseFloat(rawValue);
            
            if (isNaN(numValue)) {
                resultDiv.innerHTML = '<span class="error">Invalid number entered</span>';
                return;
            }
            
            // Test different formatting approaches
            const results = `
                <h3>Formatting Results:</h3>
                <table>
                    <tr>
                        <th>Method</th>
                        <th>Result</th>
                    </tr>
                    <tr>
                        <td>As is</td>
                        <td>£${numValue.toFixed(2)}</td>
                    </tr>
                    <tr>
                        <td>Divided by 100</td>
                        <td>£${(numValue/100).toFixed(2)}</td>
                    </tr>
                    <tr>
                        <td>Smart detection (large whole number)</td>
                        <td>£${(numValue > 100 && numValue % 1 === 0 ? numValue/100 : numValue).toFixed(2)}</td>
                    </tr>
                </table>
                <p>Raw value properties:</p>
                <ul>
                    <li>Has decimal: ${String(numValue).includes('.')}</li>
                    <li>Is whole number: ${numValue % 1 === 0}</li>
                    <li>Greater than 100: ${numValue > 100}</li>
                </ul>
            `;
            
            resultDiv.innerHTML = results;
        });
    </script>
</body>
</html>
