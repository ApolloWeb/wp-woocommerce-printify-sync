<?php defined('ABSPATH') || die('Direct access not allowed.'); ?>

<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title"><?php esc_html_e('Advanced Settings', 'wp-woocommerce-printify-sync'); ?></h5>
        
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="max_retries" class="form-label"><?php esc_html_e('Max Retries', 'wp-woocommerce-printify-sync'); ?></label>
                    <input type="number" class="form-control" id="max_retries" name="max_retries" 
                           value="<?php echo esc_attr(get_option('wpwps_max_retries', 3)); ?>" min="1" max="10">
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="retry_delay" class="form-label"><?php esc_html_e('Retry Delay (seconds)', 'wp-woocommerce-printify-sync'); ?></label>
                    <input type="number" class="form-control" id="retry_delay" name="retry_delay" 
                           value="<?php echo esc_attr(get_option('wpwps_retry_delay', 5)); ?>" min="1" max="30">
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="rate_limit_buffer" class="form-label"><?php esc_html_e('Rate Limit Buffer (%)', 'wp-woocommerce-printify-sync'); ?></label>
                    <input type="number" class="form-control" id="rate_limit_buffer" name="rate_limit_buffer" 
                           value="<?php echo esc_attr(get_option('wpwps_rate_limit_buffer', 20)); ?>" min="5" max="50">
                </div>
            </div>
        </div>
    </div>
</div>

<div id="apiHealth" class="card mt-4">
    <div class="card-body">
        <h5 class="card-title"><?php esc_html_e('API Health Status', 'wp-woocommerce-printify-sync'); ?></h5>
        <div id="apiLimitStatus">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden"><?php esc_html_e('Loading...', 'wp-woocommerce-printify-sync'); ?></span>
            </div>
        </div>
    </div>
</div>