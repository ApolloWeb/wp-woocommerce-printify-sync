<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class ProductsPage
{
    public static function render()
    {
        // Render the products page
        echo '<div class="wrap">';
        echo '<h1>WP WooCommerce Printify Sync Products</h1>';
        echo '<button id="sync-products" class="button button-primary">Sync Products</button>';
        echo '</div>';
    }
}
