<?php
/**
 * Settings template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap wpwps-settings">
    <h1 class="wp-heading-inline">
        <i class="fas fa-cog"></i> <?php echo esc_html__('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?>
    </h1>

    <div class="wpwps-settings-container">
        <div class="wpwps-settings-tabs">
            <ul class="nav nav-tabs" id="wpwps-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="api-tab" data-bs-toggle="tab" data-bs-target="#api" type="button" role="tab" aria-controls="api" aria-selected="true">
                        <i class="fas fa-plug"></i> <?php echo esc_html__('API Settings', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sync-tab" data-bs-toggle="tab" data-bs-target="#sync" type="button" role="tab" aria-controls="sync" aria-selected="false">
                        <i class="fas fa-sync"></i> <?php echo esc_html__('Sync Settings', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="false">
                        <i class="fas fa-envelope"></i> <?php echo esc_html__('Email Settings', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ai-tab" data-bs-toggle="tab" data-bs-target="#ai" type="button" role="tab" aria-controls="ai" aria-selected="false">
                        <i class="fas fa-robot"></i> <?php echo esc_html__('AI Settings', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab" aria-controls="advanced" aria-selected="false">
                        <i class="fas fa-cogs"></i> <?php echo esc_html__('Advanced', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                </li>
            </ul>
            <div class="tab-content" id="wpwps-tabs-content">
                <!-- API Settings Tab -->
                <div class="tab-pane fade show active" id="api" role="tabpanel" aria-labelledby="api-tab">
                    <div class="card wpwps-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo esc_html__('Printify API Connection', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo esc_html__('Connect to your Printify account to sync products.', 'wp-woocommerce-printify-sync'); ?>
                                <a href="https://developers.printify.com/" target="_blank"><?php echo esc_html__('Learn more about Printify API', 'wp-woocommerce-printify-sync'); ?></a>
                            </p>

                            <form id="wpwps-api-settings-form" class="wpwps-form">
                                <div class="mb-3">
                                    <label for="api_endpoint" class="form-label"><?php echo esc_html__('API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="url" class="form-control" id="api_endpoint" name="api_endpoint" value="<?php echo esc_attr($api_endpoint); ?>" required>
                                    <div class="form-text"><?php echo esc_html__('The base URL for the Printify API.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="mb-3">
                                    <label for="api_key" class="form-label"><?php echo esc_html__('API Key', 'wp-woocommerce-printify-sync'); ?></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" placeholder="<?php echo esc_attr($api_key_encrypted ? '••••••••••••••••••••••••' : ''); ?>">
                                        <button class="button button-secondary toggle-password" type="button" data-toggle="api_key">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="button button-secondary" type="button" id="wpwps-test-api-connection">
                                            <?php echo esc_html__('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                        </button>
                                    </div>
                                    <div class="form-text"><?php echo esc_html__('Your Printify API key. This will be stored securely.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div id="shop-selection-container" class="mb-3 <?php echo empty($api_key_encrypted) ? 'd-none' : ''; ?>">
                                    <label for="shop_id" class="form-label"><?php echo esc_html__('Select Shop', 'wp-woocommerce-printify-sync'); ?></label>
                                    <select class="form-control" id="shop_id" name="shop_id" <?php echo !empty($shop_id) ? 'disabled' : ''; ?>>
                                        <?php if ($shop_id && $shop_name) : ?>
                                            <option value="<?php echo esc_attr($shop_id); ?>" selected><?php echo esc_html($shop_name); ?></option>
                                        <?php else : ?>
                                            <option value="" selected disabled><?php echo esc_html__('Select a shop...', 'wp-woocommerce-printify-sync'); ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text">
                                        <?php if (!empty($shop_id)) : ?>
                                            <?php echo esc_html__('Your shop is connected. Shop ID cannot be changed once set.', 'wp-woocommerce-printify-sync'); ?>
                                        <?php else : ?>
                                            <?php echo esc_html__('Select the Printify shop to sync with your WooCommerce store.', 'wp-woocommerce-printify-sync'); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="wpwps-form-actions">
                                    <button type="submit" class="button button-primary">
                                        <i class="fas fa-save"></i> <?php echo esc_html__('Save API Settings', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card wpwps-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo esc_html__('Webhook Setup', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo esc_html__('Use webhooks to receive real-time updates from Printify.', 'wp-woocommerce-printify-sync'); ?>
                            </p>

                            <div class="mb-3">
                                <label class="form-label"><?php echo esc_html__('Webhook URL', 'wp-woocommerce-printify-sync'); ?></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="webhook_url" value="<?php echo esc_url(rest_url('wpwps/v1/webhook')); ?>" readonly>
                                    <button class="button button-secondary" type="button" id="wpwps-copy-webhook-url">
                                        <i class="fas fa-copy"></i> <?php echo esc_html__('Copy', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                </div>
                                <div class="form-text"><?php echo esc_html__('Add this URL to your Printify webhook settings.', 'wp-woocommerce-printify-sync'); ?></div>
                            </div>

                            <div class="wpwps-webhook-instructions">
                                <h6><?php echo esc_html__('How to set up webhooks:', 'wp-woocommerce-printify-sync'); ?></h6>
                                <ol>
                                    <li><?php echo esc_html__('Copy the webhook URL above.', 'wp-woocommerce-printify-sync'); ?></li>
                                    <li><?php echo esc_html__('Go to your Printify Developer Dashboard.', 'wp-woocommerce-printify-sync'); ?></li>
                                    <li><?php echo esc_html__('Navigate to Webhooks section.', 'wp-woocommerce-printify-sync'); ?></li>
                                    <li><?php echo esc_html__('Add a new webhook using the URL.', 'wp-woocommerce-printify-sync'); ?></li>
                                    <li><?php echo esc_html__('Select all relevant event types (products, orders, etc.).', 'wp-woocommerce-printify-sync'); ?></li>
                                    <li><?php echo esc_html__('Save the webhook configuration.', 'wp-woocommerce-printify-sync'); ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sync Settings Tab -->
                <div class="tab-pane fade" id="sync" role="tabpanel" aria-labelledby="sync-tab">
                    <div class="card wpwps-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo esc_html__('Product Sync Settings', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo esc_html__('Configure how products are synchronized between Printify and WooCommerce.', 'wp-woocommerce-printify-sync'); ?>
                            </p>

                            <form id="wpwps-sync-settings-form" class="wpwps-form">
                                <div class="mb-3">
                                    <label for="sync_interval" class="form-label"><?php echo esc_html__('Sync Interval (hours)', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="sync_interval" name="sync_interval" value="<?php echo esc_attr($sync_interval); ?>" min="1" max="24" required>
                                    <div class="form-text"><?php echo esc_html__('How often to automatically sync products from Printify (1-24 hours).', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="wpwps-form-actions">
                                    <button type="submit" class="button button-primary">
                                        <i class="fas fa-save"></i> <?php echo esc_html__('Save Sync Settings', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                    <button type="button" class="button button-secondary" id="wpwps-run-sync-now">
                                        <i class="fas fa-sync"></i> <?php echo esc_html__('Sync Now', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Email Settings Tab -->
                <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                    <div class="card wpwps-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo esc_html__('Email Queue Settings', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo esc_html__('Configure the email queue system for order notifications and customer communications.', 'wp-woocommerce-printify-sync'); ?>
                            </p>

                            <form id="wpwps-email-queue-settings-form" class="wpwps-form">
                                <div class="mb-3">
                                    <label for="email_queue_interval" class="form-label"><?php echo esc_html__('Processing Interval (minutes)', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="email_queue_interval" name="email_queue_interval" value="<?php echo esc_attr($email_queue_interval); ?>" min="1" max="60" required>
                                    <div class="form-text"><?php echo esc_html__('How often to process the email queue (1-60 minutes).', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="mb-3">
                                    <label for="email_queue_batch_size" class="form-label"><?php echo esc_html__('Batch Size', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="email_queue_batch_size" name="email_queue_batch_size" value="<?php echo esc_attr($email_queue_batch_size); ?>" min="1" max="100" required>
                                    <div class="form-text"><?php echo esc_html__('Number of emails to process in each batch (1-100).', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="wpwps-form-actions">
                                    <button type="submit" class="button button-primary">
                                        <i class="fas fa-save"></i> <?php echo esc_html__('Save Email Queue Settings', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card wpwps-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo esc_html__('Email Fetching (POP3)', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo esc_html__('Configure POP3 settings to fetch customer emails for the ticketing system.', 'wp-woocommerce-printify-sync'); ?>
                            </p>

                            <form id="wpwps-pop3-settings-form" class="wpwps-form">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enable_pop3" name="enable_pop3" <?php echo $enable_pop3 ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enable_pop3"><?php echo esc_html__('Enable POP3 Email Fetching', 'wp-woocommerce-printify-sync'); ?></label>
                                    </div>
                                    <div class="form-text"><?php echo esc_html__('Enable to fetch customer emails via POP3 for the support ticketing system.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div id="pop3-settings" class="<?php echo $enable_pop3 ? '' : 'd-none'; ?>">
                                    <div class="mb-3">
                                        <label for="pop3_server" class="form-label"><?php echo esc_html__('POP3 Server', 'wp-woocommerce-printify-sync'); ?></label>
                                        <input type="text" class="form-control" id="pop3_server" name="pop3_server" value="<?php echo esc_attr($pop3_server); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="pop3_port" class="form-label"><?php echo esc_html__('POP3 Port', 'wp-woocommerce-printify-sync'); ?></label>
                                        <input type="number" class="form-control" id="pop3_port" name="pop3_port" value="<?php echo esc_attr($pop3_port); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="pop3_ssl" name="pop3_ssl" <?php echo $pop3_ssl ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pop3_ssl"><?php echo esc_html__('Use SSL', 'wp-woocommerce-printify-sync'); ?></label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="pop3_username" class="form-label"><?php echo esc_html__('Username', 'wp-woocommerce-printify-sync'); ?></label>
                                        <input type="text" class="form-control" id="pop3_username" name="pop3_username" value="<?php echo esc_attr($pop3_username); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="pop3_password" class="form-label"><?php echo esc_html__('Password', 'wp-woocommerce-printify-sync'); ?></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="pop3_password" name="pop3_password" placeholder="<?php echo esc_attr($pop3_password_encrypted ? '••••••••••••••••••••••••' : ''); ?>">
                                            <button class="button button-secondary toggle-password" type="button" data-toggle="pop3_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="wpwps-form-actions">
                                    <button type="submit" class="button button-primary">
                                        <i class="fas fa-save"></i> <?php echo esc_html__('Save POP3 Settings', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                    <button type="button" class="button button-secondary" id="wpwps-test-pop3">
                                        <i class="fas fa-vial"></i> <?php echo esc_html__('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card wpwps-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo esc_html__('Email Signature', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo esc_html__('Configure the signature that will be added to outgoing support emails.', 'wp-woocommerce-printify-sync'); ?>
                            </p>

                            <form id="wpwps-signature-settings-form" class="wpwps-form">
                                <div class="mb-3">
                                    <label for="email_signature" class="form-label"><?php echo esc_html__('Email Signature', 'wp-woocommerce-printify-sync'); ?></label>
                                    <textarea class="form-control" id="email_signature" name="email_signature" rows="5"><?php echo esc_textarea($email_signature); ?></textarea>
                                    <div class="form-text"><?php echo esc_html__('This signature will be appended to all outgoing support emails.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="wpwps-form-actions">
                                    <button type="submit" class="button button-primary">
                                        <i class="fas fa-save"></i> <?php echo esc_html__('Save Signature', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- AI Settings Tab -->
                <div class="tab-pane fade" id="ai" role="tabpanel" aria-labelledby="ai-tab">
                    <div class="card wpwps-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo esc_html__('OpenAI Integration', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo esc_html__('Configure OpenAI for AI-powered support ticket analysis and response generation.', 'wp-woocommerce-printify-sync'); ?>
                            </p>

                            <form id="wpwps-openai-settings-form" class="wpwps-form">
                                <div class="mb-3">
                                    <label for="openai_api_key" class="form-label"><?php echo esc_html__('OpenAI API Key', 'wp-woocommerce-printify-sync'); ?></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="openai_api_key" name="openai_api_key" placeholder="<?php echo esc_attr($openai_api_key_encrypted ? '••••••••••••••••••••••••' : ''); ?>">
                                        <button class="button button-secondary toggle-password" type="button" data-toggle="openai_api_key">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="button button-secondary" type="button" id="wpwps-test-openai-connection">
                                            <?php echo esc_html__('Test API', 'wp-woocommerce-printify-sync'); ?>
                                        </button>
                                    </div>
                                    <div class="form-text"><?php echo esc_html__('Your OpenAI API key. This will be stored securely.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="mb-3">
                                    <label for="openai_model" class="form-label"><?php echo esc_html__('Model', 'wp-woocommerce-printify-sync'); ?></label>
                                    <select class="form-control" id="openai_model" name="openai_model">
                                        <option value="gpt-3.5-turbo" <?php selected($openai_model, 'gpt-3.5-turbo'); ?>><?php echo esc_html__('GPT-3.5 Turbo', 'wp-woocommerce-printify-sync'); ?></option>
                                        <option value="gpt-4" <?php selected($openai_model, 'gpt-4'); ?>><?php echo esc_html__('GPT-4', 'wp-woocommerce-printify-sync'); ?></option>
                                    </select>
                                    <div class="form-text"><?php echo esc_html__('Select the OpenAI model to use for AI-powered features.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="mb-3">
                                    <label for="openai_temperature" class="form-label"><?php echo esc_html__('Temperature', 'wp-woocommerce-printify-sync'); ?> (<span id="temperature-value"><?php echo esc_html($openai_temperature); ?></span>)</label>
                                    <input type="range" class="form-range" id="openai_temperature" name="openai_temperature" min="0" max="1" step="0.1" value="<?php echo esc_attr($openai_temperature); ?>">
                                    <div class="form-text"><?php echo esc_html__('Controls randomness: 0 is more deterministic, 1 is more creative.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="mb-3">
                                    <label for="openai_monthly_token_cap" class="form-label"><?php echo esc_html__('Monthly Token Cap', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="openai_monthly_token_cap" name="openai_monthly_token_cap" value="<?php echo esc_attr($openai_monthly_token_cap); ?>" min="1000" step="1000">
                                    <div class="form-text"><?php echo esc_html__('Maximum number of tokens to use per month. Used to control costs.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label"><?php echo esc_html__('Current Usage', 'wp-woocommerce-printify-sync'); ?></label>
                                    <div class="wpwps-usage-card p-3">
                                        <div class="wpwps-progress mb-2">
                                            <?php
                                            $usage_percent = $openai_monthly_token_cap > 0 ? min(100, round($openai_current_month_usage / $openai_monthly_token_cap * 100)) : 0;
                                            ?>
                                            <div class="wpwps-progress-bar" style="width: <?php echo esc_attr($usage_percent); ?>%">
                                                <?php echo esc_html($usage_percent); ?>%
                                            </div>
                                        </div>
                                        <div class="wpwps-usage-stats">
                                            <div><?php echo esc_html(number_format($openai_current_month_usage) . ' / ' . number_format($openai_monthly_token_cap) . ' ' . __('tokens', 'wp-woocommerce-printify-sync')); ?></div>
                                            <div><?php echo esc_html__('Estimated cost:', 'wp-woocommerce-printify-sync') . ' $' . number_format($openai_current_month_cost, 2); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="wpwps-form-actions">
                                    <button type="submit" class="button button-primary">
                                        <i class="fas fa-save"></i> <?php echo esc_html__('Save AI Settings', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Advanced Tab -->
                <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                    <div class="card wpwps-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo esc_html__('Log Settings', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo esc_html__('Configure logging behavior for troubleshooting.', 'wp-woocommerce-printify-sync'); ?>
                            </p>

                            <form id="wpwps-log-settings-form" class="wpwps-form">
                                <div class="mb-3">
                                    <label for="log_retention_days" class="form-label"><?php echo esc_html__('Log Retention (days)', 'wp-woocommerce-printify-sync'); ?></label>
                                    <input type="number" class="form-control" id="log_retention_days" name="log_retention_days" value="<?php echo esc_attr($log_retention_days); ?>" min="1" max="365" required>
                                    <div class="form-text"><?php echo esc_html__('Number of days to keep logs before automatic deletion (1-365).', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="wpwps-form-actions">
                                    <button type="submit" class="button button-primary">
                                        <i class="fas fa-save"></i> <?php echo esc_html__('Save Log Settings', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpwps-settings&action=download-logs')); ?>" class="button button-secondary">
                                        <i class="fas fa-download"></i> <?php echo esc_html__('Download Logs', 'wp-woocommerce-printify-sync'); ?>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card wpwps-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-exclamation-triangle text-danger"></i> <?php echo esc_html__('Danger Zone', 'wp-woocommerce-printify-sync'); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo esc_html__('These actions can cause data loss. Use with caution.', 'wp-woocommerce-printify-sync'); ?>
                            </p>

                            <form id="wpwps-danger-zone-form" class="wpwps-form">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="delete_data_on_uninstall" name="delete_data_on_uninstall" <?php echo $delete_data_on_uninstall ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="delete_data_on_uninstall"><?php echo esc_html__('Delete all plugin data when uninstalling', 'wp-woocommerce-printify-sync'); ?></label>
                                    </div>
                                    <div class="form-text"><?php echo esc_html__('If enabled, all plugin data including settings, logs, and product mappings will be deleted when the plugin is uninstalled.', 'wp-woocommerce-printify-sync'); ?></div>
                                </div>

                                <div class="wpwps-form-actions">
                                    <button type="submit" class="button button-primary">
                                        <i class="fas fa-save"></i> <?php echo esc_html__('Save Settings', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                    <button type="button" class="button button-danger" id="wpwps-reset-plugin">
                                        <i class="fas fa-trash"></i> <?php echo esc_html__('Reset Plugin Data', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
