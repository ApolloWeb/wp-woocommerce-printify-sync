<?php

use ApolloWeb\WPWooCommercePrintifySync\ShopPrintify;

?>
<div class="wrap">
    <h1>Shops</h1>
    <div id="shops-list" class="row">
        <?php
        $shops = ShopPrintify::get_shops();
        if (!empty($shops)) {
            foreach ($shops as $shop) {
                echo '<div class="col s12 m6 l4">';
                echo '<div class="shop-item card">';
                echo '<div class="card-content">';
                echo '<span class="card-title">' . esc_html($shop['name']) . '</span>';
                echo '<p><strong>Shop ID:</strong> ' . esc_html($shop['id']) . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p>No shops found.</p>';
        }
        ?>
    </div>
</div>