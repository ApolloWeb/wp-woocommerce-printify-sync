<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class OrdersPage
{
    public static function render()
    {
        // Render the orders page
        echo '<div class="wrap">';
        echo '<h1>WP WooCommerce Printify Sync Orders</h1>';
        echo '<button id="sync-orders" class="button button-primary">Sync Orders</button>';
        echo '</div>';
    }
}
