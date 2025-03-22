<?php defined('ABSPATH') || exit; ?>

<div class="tab-pane fade" id="oauth" role="tabpanel" aria-labelledby="oauth-tab">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <?php esc_html_e('Connect to Printify using OAuth for a more secure connection. This method is recommended over API keys.', 'wp-woocommerce-printify-sync'); ?>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="printify_client_id" class="form-label">
                <?php esc_html_e('Client ID', 'wp-woocommerce-printify-sync'); ?>
            </label>
            <input type="text" class="form-control" id="printify_client_id" name="printify_client_id" value="<?php echo esc_attr($client_id ?? ''); ?>">
            <div class="form-text"><?php esc_html_e('Enter your OAuth Client ID from Printify Developer Dashboard.', 'wp-woocommerce-printify-sync'); ?></div>
        </div>
        
        <div class="col-md-6">
            <label for="printify_client_secret" class="form-label">
                <?php esc_html_e('Client Secret', 'wp-woocommerce-printify-sync'); ?>
            </label>
            <div class="password-input-group">
                <input type="password" class="form-control" id="printify_client_secret" name="printify_client_secret" value="<?php echo esc_attr($client_secret ?? ''); ?>">
                <button type="button" class="password-toggle" tabindex="-1">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="form-text"><?php esc_html_e('Enter your OAuth Client Secret from Printify Developer Dashboard.', 'wp-woocommerce-printify-sync'); ?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <label class="form-label"><?php esc_html_e('Connection Status', 'wp-woocommerce-printify-sync'); ?></label>
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($access_token)) : ?>
                        <div class="d-flex align-items-center">
                            <span class="wpwps-status-indicator wpwps-status-success me-2"></span>
                            <span class="fw-bold"><?php esc_html_e('Connected to Printify', 'wp-woocommerce-printify-sync'); ?></span>
                            
                            <?php if (!empty($token_expires)) : ?>
                                <span class="badge bg-light text-dark ms-2">
                                    <?php printf(
                                        esc_html__('Token expires: %s', 'wp-woocommerce-printify-sync'),
                                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $token_expires)
                                    ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($shops)) : ?>
                            <div class="mt-3">
                                <label for="printify_shop_id_oauth" class="form-label"><?php esc_html_e('Connected Shops', 'wp-woocommerce-printify-sync'); ?></label>
                                <select class="form-select" id="printify_shop_id_oauth" name="printify_shop_id">
                                    <option value=""><?php esc_html_e('Select a shop', 'wp-woocommerce-printify-sync'); ?></option>
                                    <?php foreach ($shops as $shop) : ?>
                                        <option value="<?php echo esc_attr($shop['id']); ?>" <?php selected($shop_id, $shop['id']); ?>>
                                            <?php echo esc_html($shop['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpwps_printify_disconnect'), 'wpwps_printify_disconnect')); ?>" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-unlink"></i> <?php esc_html_e('Disconnect', 'wp-woocommerce-printify-sync'); ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="d-flex align-items-center">
                            <span class="wpwps-status-indicator wpwps-status-danger me-2"></span>
                            <span class="fw-bold"><?php esc_html_e('Not connected to Printify', 'wp-woocommerce-printify-sync'); ?></span>
                        </div>
                        
                        <div class="mt-3">
                            <?php if (!empty($client_id) && !empty($client_secret)) : ?>
                                <a href="<?php echo esc_url($oauth_url); ?>" class="btn btn-primary">
                                    <i class="fas fa-plug"></i> <?php esc_html_e('Connect to Printify', 'wp-woocommerce-printify-sync'); ?>
                                </a>
                            <?php else : ?>
                                <div class="alert alert-warning">
                                    <?php esc_html_e('Enter your Client ID and Client Secret first, then save settings to enable the connection button.', 'wp-woocommerce-printify-sync'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <h6><?php esc_html_e('Redirect URI', 'wp-woocommerce-printify-sync'); ?></h6>
            <p><?php esc_html_e('Add this redirect URI to your Printify OAuth Application settings:', 'wp-woocommerce-printify-sync'); ?></p>
            
            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <span><?php esc_html_e('Redirect URI', 'wp-woocommerce-printify-sync'); ?></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary copy-redirect-uri">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <code class="redirect-uri"><?php echo esc_url(admin_url('admin-post.php?action=wpwps_printify_connect')); ?></code>
                </div>
            </div>
            
            <div class="alert alert-success redirect-copy-success" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <?php esc_html_e('Redirect URI copied to clipboard!', 'wp-woocommerce-printify-sync'); ?>
            </div>
        </div>
    </div>
</div>
