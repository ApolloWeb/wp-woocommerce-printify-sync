<div class="wrap">
    <h1>Currency Management</h1>

    <?php
    $last_update_time = get_option('wpwcs_last_currency_update_time', 'Never');

    echo '<p><strong>Last Update Time:</strong> ' . esc_html($last_update_time) . '</p>';
    ?>

    <h2>Current Conversion Rates</h2>
    <div class="row">
        <?php
        $currencies = get_woocommerce_currencies();
        $base_currency = 'GBP';
        foreach ($currencies as $code => $name) {
            if ($code == $base_currency) {
                continue;
            }
            $rate = get_option('wpwcs_currency_rate_' . $code, 'N/A');
            echo '<div class="col s12 m6 l4">';
            echo '<div class="card">';
            echo '<div class="card-content">';
            echo '<span class="card-title">' . esc_html($name) . ' (' . esc_html($code) . ')</span>';
            echo '<p><strong>Conversion Rate:</strong> ' . esc_html($rate) . '</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>

    <button id="update-currencies-btn" class="btn waves-effect waves-light">Update Currencies</button>
</div>