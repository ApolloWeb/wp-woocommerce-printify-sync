<?php
/**
 * Price Debugging Tool
 * 
 * This tool helps debug price formatting issues by showing the exact
 * data coming from the AJAX requests and how it's processed.
 */

if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Example price data
$example = [
    'price' => 1022,
    'shipping' => 349,
    'cost' => 500,
    'shipping_cost' => 150
];

// Normalize - divide by 100
$normalized = [
    'price' => $example['price'] / 100,
    'shipping' => $example['shipping'] / 100,
    'cost' => $example['cost'] / 100,
    'shipping_cost' => $example['shipping_cost'] / 100
];

// Calculate totals
$total = $normalized['price'] + $normalized['shipping'];
$cost = $normalized['cost'] + $normalized['shipping_cost'];
$profit = $total - $cost;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Price Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; padding: 20px; }
        h1, h2 { color: #333; }
        .card { background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        pre { background: #f0f0f0; padding: 10px; overflow: auto; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Price Debug Tool</h1>
    
    <div class="card">
        <h2>Current Price Processing Logic</h2>
        <p>The server receives prices in cents (e.g., 1022) and divides by 100 to get the correct display value (£10.22).</p>
        <p>The JavaScript should NOT divide again, only format with the currency symbol.</p>
    </div>
    
    <div class="card">
        <h2>Example Data Processing</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Raw Value (cents)</th>
                <th>Normalized (÷ 100)</th>
                <th>Formatted</th>
            </tr>
            <tr>
                <td>Product Price</td>
                <td><?php echo $example['price']; ?></td>
                <td><?php echo $normalized['price']; ?></td>
                <td>£<?php echo number_format($normalized['price'], 2); ?></td>
            </tr>
            <tr>
                <td>Shipping</td>
                <td><?php echo $example['shipping']; ?></td>
                <td><?php echo $normalized['shipping']; ?></td>
                <td>£<?php echo number_format($normalized['shipping'], 2); ?></td>
            </tr>
            <tr>
                <td>Total Amount</td>
                <td><?php echo $example['price'] + $example['shipping']; ?></td>
                <td><?php echo $total; ?></td>
                <td>£<?php echo number_format($total, 2); ?></td>
            </tr>
            <tr>
                <td>Merchant Cost</td>
                <td><?php echo $example['cost'] + $example['shipping_cost']; ?></td>
                <td><?php echo $cost; ?></td>
                <td>£<?php echo number_format($cost, 2); ?></td>
            </tr>
            <tr>
                <td>Profit</td>
                <td><?php echo ($example['price'] + $example['shipping']) - ($example['cost'] + $example['shipping_cost']); ?></td>
                <td><?php echo $profit; ?></td>
                <td>£<?php echo number_format($profit, 2); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="card">
        <h2>JavaScript Formatter Test</h2>
        <div id="js-test"></div>
        
        <script>
            // Test the JavaScript formatter
            const testValues = {
                price: <?php echo $normalized['price']; ?>,
                shipping: <?php echo $normalized['shipping']; ?>,
                total: <?php echo $total; ?>,
                cost: <?php echo $cost; ?>,
                profit: <?php echo $profit; ?>
            };
            
            // Simple formatter function
            function formatPrice(value) {
                const num = parseFloat(value);
                return '£' + num.toFixed(2);
            }
            
            let html = '<table>';
            html += '<tr><th>Item</th><th>Value</th><th>Formatted</th></tr>';
            html += `<tr><td>Product Price</td><td>${testValues.price}</td><td>${formatPrice(testValues.price)}</td></tr>`;
            html += `<tr><td>Shipping</td><td>${testValues.shipping}</td><td>${formatPrice(testValues.shipping)}</td></tr>`;
            html += `<tr><td>Total Amount</td><td>${testValues.total}</td><td>${formatPrice(testValues.total)}</td></tr>`;
            html += `<tr><td>Merchant Cost</td><td>${testValues.cost}</td><td>${formatPrice(testValues.cost)}</td></tr>`;
            html += `<tr><td>Profit</td><td>${testValues.profit}</td><td>${formatPrice(testValues.profit)}</td></tr>`;
            html += '</table>';
            
            document.getElementById('js-test').innerHTML = html;
        </script>
    </div>
</body>
</html>
