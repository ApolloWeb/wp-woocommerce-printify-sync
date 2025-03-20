<?php
/**
 * Raw Price Debug Tool
 */

// Basic security
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Only admins can access
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Get test values
$test_raw = isset($_GET['raw']) ? (int)$_GET['raw'] : 2200;
$test_normalized = $test_raw / 100;

// Output directly for debugging
header('Content-Type: text/html');
?><!DOCTYPE html>
<html>
<head>
    <title>Raw Price Debug</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .card { background: #f8f9fa; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        pre { background: #f1f1f1; padding: 15px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Raw Price Debug Tool</h1>
    
    <div class="card">
        <h2>Test Different Values</h2>
        <form method="get">
            <label>Enter raw price value (in cents): 
                <input type="number" name="raw" value="<?php echo $test_raw; ?>">
            </label>
            <button type="submit">Test</button>
        </form>
    </div>
    
    <div class="card">
        <h2>Test Results</h2>
        <p>Raw value: <strong><?php echo $test_raw; ?></strong></p>
        <p>Normalized (÷ 100): <strong><?php echo $test_normalized; ?></strong></p>
        <p>Formatted: <strong><?php echo get_woocommerce_currency_symbol() . number_format($test_normalized, 2); ?></strong></p>
    </div>
    
    <div class="card">
        <h2>PHP Calculation Test</h2>
        <pre><?php
            // Test different scenarios
            $test_values = [
                'price' => 2200,
                'shipping' => 400,
                'cost' => 1050,
                'shipping_cost' => 400
            ];
            
            // Process the values
            $product_price = $test_values['price'] / 100;
            $shipping_price = $test_values['shipping'] / 100;
            $total = $product_price + $shipping_price;
            
            $item_cost = $test_values['cost'] / 100;
            $shipping_cost = $test_values['shipping_cost'] / 100;
            $merchant_cost = $item_cost + $shipping_cost;
            
            $profit = $total - $merchant_cost;
            
            echo "Raw values:\n";
            echo "Price: {$test_values['price']}, Shipping: {$test_values['shipping']}\n";
            echo "Cost: {$test_values['cost']}, Shipping Cost: {$test_values['shipping_cost']}\n\n";
            
            echo "Normalized values:\n";
            echo "Product Price: {$product_price}\n";
            echo "Shipping Price: {$shipping_price}\n";
            echo "Total Amount: {$total}\n";
            echo "Item Cost: {$item_cost}\n";
            echo "Shipping Cost: {$shipping_cost}\n";
            echo "Merchant Cost: {$merchant_cost}\n";
            echo "Profit: {$profit}\n";
        ?></pre>
    </div>
    
    <div class="card">
        <h2>JavaScript Test</h2>
        <div id="js-output"></div>
        
        <script>
            // Test the same calculations in JavaScript
            const testValues = {
                price: 2200,
                shipping: 400,
                cost: 1050,
                shipping_cost: 400
            };
            
            // Process values
            const productPrice = testValues.price / 100;
            const shippingPrice = testValues.shipping / 100;
            const total = productPrice + shippingPrice;
            
            const itemCost = testValues.cost / 100;
            const shippingCost = testValues.shipping_cost / 100; 
            const merchantCost = itemCost + shippingCost;
            
            const profit = total - merchantCost;
            
            // Format results
            let output = '<pre>';
            output += 'Raw values:\n';
            output += `Price: ${testValues.price}, Shipping: ${testValues.shipping}\n`;
            output += `Cost: ${testValues.cost}, Shipping Cost: ${testValues.shipping_cost}\n\n`;
            
            output += 'Normalized values:\n';
            output += `Product Price: ${productPrice}\n`;
            output += `Shipping Price: ${shippingPrice}\n`;
            output += `Total Amount: ${total}\n`;
            output += `Item Cost: ${itemCost}\n`;
            output += `Shipping Cost: ${shippingCost}\n`;
            output += `Merchant Cost: ${merchantCost}\n`;
            output += `Profit: ${profit}\n`;
            output += '</pre>';
            
            document.getElementById('js-output').innerHTML = output;
        </script>
    </div>
</body>
</html>
