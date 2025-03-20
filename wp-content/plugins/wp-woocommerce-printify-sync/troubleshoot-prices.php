<?php
/**
 * Price Troubleshooting Tool for Printify Sync
 * This file helps diagnose issues with price formatting in the plugin
 */

// Basic security
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Only accessible to administrators
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Clear output buffer
@ob_end_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Printify Sync - Price Troubleshooting</title>
    <style>
        body { font-family: sans-serif; line-height: 1.5; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { color: #23282d; }
        pre { background: #f1f1f1; padding: 15px; border-radius: 3px; overflow: auto; }
        .card { border: 1px solid #ccd0d4; border-radius: 3px; padding: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e2e4e7; }
        th { background-color: #f9f9f9; }
        .label { display: inline-block; width: 120px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 3px; }
        .error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Printify Sync - Price Troubleshooting</h1>
    
    <div class="card">
        <h2>Environment Information</h2>
        <p><span class="label">WP Version:</span> <?php echo get_bloginfo('version'); ?></p>
        <p><span class="label">PHP Version:</span> <?php echo phpversion(); ?></p>
        <p><span class="label">WC Version:</span> <?php echo defined('WC_VERSION') ? WC_VERSION : 'Not Active'; ?></p>
        <p><span class="label">Currency:</span> <?php echo get_option('wpwps_currency', 'GBP'); ?></p>
    </div>
    
    <div class="card">
        <h2>Price Conversion Test</h2>
        <form method="post" action="">
            <p>
                <label for="test_price">Test Price Value:</label>
                <input type="text" id="test_price" name="test_price" value="<?php echo isset($_POST['test_price']) ? esc_attr($_POST['test_price']) : '1022'; ?>">
                <button type="submit" name="test_conversion">Test</button>
            </p>
        </form>
        
        <?php if (isset($_POST['test_conversion']) && isset($_POST['test_price'])): 
            $test_price = $_POST['test_price'];
            $float_value = (float) $test_price;
            $normalized = ($float_value > 100 && floor($float_value) == $float_value) ? $float_value / 100 : $float_value;
        ?>
            <div class="success">
                <h3>Conversion Results:</h3>
                <p><strong>Original Value:</strong> <?php echo esc_html($test_price); ?></p>
                <p><strong>Float Value:</strong> <?php echo esc_html($float_value); ?></p>
                <p><strong>Normalized Value:</strong> <?php echo esc_html($normalized); ?></p>
                <p><strong>Formatted:</strong> <?php echo get_woocommerce_currency_symbol() . number_format($normalized, 2); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>Recent Printify Orders</h2>
        <?php
        global $wpdb;
        $orders = $wpdb->get_results(
            "SELECT p.ID, pm.meta_value as printify_id
             FROM {$wpdb->posts} p
             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'shop_order'
             AND pm.meta_key = '_printify_id'
             ORDER BY p.post_date DESC 
             LIMIT 5"
        );
        
        if ($orders): ?>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Printify ID</th>
                    <th>Total</th>
                    <th>Merchant Cost</th>
                    <th>Profit</th>
                    <th>Raw Data</th>
                </tr>
                <?php foreach ($orders as $order):
                    $wc_order = wc_get_order($order->ID);
                    if (!$wc_order) continue;
                    
                    $total = $wc_order->get_total();
                    $merchant_cost = get_post_meta($order->ID, '_printify_merchant_cost', true);
                    $profit = get_post_meta($order->ID, '_printify_profit', true);
                ?>
                <tr>
                    <td><?php echo $order->ID; ?></td>
                    <td><?php echo $order->printify_id; ?></td>
                    <td><?php echo wc_price($total); ?></td>
                    <td><?php echo wc_price($merchant_cost); ?></td>
                    <td><?php echo wc_price($profit); ?></td>
                    <td>
                        <details>
                            <summary>View Data</summary>
                            <pre><?php 
                                echo "Order Total: " . print_r($total, true) . "\n";
                                echo "Merchant Cost: " . print_r($merchant_cost, true) . "\n";
                                echo "Profit: " . print_r($profit, true) . "\n";
                            ?></pre>
                        </details>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="error">No Printify orders found.</p>
        <?php endif; ?>
    </div>
    
    <script>
        // Simple test script for price formatting
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Price formatting test:');
            
            // Test values
            const testValues = [
                1022, 
                10.22, 
                2200, 
                22.00, 
                0,
                "1022",
                "10.22"
            ];
            
            // Test each value
            testValues.forEach(value => {
                // Raw value
                const numValue = parseFloat(value);
                
                // Simple formatter
                const simple = '£' + numValue.toFixed(2);
                
                // Smart formatter
                const needsDivision = numValue > 100 && !String(numValue).includes('.') && numValue % 1 === 0;
                const normalizedValue = needsDivision ? numValue / 100 : numValue;
                const smart = '£' + normalizedValue.toFixed(2);
                
                console.log(`Value: ${value} (${typeof value})`);
                console.log(`- Simple: ${simple}`);
                console.log(`- Smart: ${smart}`);
                console.log('---');
            });
        });
    </script>
</body>
</html>
