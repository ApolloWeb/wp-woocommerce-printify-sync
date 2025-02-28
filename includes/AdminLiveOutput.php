/**
 * AdminLiveOutput class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Register an admin menu page.
add_action('admin_menu', function() {
    add_menu_page(
        'Printify Live Output',
        'Printify Live',
        'manage_options',
        'printify-live-output',
        'AdminLiveOutput::renderPrintifyLiveOutput',
        'dashicons-products',
        6
    );
});

class AdminLiveOutput
{
    public static function renderPrintifyLiveOutput(): void {
        // Instantiate the PrintifyAPI (replace with live integration if available).
        $api = new PrintifyAPI();

        // Fetch products from the live API. We're simulating one product for demonstration.
        $products = $api->getProducts(1);

        ?>
        <div class="wrap">
            <h1>Live Product Data from Printify</h1>
            <?php if (!empty($products)) : 
                $product = $products[0]; ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Name</th>
                            <th>Tags</th>
                            <th>Categories</th>
                            <th>Price (First Variant)</th>
                            <th>Image</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo esc_html($product['id']); ?></td>
                            <td><?php echo esc_html($product['name']); ?></td>
                            <td><?php echo esc_html(implode(', ', $product['tags'] ?? [])); ?></td>
                            <td>
                                <?php 
                                    if (!empty($product['categories'])) {
                                        $cats = array_map(function($cat) { 
                                            return esc_html($cat['name']); 
                                        }, $product['categories']);
                                        echo esc_html(implode(', ', $cats));
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($product['variants'])) {
                                        $variant = $product['variants'][0];
                                        echo esc_html($variant['price'] ?? '');
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($product['images'])) {
                                        $imgUrl = esc_url($product['images'][0]);
                                        echo "<img src='{$imgUrl}' width='100' alt='Product Image'/>";
                                    }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No products found.</p>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!class_exists('PrintifyAPI')) {
    class PrintifyAPI {
        public function getProducts(int $page): array {
            // Only return data for the first page.
            if ($page > 1) {
                return [];
            }
            return [
                [
                    'id'         => 'Live-001',
                    'name'       => 'Live Test Product',
                    'tags'       => ['live', 'test'],
                    'categories' => [['name' => 'Live Category']],
                    'variants'   => [
                        ['sku' => 'Live-001-A', 'price' => '29.99', 'attributes' => ['size' => 'L']],
                    ],
                    'images'     => [
                        'https://via.placeholder.com/150',
                        'https://via.placeholder.com/100',
                    ],
                ],
            ];
        }
    }
}
