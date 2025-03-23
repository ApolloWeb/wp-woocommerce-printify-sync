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
