<?php
/**
 * Price Calculation Test Tool
 */

// Ensure this is only accessible by admins
if (!defined('ABSPATH')) {
    define('WP_USE_THEMES', false);
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
}

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Example data from the docs
$example = [
    'total_price' => 2200,
    'total_shipping' => 400,
    'line_items' => [
        [
            'quantity' => 1,
            'cost' => 1050,
            'shipping_cost' => 400
        ]
    ]
];

// Process the example data
$total_price = $example['total_price'] / 100;
$total_shipping = $example['total_shipping'] / 100;
$total_amount = $total_price + $total_shipping;

$merchant_cost = 0;
foreach ($example['line_items'] as $item) {
    $item_cost = $item['cost'] / 100;
    $shipping_cost = $item['shipping_cost'] / 100;
    $quantity = $item['quantity'];
    
    $merchant_cost += ($item_cost * $quantity) + $shipping_cost;
}

$profit = $total_amount - $merchant_cost;

?><!DOCTYPE html>
<html>
<head>
    <title>Price Calculation Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { text-align: left; padding: 8px; border: 1px solid #ddd; }
        th { background-color: #f8f8f8; }
        .value { font-weight: bold; }
        .formula { color: #777; font-family: monospace; }
    </style>
</head>
<body>
    <h1>Price Calculation Test</h1>
    
    <h2>Raw API Data</h2>
    <pre><?php echo json_encode($example, JSON_PRETTY_PRINT); ?></pre>
    
    <h2>Calculation Results</h2>
    <table>
        <tr>
            <th>Item</th>
            <th>Raw Value (cents)</th>
            <th>Converted (÷ 100)</th>
            <th>Formula</th>
        </tr>
        <tr>
            <td>Product Price</td>
            <td><?php echo $example['total_price']; ?></td>
            <td class="value">£<?php echo number_format($total_price, 2); ?></td>
            <td class="formula"><?php echo $example['total_price']; ?> ÷ 100</td>
        </tr>
        <tr>
            <td>Shipping</td>
            <td><?php echo $example['total_shipping']; ?></td>
            <td class="value">£<?php echo number_format($total_shipping, 2); ?></td>
            <td class="formula"><?php echo $example['total_shipping']; ?> ÷ 100</td>
        </tr>
        <tr>
            <td>Total Amount</td>
            <td><?php echo $example['total_price'] + $example['total_shipping']; ?></td>
            <td class="value">£<?php echo number_format($total_amount, 2); ?></td>
            <td class="formula"><?php echo number_format($total_price, 2); ?> + <?php echo number_format($total_shipping, 2); ?></td>
        </tr>
        <tr>
            <td>Item Cost</td>
            <td><?php echo $example['line_items'][0]['cost']; ?></td>
            <td class="value">£<?php echo number_format($example['line_items'][0]['cost'] / 100, 2); ?></td>
            <td class="formula"><?php echo $example['line_items'][0]['cost']; ?> ÷ 100</td>
        </tr>
        <tr>
            <td>Shipping Cost</td>
            <td><?php echo $example['line_items'][0]['shipping_cost']; ?></td>
            <td class="value">£<?php echo number_format($example['line_items'][0]['shipping_cost'] / 100, 2); ?></td>
            <td class="formula"><?php echo $example['line_items'][0]['shipping_cost']; ?> ÷ 100</td>
        </tr>
        <tr>
            <td>Merchant Cost</td>
            <td><?php echo $example['line_items'][0]['cost'] + $example['line_items'][0]['shipping_cost']; ?></td>
            <td class="value">£<?php echo number_format($merchant_cost, 2); ?></td>
            <td class="formula">(<?php echo number_format($example['line_items'][0]['cost'] / 100, 2); ?> × <?php echo $example['line_items'][0]['quantity']; ?>) + <?php echo number_format($example['line_items'][0]['shipping_cost'] / 100, 2); ?></td>
        </tr>
        <tr>
            <td>Profit</td>
            <td><?php echo ($example['total_price'] + $example['total_shipping']) - ($example['line_items'][0]['cost'] + $example['line_items'][0]['shipping_cost']); ?></td>
            <td class="value">£<?php echo number_format($profit, 2); ?></td>
            <td class="formula"><?php echo number_format($total_amount, 2); ?> - <?php echo number_format($merchant_cost, 2); ?></td>
        </tr>
    </table>
    
    <h2>Expected Display</h2>
    <div style="border: 1px solid #ddd; padding: 15px; margin-top: 20px;">
        <p style="font-weight: bold;">£<?php echo number_format($total_amount, 2); ?></p>
        <p>Product: £<?php echo number_format($total_price, 2); ?></p>
        <p>Shipping: £<?php echo number_format($total_shipping, 2); ?></p>
        <p style="font-weight: bold;">£<?php echo number_format($merchant_cost, 2); ?></p>
        <p>Profit: £<?php echo number_format($profit, 2); ?></p>
    </div>
</body>
</html>
