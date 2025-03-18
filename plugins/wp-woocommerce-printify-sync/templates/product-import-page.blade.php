<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <title><?php _e('Product Import', 'wp-woocommerce-printify-sync'); ?></title>
    <link rel="stylesheet" href="<?php echo plugins_url('assets/css/product-import.css', dirname(__FILE__, 2)); ?>">
</head>
<body>
    <div class="container">
        <h1><?php _e('Product Import', 'wp-woocommerce-printify-sync'); ?></h1>
        <button id="retrieve-products" class="btn btn-primary"><?php _e('Retrieve Products', 'wp-woocommerce-printify-sync'); ?></button>
        <div id="import-progress" class="progress">
            <div id="progress-bar" class="progress-bar" role="progressbar" style="width:0%;"></div>
        </div>
        <div id="products-list">
            <!-- Retrieved products will be placed here -->
        </div>
        <button id="import-products" class="btn btn-success" style="display:none;"><?php _e('Import to WooCommerce', 'wp-woocommerce-printify-sync'); ?></button>
    </div>
    <script src="<?php echo plugins_url('assets/js/product-import.js', dirname(__FILE__, 2)); ?>"></script>
</body>
</html>
