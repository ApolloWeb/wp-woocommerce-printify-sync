<?php
/** @var array $apiStatus */
?>

<div class="wpwps-api-status">
    <h3><?php _e('System Status', 'wp-woocommerce-printify-sync'); ?></h3>
    
    <div class="wpwps-status-grid">
        <!-- Printify API -->
        <div class="status-item <?php echo $apiStatus['printify'] ? 'success' : 'error'; ?>">
            <span class="status-icon">
                <i class="fas fa-cloud"></i>
            </span>
            <div class="status-content">
                <h4><?php _e('Printify API', 'wp-woocommerce-printify-sync'); ?></h4>
                <p><?php echo $apiStatus['printify'] ? __('Connected', 'wp-woocommerce-printify-sync') : __('Error', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
        </div>
        
        <!-- WooCommerce -->
        <div class="status-item <?php echo $apiStatus['woocommerce'] ? 'success' : 'error'; ?>">
            <span class="status-icon">
                <i class="fas fa-shopping-cart"></i>
            </span>
            <div class="status-content">
                <h4><?php _e('WooCommerce', 'wp-woocommerce-printify-sync'); ?></h4>
                <p><?php echo $apiStatus['woocommerce'] ? __('Active', 'wp-woocommerce-printify-sync') : __('Not Active', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
        </div>
        
        <!-- Geolocation API -->
        <div class="status-item <?php echo $apiStatus['geolocation'] ? 'success' : 'warning'; ?>">
            <span class="status-icon">
                <i class="fas fa-map-marker-alt"></i>
            </span>
            <div class="status-content">
                <h4><?php _e('Geolocation', 'wp-woocommerce-printify-sync'); ?></h4>
                <p><?php echo $apiStatus['geolocation'] ? __('Enabled', 'wp-woocommerce-printify-sync') : __('Disabled', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
        </div>
        
        <!-- Currency API -->
        <div class="status-item <?php echo $apiStatus['currency'] ? 'success' : 'warning'; ?>">
            <span class="status-icon">
                <i class="fas fa-money-bill"></i>
            </span>
            <div class="status-content">
                <h4><?php _e('Currency API', 'wp-woocommerce-printify-sync'); ?></h4>
                <p><?php echo $apiStatus['currency'] ? __('Connected', 'wp-woocommerce-printify-sync') : __('Not Connected', 'wp-woocommerce-printify-sync'); ?></p>
            </div>
        </div>
    </div>
</div>