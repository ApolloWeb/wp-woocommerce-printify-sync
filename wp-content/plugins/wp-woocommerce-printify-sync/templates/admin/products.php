<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-wrapper">
    <h1>Printify Products</h1>
    
    <div class="wpwps-timestamp">
        <i class="material-icons">access_time</i>
        <?php echo esc_html($this->currentTime); ?>
    </div>

    <!-- Product List Table -->
    <?php
    $productList = new \ApolloWeb\WPWooCommercePrintifySync\Admin\ProductList();
    $productList->prepare_items();
    $productList->display();
    ?>
</div>