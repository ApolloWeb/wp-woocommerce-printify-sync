<div class="wrap">
    <h1>Printify Sync Products</h1>
    
    <?php
    $last_import_time = get_option('wpwcs_last_import_time', 'Never');
    $last_import_count = get_option('wpwcs_last_import_count', 0);

    echo '<p><strong>Last Import Time:</strong> ' . esc_html($last_import_time) . '</p>';
    echo '<p><strong>Number of Products Imported:</strong> ' . esc_html($last_import_count) . '</p>';
    ?>

    <h2>Products</h2>
    <button id="get-products-btn" class="btn waves-effect waves-light">Get Products</button>
    <div id="products-info" style="display: none;">
        <p><strong>Published Products:</strong> <span id="published-count"></span></p>
        <p><strong>Unpublished Products:</strong> <span id="unpublished-count"></span></p>
        <button id="import-products-btn" class="btn waves-effect waves-light">Import All Products To WooCommerce</button>
    </div>

    <h2>Product Categories</h2>
    <div id="categories-info">
        <!-- Categories info will be loaded here -->
    </div>

    <h2>Product Search</h2>
    <div class="input-field">
        <input type="text" id="product-search">
        <label for="product-search">Search for a product...</label>
    </div>
    <div id="product-search-results"></div>
</div>