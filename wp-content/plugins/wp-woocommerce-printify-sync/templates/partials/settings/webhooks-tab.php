<?php defined('ABSPATH') || exit; ?>

<div class="tab-pane fade" id="webhooks" role="tabpanel" aria-labelledby="webhooks-tab">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <?php esc_html_e('Webhooks allow Printify to send real-time updates to your store. Configure them here and then add them to your Printify settings.', 'wp-woocommerce-printify-sync'); ?>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="printify_webhook_secret" class="form-label">
                <?php esc_html_e('Webhook Secret', 'wp-woocommerce-printify-sync'); ?>
            </label>
            <div class="password-input-group">
                <input type="password" class="form-control" id="printify_webhook_secret" name="printify_webhook_secret" value="<?php echo esc_attr($webhook_secret ?? ''); ?>">
                <button type="button" class="password-toggle" tabindex="-1">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="form-text"><?php esc_html_e('Secret key used to verify webhook requests from Printify.', 'wp-woocommerce-printify-sync'); ?></div>
        </div>
        <div class="col-md-6 mt-4">
            <button type="button" id="generate-webhook-secret" class="btn btn-outline-primary">
                <i class="fas fa-key"></i> <?php esc_html_e('Generate Secret', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <h6><?php esc_html_e('Webhook URLs', 'wp-woocommerce-printify-sync'); ?></h6>
            <p><?php esc_html_e('Add these URLs to your Printify account to receive updates:', 'wp-woocommerce-printify-sync'); ?></p>
            
            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <span><?php esc_html_e('Webhook URL', 'wp-woocommerce-printify-sync'); ?></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary copy-webhook-url">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <code class="webhook-url"><?php echo esc_url(rest_url('wpwps/v1/webhook')); ?></code>
                </div>
            </div>
            
            <div class="alert alert-success webhook-copy-success" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <?php esc_html_e('Webhook URL copied to clipboard!', 'wp-woocommerce-printify-sync'); ?>
            </div>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <h6><?php esc_html_e('Recommended Events', 'wp-woocommerce-printify-sync'); ?></h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Event', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php esc_html_e('Description', 'wp-woocommerce-printify-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>order:update</code></td>
                            <td><?php esc_html_e('Triggers when an order status changes in Printify', 'wp-woocommerce-printify-sync'); ?></td>
                        </tr>
                        <tr>
                            <td><code>product:update</code></td>
                            <td><?php esc_html_e('Triggers when a product is updated in Printify', 'wp-woocommerce-printify-sync'); ?></td>
                        </tr>
                        <tr>
                            <td><code>shop:disconnected</code></td>
                            <td><?php esc_html_e('Triggers when the shop is disconnected from Printify', 'wp-woocommerce-printify-sync'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
