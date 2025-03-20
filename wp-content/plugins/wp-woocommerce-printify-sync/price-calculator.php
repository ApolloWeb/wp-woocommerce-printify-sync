<?php
/**
 * Price Calculator Tool
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

// Handle form submission
$example_price = 2200;
$example_shipping = 400;
$example_cost = 1050;
$example_shipping_cost = 400;

if (isset($_POST['calculate'])) {
    $example_price = floatval($_POST['price']);
    $example_shipping = floatval($_POST['shipping']);
    $example_cost = floatval($_POST['cost']);
    $example_shipping_cost = floatval($_POST['shipping_cost']);
}

// Calculate values
$total_price = $example_price / 100;
$total_shipping = $example_shipping / 100;
$total_amount = $total_price + $total_shipping;

$item_cost = $example_cost / 100;
$item_shipping_cost = $example_shipping_cost / 100;
$merchant_cost = $item_cost + $item_shipping_cost;

$profit = $total_amount - $merchant_cost;

// Format for display
$currency = '£';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Printify Price Calculator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; }
        .calculator { border: 1px solid #ccc; padding: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 150px; }
        input[type="number"] { width: 100px; }
        .result { margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        .price { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Printify Price Calculator</h1>
    <p>This tool helps you understand how the prices are calculated for Printify orders.</p>
    
    <div class="calculator">
        <form method="post">
            <div class="form-group">
                <label for="price">Product Price (cents):</label>
                <input type="number" id="price" name="price" value="<?php echo $example_price; ?>">
            </div>
            <div class="form-group">
                <label for="shipping">Shipping (cents):</label>
                <input type="number" id="shipping" name="shipping" value="<?php echo $example_shipping; ?>">
            </div>
            <div class="form-group">
                <label for="cost">Cost Price (cents):</label>
                <input type="number" id="cost" name="cost" value="<?php echo $example_cost; ?>">
            </div>
            <div class="form-group">
                <label for="shipping_cost">Shipping Cost (cents):</label>
                <input type="number" id="shipping_cost" name="shipping_cost" value="<?php echo $example_shipping_cost; ?>">
            </div>
            <button type="submit" name="calculate">Calculate</button>
        </form>
    </div>
    
    <div class="result">
        <h2>Calculation Results</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Raw Value (cents)</th>
                <th>Formatted Value</th>
                <th>Calculation</th>
            </tr>
            <tr>
                <td>Product Price</td>
                <td><?php echo $example_price; ?></td>
                <td class="price"><?php echo $currency . number_format($total_price, 2); ?></td>
                <td><?php echo $example_price; ?> / 100</td>
            </tr>
            <tr>
                <td>Shipping</td>
                <td><?php echo $example_shipping; ?></td>
                <td class="price"><?php echo $currency . number_format($total_shipping, 2); ?></td>
                <td><?php echo $example_shipping; ?> / 100</td>
            </tr>
            <tr>
                <td>Total Amount</td>
                <td><?php echo $example_price + $example_shipping; ?></td>
                <td class="price"><?php echo $currency . number_format($total_amount, 2); ?></td>
                <td><?php echo $currency . number_format($total_price, 2); ?> + <?php echo $currency . number_format($total_shipping, 2); ?></td>
            </tr>
            <tr>
                <td>Cost Price</td>
                <td><?php echo $example_cost; ?></td>
                <td class="price"><?php echo $currency . number_format($item_cost, 2); ?></td>
                <td><?php echo $example_cost; ?> / 100</td>
            </tr>
            <tr>
                <td>Shipping Cost</td>
                <td><?php echo $example_shipping_cost; ?></td>
                <td class="price"><?php echo $currency . number_format($item_shipping_cost, 2); ?></td>
                <td><?php echo $example_shipping_cost; ?> / 100</td>
            </tr>
            <tr>
                <td>Total Cost</td>
                <td><?php echo $example_cost + $example_shipping_cost; ?></td>
                <td class="price"><?php echo $currency . number_format($merchant_cost, 2); ?></td>
                <td><?php echo $currency . number_format($item_cost, 2); ?> + <?php echo $currency . number_format($item_shipping_cost, 2); ?></td>
            </tr>
            <tr>
                <td>Profit</td>
                <td><?php echo ($example_price + $example_shipping) - ($example_cost + $example_shipping_cost); ?></td>
                <td class="price"><?php echo $currency . number_format($profit, 2); ?></td>
                <td><?php echo $currency . number_format($total_amount, 2); ?> - <?php echo $currency . number_format($merchant_cost, 2); ?></td>
            </tr>
        </table>
    </div>
</body>
</html>
