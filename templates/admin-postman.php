<div class="wrap">
    <h1><?php _e('Postman Collection', 'wp-woocommerce-printify-sync'); ?></h1>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-file-download"></i> <?php _e('Download Postman Collection', 'wp-woocommerce-printify-sync'); ?>
                    </div>
                    <div class="card-body">
                        <p><?php _e('Download the Postman collection to test the Printify and WooCommerce APIs.', 'wp-woocommerce-printify-sync'); ?></p>
                        <a href="<?php echo WP_WOOCOMMERCE_PRINTIFY_SYNC_PLUGIN_URL . 'postman/wp-woocommerce-printify-sync.postman_collection.json'; ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i> <?php _e('Download Collection', 'wp-woocommerce-printify-sync'); ?>
                        </a>
                        <div class="mt-3">
                            <h5><?php _e('Instructions:', 'wp-woocommerce-printify-sync'); ?></h5>
                            <ul>
                                <li><?php _e('1. Download the Postman collection.', 'wp-woocommerce-printify-sync'); ?></li>
                                <li><?php _e('2. Import the collection into Postman.', 'wp-woocommerce-printify-sync'); ?></li>
                                <li><?php _e('3. Set the environment variables for Printify API key, WooCommerce API key, and WooCommerce store URL.', 'wp-woocommerce-printify-sync'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>