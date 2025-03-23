<div class="row g-4">
    <div class="col-lg-8">
        <!-- API Settings -->
        <div class="wpwps-card mb-4">
            <div class="card-body">
                <h5 class="card-title">API Settings</h5>
                <form id="api-settings-form">
                    <div class="mb-3">
                        <label class="form-label">Printify API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="printify-api-key" 
                                   value="<?php echo esc_attr(get_option('wpwps_printify_api_key', '')); ?>">
                            <button class="btn btn-outline-secondary" type="button" id="toggle-api-key">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-primary" type="button" id="test-api-connection">
                                Test Connection
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">OpenAI API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="openai-api-key" 
                                   value="<?php echo esc_attr(get_option('wpwps_openai_api_key', '')); ?>">
                            <button class="btn btn-outline-secondary" type="button" id="toggle-openai-key">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Shop ID</label>
                        <select class="form-select" id="printify-shop-id">
                            <option value="">Select Shop</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save API Settings</button>
                </form>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="wpwps-card mb-4">
            <div class="card-body">
                <h5 class="card-title">Email Settings</h5>
                <form id="email-settings-form">
                    <div class="mb-3">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="smtp-host" 
                               value="<?php echo esc_attr(get_option('wpwps_smtp_host', '')); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" class="form-control" id="smtp-username" 
                                       value="<?php echo esc_attr(get_option('wpwps_smtp_username', '')); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" id="smtp-password" 
                                       value="<?php echo esc_attr(get_option('wpwps_smtp_password', '')); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" id="smtp-port" 
                                       value="<?php echo esc_attr(get_option('wpwps_smtp_port', '587')); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Encryption</label>
                                <select class="form-select" id="smtp-encryption">
                                    <option value="tls" <?php selected(get_option('wpwps_smtp_encryption', 'tls'), 'tls'); ?>>TLS</option>
                                    <option value="ssl" <?php selected(get_option('wpwps_smtp_encryption', 'tls'), 'ssl'); ?>>SSL</option>
                                    <option value="none" <?php selected(get_option('wpwps_smtp_encryption', 'tls'), 'none'); ?>>None</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Email Settings</button>
                    <button type="button" class="btn btn-outline-primary" id="test-email">Send Test Email</button>
                </form>
            </div>
        </div>

        <!-- Email Server Settings -->
        <div class="wpwps-card mb-4">
            <div class="card-body">
                <h5 class="card-title d-flex justify-content-between">
                    Email Server Settings
                    <span class="badge bg-success" id="email-status-badge">Connected</span>
                </h5>

                <!-- POP3 Settings -->
                <div class="mb-4">
                    <h6>POP3 Configuration</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">POP3 Host</label>
                            <input type="text" class="form-control" id="pop3-host" 
                                   value="<?php echo esc_attr(get_option('wpwps_pop3_host', '')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Port</label>
                            <input type="number" class="form-control" id="pop3-port" 
                                   value="<?php echo esc_attr(get_option('wpwps_pop3_port', '995')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Security</label>
                            <select class="form-select" id="pop3-security">
                                <option value="ssl" <?php selected(get_option('wpwps_pop3_security', 'ssl'), 'ssl'); ?>>SSL</option>
                                <option value="tls" <?php selected(get_option('wpwps_pop3_security', 'ssl'), 'tls'); ?>>TLS</option>
                                <option value="none" <?php selected(get_option('wpwps_pop3_security', 'ssl'), 'none'); ?>>None</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="pop3-username" 
                                   value="<?php echo esc_attr(get_option('wpwps_pop3_username', '')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="pop3-password" 
                                       value="<?php echo esc_attr(get_option('wpwps_pop3_password', '')); ?>">
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary" id="test-pop3">
                            Test POP3 Connection
                        </button>
                        <div class="form-check form-switch d-inline-block ms-3">
                            <input class="form-check-input" type="checkbox" id="pop3-delete" 
                                   <?php checked(get_option('wpwps_pop3_delete', 'yes'), 'yes'); ?>>
                            <label class="form-check-label">Delete messages after fetching</label>
                        </div>
                    </div>
                </div>

                <!-- SMTP Settings -->
                <div class="mb-4">
                    <h6>SMTP Configuration</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtp-host" 
                                   value="<?php echo esc_attr(get_option('wpwps_smtp_host', '')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Port</label>
                            <input type="number" class="form-control" id="smtp-port" 
                                   value="<?php echo esc_attr(get_option('wpwps_smtp_port', '587')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Security</label>
                            <select class="form-select" id="smtp-security">
                                <option value="tls" <?php selected(get_option('wpwps_smtp_security', 'tls'), 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected(get_option('wpwps_smtp_security', 'tls'), 'ssl'); ?>>SSL</option>
                                <option value="none" <?php selected(get_option('wpwps_smtp_security', 'tls'), 'none'); ?>>None</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="smtp-username" 
                                   value="<?php echo esc_attr(get_option('wpwps_smtp_username', '')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="smtp-password" 
                                       value="<?php echo esc_attr(get_option('wpwps_smtp_password', '')); ?>">
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary" id="test-smtp">
                            Test SMTP Connection
                        </button>
                    </div>
                </div>

                <!-- Queue Settings -->
                <div class="mb-4">
                    <h6>Queue Configuration</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Email Check Interval</label>
                            <select class="form-select" id="email-check-interval">
                                <option value="300" <?php selected(get_option('wpwps_email_check_interval', '900'), '300'); ?>>Every 5 minutes</option>
                                <option value="600" <?php selected(get_option('wpwps_email_check_interval', '900'), '600'); ?>>Every 10 minutes</option>
                                <option value="900" <?php selected(get_option('wpwps_email_check_interval', '900'), '900'); ?>>Every 15 minutes</option>
                                <option value="1800" <?php selected(get_option('wpwps_email_check_interval', '900'), '1800'); ?>>Every 30 minutes</option>
                                <option value="3600" <?php selected(get_option('wpwps_email_check_interval', '900'), '3600'); ?>>Every hour</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Queue Processing Interval</label>
                            <select class="form-select" id="queue-process-interval">
                                <option value="60" <?php selected(get_option('wpwps_queue_interval', '300'), '60'); ?>>Every minute</option>
                                <option value="300" <?php selected(get_option('wpwps_queue_interval', '300'), '300'); ?>>Every 5 minutes</option>
                                <option value="600" <?php selected(get_option('wpwps_queue_interval', '300'), '600'); ?>>Every 10 minutes</option>
                                <option value="900" <?php selected(get_option('wpwps_queue_interval', '300'), '900'); ?>>Every 15 minutes</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batch Size</label>
                            <input type="number" class="form-control" id="queue-batch-size" 
                                   value="<?php echo esc_attr(get_option('wpwps_queue_batch_size', '50')); ?>"
                                   min="10" max="100">
                            <small class="text-muted">Maximum emails to process per batch</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Retry Limit</label>
                            <input type="number" class="form-control" id="queue-retry-limit" 
                                   value="<?php echo esc_attr(get_option('wpwps_queue_retry_limit', '3')); ?>"
                                   min="1" max="10">
                            <small class="text-muted">Maximum retry attempts for failed emails</small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="save-email-settings">Save Email Settings</button>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Sync Settings -->
        <div class="wpwps-card mb-4">
            <div class="card-body">
                <h5 class="card-title">Sync Settings</h5>
                <form id="sync-settings-form">
                    <div class="mb-3">
                        <label class="form-label">Sync Interval</label>
                        <select class="form-select" id="sync-interval">
                            <option value="3600">Every hour</option>
                            <option value="7200">Every 2 hours</option>
                            <option value="14400">Every 4 hours</option>
                            <option value="21600">Every 6 hours</option>
                            <option value="43200">Every 12 hours</option>
                            <option value="86400">Every 24 hours</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">API Rate Limit</label>
                        <input type="number" class="form-control" id="api-rate-limit" 
                               value="<?php echo esc_attr(get_option('wpwps_api_rate_limit', '60')); ?>">
                        <small class="text-muted">Requests per minute</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Sync Settings</button>
                </form>
            </div>
        </div>

        <!-- License Settings -->
        <div class="wpwps-card">
            <div class="card-body">
                <h5 class="card-title">License</h5>
                <form id="license-form">
                    <div class="mb-3">
                        <label class="form-label">License Key</label>
                        <input type="text" class="form-control" id="license-key" 
                               value="<?php echo esc_attr(get_option('wpwps_license_key', '')); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Activate License</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="wpwps-card mb-4">
    <div class="card-body">
        <h5 class="card-title">AI Settings</h5>
        <form id="ai-settings-form">
            <div class="mb-3">
                <label class="form-label">OpenAI API Key</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="openai-api-key" 
                           value="<?php echo esc_attr(get_option('wpwps_openai_api_key', '')); ?>">
                    <button class="btn btn-outline-secondary" type="button" id="toggle-openai-key">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-primary" type="button" id="test-ai-connection">
                        Test Connection
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Model Configuration</label>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Max Tokens per Request</label>
                        <input type="number" class="form-control" id="ai-max-tokens" 
                               value="<?php echo esc_attr(get_option('wpwps_ai_max_tokens', '500')); ?>"
                               min="1" max="4000" step="1">
                        <small class="text-muted">Maximum tokens to generate per API call</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Temperature (Creativity) <span id="temp-value">0.7</span></label>
                        <input type="range" class="form-range" id="ai-temperature" 
                               value="<?php echo esc_attr(get_option('wpwps_ai_temperature', '0.7')); ?>"
                               min="0" max="1" step="0.1">
                        <small class="text-muted">Lower values = more focused, Higher = more creative</small>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Monthly Usage Limits</label>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Monthly Token Cap</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="ai-monthly-cap" 
                                   value="<?php echo esc_attr(get_option('wpwps_ai_monthly_cap', '100000')); ?>"
                                   min="1000" step="1000">
                            <span class="input-group-text">tokens</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monthly Budget Cap</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="ai-budget-cap" 
                                   value="<?php echo esc_attr(get_option('wpwps_ai_budget_cap', '20')); ?>"
                                   min="1" step="1">
                        </div>
                    </div>
                </div>
            </div>

            <div class="wpwps-card bg-light mb-4">
                <div class="card-body">
                    <h6 class="card-title">Cost Estimation</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Estimated Daily Requests</label>
                            <input type="number" class="form-control form-control-sm" id="ai-daily-requests" 
                                   value="100" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Average Tokens/Request</label>
                            <input type="number" class="form-control form-control-sm" id="ai-avg-tokens" 
                                   value="200" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Model Price/1K tokens</label>
                            <input type="number" class="form-control form-control-sm" id="ai-token-price" 
                                   value="0.02" step="0.001" readonly>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <label class="form-label">Daily Cost</label>
                            <h5 class="mb-0" id="ai-daily-cost">$0.00</h5>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Weekly Cost</label>
                            <h5 class="mb-0" id="ai-weekly-cost">$0.00</h5>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Monthly Cost</label>
                            <h5 class="mb-0" id="ai-monthly-cost">$0.00</h5>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Monthly Tokens</label>
                            <h5 class="mb-0" id="ai-monthly-tokens">0</h5>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save AI Settings</button>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> The system will stop making AI API calls when either the token cap or budget cap is reached for the month.
            </div>
        </form>
    </div>
</div>
