<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><?= __('Printify Webhooks', 'wp-woocommerce-printify-sync') ?></h5>
    </div>
    <div class="card-body">
        <p><?= __('Configure Printify webhooks to automatically sync orders and products.', 'wp-woocommerce-printify-sync') ?></p>
        
        <div class="mb-3">
            <label for="wpwps-webhook-url" class="form-label"><?= __('Webhook URL', 'wp-woocommerce-printify-sync') ?></label>
            <div class="input-group">
                <input type="text" class="form-control" id="wpwps-webhook-url" value="<?= esc_url(\ApolloWeb\WPWooCommercePrintifySync\Controllers\WebhookController::getWebhookUrl()) ?>" readonly>
                <button class="btn btn-outline-secondary" type="button" id="wpwps-copy-webhook-url">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <small class="form-text text-muted"><?= __('Provide this URL in your Printify dashboard to receive webhook events.', 'wp-woocommerce-printify-sync') ?></small>
        </div>
        
        <div class="mb-3">
            <label for="wpwps-webhook-secret" class="form-label"><?= __('Webhook Secret', 'wp-woocommerce-printify-sync') ?></label>
            <div class="input-group">
                <input type="text" class="form-control" id="wpwps-webhook-secret" value="<?= esc_attr(\ApolloWeb\WPWooCommercePrintifySync\Controllers\WebhookController::getMaskedWebhookSecret()) ?>" readonly>
                <button class="btn btn-outline-secondary" type="button" id="wpwps-regenerate-webhook-secret">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <small class="form-text text-muted"><?= __('Provide this secret in your Printify dashboard for secure webhook validation.', 'wp-woocommerce-printify-sync') ?></small>
        </div>
        
        <div class="mb-3">
            <label class="form-label"><?= __('Webhook Events', 'wp-woocommerce-printify-sync') ?></label>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= __('Order Created', 'wp-woocommerce-printify-sync') ?>
                    <span class="badge bg-success rounded-pill"><?= __('Supported', 'wp-woocommerce-printify-sync') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= __('Order Updated', 'wp-woocommerce-printify-sync') ?>
                    <span class="badge bg-success rounded-pill"><?= __('Supported', 'wp-woocommerce-printify-sync') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= __('Order Canceled', 'wp-woocommerce-printify-sync') ?>
                    <span class="badge bg-success rounded-pill"><?= __('Supported', 'wp-woocommerce-printify-sync') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= __('Shipping Update', 'wp-woocommerce-printify-sync') ?>
                    <span class="badge bg-success rounded-pill"><?= __('Supported', 'wp-woocommerce-printify-sync') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= __('Product Created', 'wp-woocommerce-printify-sync') ?>
                    <span class="badge bg-success rounded-pill"><?= __('Supported', 'wp-woocommerce-printify-sync') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= __('Product Updated', 'wp-woocommerce-printify-sync') ?>
                    <span class="badge bg-success rounded-pill"><?= __('Supported', 'wp-woocommerce-printify-sync') ?></span>
                </li>
            </ul>
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
            <button type="button" class="btn btn-primary" id="wpwps-test-webhook">
                <i class="fas fa-plug me-1"></i> <?= __('Test Webhook', 'wp-woocommerce-printify-sync') ?>
            </button>
            <a href="https://docs.printify.com/docs/webhooks" target="_blank" class="btn btn-outline-secondary">
                <i class="fas fa-external-link-alt me-1"></i> <?= __('Printify Documentation', 'wp-woocommerce-printify-sync') ?>
            </a>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Copy webhook URL to clipboard
    $('#wpwps-copy-webhook-url').on('click', function() {
        var copyText = document.getElementById('wpwps-webhook-url');
        copyText.select();
        document.execCommand('copy');
        WPWPS.toast.success('<?= esc_js(__('Webhook URL copied to clipboard', 'wp-woocommerce-printify-sync')) ?>');
    });
    
    // Regenerate webhook secret
    $('#wpwps-regenerate-webhook-secret').on('click', function() {
        if (!confirm('<?= esc_js(__('Are you sure you want to regenerate the webhook secret? You will need to update this in your Printify dashboard.', 'wp-woocommerce-printify-sync')) ?>')) {
            return;
        }
        
        WPWPS.api.post('generate_webhook_secret')
            .then(function(response) {
                if (response.success) {
                    $('#wpwps-webhook-secret').val(response.data.secret);
                    WPWPS.toast.success(response.data.message);
                } else {
                    WPWPS.toast.error(response.data.message || '<?= esc_js(__('Error regenerating webhook secret', 'wp-woocommerce-printify-sync')) ?>');
                }
            })
            .catch(function(error) {
                WPWPS.toast.error('<?= esc_js(__('Error regenerating webhook secret', 'wp-woocommerce-printify-sync')) ?>');
                console.error(error);
            });
    });
    
    // Test webhook
    $('#wpwps-test-webhook').on('click', function() {
        WPWPS.toast.info('<?= esc_js(__('Testing webhook...', 'wp-woocommerce-printify-sync')) ?>');
        
        WPWPS.api.post('test_webhook')
            .then(function(response) {
                if (response.success) {
                    WPWPS.toast.success(response.data.message);
                } else {
                    WPWPS.toast.error(response.data.message || '<?= esc_js(__('Error testing webhook', 'wp-woocommerce-printify-sync')) ?>');
                }
            })
            .catch(function(error) {
                WPWPS.toast.error('<?= esc_js(__('Error testing webhook', 'wp-woocommerce-printify-sync')) ?>');
                console.error(error);
            });
    });
});
</script>
