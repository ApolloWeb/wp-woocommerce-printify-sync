<?php
/**
 * Debug tool for Etsy/Printify order pricing issues
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

// Get imported Printify orders
global $wpdb;
$printify_orders = $wpdb->get_results(
    "SELECT p.ID as order_id, pm.meta_value as printify_id 
     FROM {$wpdb->posts} p 
     JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
     WHERE p.post_type = 'shop_order' 
     AND pm.meta_key = '_printify_id' 
     ORDER BY p.post_date DESC 
     LIMIT 20"
);

?><!DOCTYPE html>
<html>
<head>
    <title>Etsy/Printify Order Price Debug</title>
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
    <h1>Etsy/Printify Order Price Diagnostic Tool</h1>
    
    <div class="panel">
        <h2>Price Normalization Test</h2>
        <p>This tool helps diagnose issues with price formatting for Etsy orders imported from Printify.</p>
        <form id="priceTestForm">
            <div>
                <label>Test a price value:</label>
                <input type="text" id="testPrice" value="1022" style="width: 100px;">
                <button type="submit">Test Normalization</button>
            </div>
        </form>
        <div id="priceTestResult" style="margin-top: 15px;"></div>
    </div>
    
    <div class="panel">
        <h2>Recently Imported Etsy/Printify Orders</h2>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Printify ID</th>
                <th>Total</th>
                <th>Merchant Cost</th>
                <th>Profit</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($printify_orders as $order): 
                $wc_order = wc_get_order($order->order_id);
                if (!$wc_order) continue;
                
                $total = $wc_order->get_total();
                $merchant_cost = get_post_meta($order->order_id, '_printify_merchant_cost', true);
                $profit = get_post_meta($order->order_id, '_printify_profit', true);
            ?>
            <tr>
                <td><?php echo $order->order_id; ?></td>
                <td><?php echo $order->printify_id; ?></td>
                <td><?php echo wc_price($total); ?></td>
                <td><?php echo wc_price($merchant_cost); ?></td>
                <td><?php echo wc_price($profit); ?></td>
                <td>
                    <a href="<?php echo get_edit_post_link($order->order_id); ?>" target="_blank">View Order</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <script>
        document.getElementById('priceTestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const price = parseFloat(document.getElementById('testPrice').value);
            if (isNaN(price)) {
                document.getElementById('priceTestResult').innerHTML = `
                    <div class="error">Please enter a valid number</div>
                `;
                return;
            }
            
            // Test price normalization logic
            const normalizedPrice = price > 100 && Math.floor(price) === price ? price / 100 : price;
            
            document.getElementById('priceTestResult').innerHTML = `
                <h3>Results:</h3>
                <table>
                    <tr>
                        <th>Original Value</th>
                        <td>${price}</td>
                    </tr>
                    <tr>
                        <th>Normalized Value</th>
                        <td>${normalizedPrice.toFixed(2)}</td>
                    </tr>
                    <tr>
                        <th>Formatted</th>
                        <td>${'£' + normalizedPrice.toFixed(2)}</td>
                    </tr>
                </table>
                <p><em>Note: This uses the same normalization logic as the importer.</em></p>
            `;
        });
    </script>
</body>
</html>
