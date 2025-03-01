<div class="wrap">
    <h2><?php esc_html_e( 'Products Section', 'wp-woocommerce-printify-sync' ); ?></h2>
    <p><?php esc_html_e( 'Manage your Printify products here.', 'wp-woocommerce-printify-sync' ); ?></p>
    <!-- Add content for managing products -->
    <button id="printify-sync-import-btn"><?php esc_html_e( 'Import Products', 'wp-woocommerce-printify-sync' ); ?></button>
    <div id="printify-sync-import-progress" style="display: none;">
        <progress id="printify-sync-import-progress-bar" value="0" max="100"></progress>
        <span id="printify-sync-import-progress-text">0%</span>
    </div>
</div>
<script src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/js/products.js'; ?>"></script>