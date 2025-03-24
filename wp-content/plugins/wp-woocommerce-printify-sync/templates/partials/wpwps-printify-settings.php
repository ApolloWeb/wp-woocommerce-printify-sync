<form id="printifySettingsForm">
    <div class="mb-3">
        <label for="printify_api_key" class="form-label">API Key</label>
        <div class="input-group">
            <input type="password" class="form-control" id="printify_api_key" 
                   value="<?php echo esc_attr($settings['printify_api_key']); ?>"
                   aria-describedby="printifyApiHelp">
            <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Toggle API key visibility">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        <div id="printifyApiHelp" class="form-text">Enter your Printify API key to enable synchronization</div>
    </div>
    
    <div class="mb-3">
        <label for="printify_api_endpoint" class="form-label">API Endpoint</label>
        <input type="url" class="form-control" id="printify_api_endpoint"
               value="<?php echo esc_attr($settings['printify_api_endpoint'] ?? 'https://api.printify.com/v1'); ?>"
               aria-describedby="endpointHelp">
        <div id="endpointHelp" class="form-text">API endpoint URL (defaults to Printify production API)</div>
    </div>

    <div class="mb-3">
        <button type="button" id="testPrintifyConnection" class="btn btn-secondary" aria-describedby="connectionStatus">
            Test Connection
        </button>
        <div id="connectionStatus" class="visually-hidden" role="status"></div>
    </div>
    <div class="mb-3" id="shopSelector" style="display: none;">
        <label for="printify_shop" class="form-label">Select Shop</label>
        <select class="form-select" id="printify_shop" <?php echo $settings['printify_shop'] ? 'disabled' : ''; ?>>
        </select>
        <?php if ($settings['printify_shop']): ?>
            <small class="text-muted">Shop selection is locked for stability. Contact support to change shops.</small>
        <?php endif; ?>
    </div>
</form>
