jQuery(document).ready(function($) {
    // Create toast container if it doesn't exist
    if ($('.wpwps-toast-container').length === 0) {
        $('body').append('<div class="wpwps-toast-container"></div>');
    }

    // Cache selectors
    const $form = $('.wpwps-settings-form');
    const $testConnection = $('.test-connection');
    const $apiKey = $('#wpwps_api_key');
    const $shopId = $('#wpwps_shop_id');
    const $connectionStatus = $('.wpwps-settings-connection-status');
    const $autoSync = $('input[name="wpwps_settings[auto_sync]"]');
    const $syncInterval = $('#wpwps_sync_interval');
    const $testPrintifyConnection = $('.test-printify-connection');
    const $testOpenAIConnection = $('.test-openai-connection');
    const $printifyApiKey = $('#wpwps_printify_api_key');
    const $printifyEndpoint = $('#wpwps_printify_endpoint');
    const $printifyShop = $('#wpwps_printify_shop');
    const $openAIApiKey = $('#wpwps_openai_api_key');
    const $openAITokens = $('#wpwps_openai_tokens');
    const $openAITemperature = $('#wpwps_openai_temperature');
    const $openAISpendCap = $('#wpwps_openai_spend_cap');

    // Handle connection test
    $testConnection.on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const originalText = $button.html();

        // Validate inputs
        if (!$apiKey.val() || !$shopId.val()) {
            showAlert('error', wpwps.i18n.missing_credentials);
            return;
        }

        // Disable button and show loading state
        $button.html('<i class="fas fa-spinner fa-spin"></i> ' + wpwps.i18n.testing_connection);
        $button.prop('disabled', true);

        // Make API test request
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_connection',
                nonce: wpwps.nonce,
                api_key: $apiKey.val(),
                shop_id: $shopId.val()
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.data.message);
                    updateConnectionStatus(true);
                } else {
                    showAlert('error', response.data.message);
                    updateConnectionStatus(false);
                }
            },
            error: function() {
                showAlert('error', wpwps.i18n.connection_error);
                updateConnectionStatus(false);
            },
            complete: function() {
                $button.html(originalText);
                $button.prop('disabled', false);
            }
        });
    });

    // Handle Printify connection test
    $testPrintifyConnection.on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const originalText = $button.html();

        // Validate inputs
        if (!$printifyApiKey.val() || !$printifyEndpoint.val()) {
            showToast('error', wpwps.i18n.missing_credentials);
            return;
        }

        // Disable button and show loading state
        $button.html('<i class="fas fa-spinner fa-spin"></i> ' + wpwps.i18n.testing_connection);
        $button.prop('disabled', true);

        // Make AJAX request
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_printify_connection',
                nonce: wpwps.nonce,
                api_key: $printifyApiKey.val(),
                endpoint: $printifyEndpoint.val()
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.data.message);
                    populatePrintifyShops(response.data.shops);
                } else {
                    showToast('error', response.data.message);
                }
            },
            error: function() {
                showToast('error', wpwps.i18n.connection_error);
            },
            complete: function() {
                $button.html(originalText);
                $button.prop('disabled', false);
            }
        });
    });

    // Handle OpenAI connection test
    $testOpenAIConnection.on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const originalText = $button.html();

        // Validate inputs
        if (!$openAIApiKey.val()) {
            showToast('error', wpwps.i18n.missing_credentials);
            return;
        }

        // Disable button and show loading state
        $button.html('<i class="fas fa-spinner fa-spin"></i> ' + wpwps.i18n.testing_connection);
        $button.prop('disabled', true);

        // Make AJAX request
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_test_openai_connection',
                nonce: wpwps.nonce,
                api_key: $openAIApiKey.val(),
                tokens: $openAITokens.val(),
                temperature: $openAITemperature.val(),
                spend_cap: $openAISpendCap.val()
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.data.message);
                } else {
                    showToast('error', response.data.message);
                }
            },
            error: function() {
                showToast('error', wpwps.i18n.connection_error);
            },
            complete: function() {
                $button.html(originalText);
                $button.prop('disabled', false);
            }
        });
    });

    // Handle auto sync checkbox changes
    $autoSync.on('change', function() {
        const isChecked = $(this).prop('checked');
        $syncInterval.prop('disabled', !isChecked);
        if (!isChecked) {
            showAlert('info', wpwps.i18n.auto_sync_disabled);
        }
    });

    // Initialize auto sync state
    $syncInterval.prop('disabled', !$autoSync.prop('checked'));

    // Handle form submission
    $form.on('submit', function() {
        const $submitButton = $(this).find('button[type="submit"]');
        const originalText = $submitButton.html();
        
        $submitButton.html('<i class="fas fa-spinner fa-spin"></i> ' + wpwps.i18n.saving);
        $submitButton.prop('disabled', true);

        // Re-enable after short delay to allow form submission
        setTimeout(() => {
            $submitButton.html(originalText);
            $submitButton.prop('disabled', false);
        }, 1000);
    });

    /**
     * Show an alert message
     * @param {string} type Alert type (success, error, info, warning)
     * @param {string} message Message to display
     */
    function showAlert(type, message) {
        const icons = {
            success: 'check-circle',
            error: 'times-circle',
            info: 'info-circle',
            warning: 'exclamation-triangle'
        };

        const $alert = $('<div>')
            .addClass('wpwps-settings-alert')
            .addClass(`wpwps-settings-alert-${type}`)
            .html(`
                <i class="fas fa-${icons[type]}"></i>
                <div>${message}</div>
            `);

        // Remove any existing alerts
        $('.wpwps-settings-alert').remove();

        // Insert new alert at the top of the form
        $form.prepend($alert);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            $alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Show a toast notification
     * @param {string} type Notification type (success, error, info, warning)
     * @param {string} message Notification message
     */
    function showToast(type, message) {
        const icons = {
            success: 'check-circle',
            error: 'times-circle',
            info: 'info-circle',
            warning: 'exclamation-triangle'
        };

        const $toast = $('<div>')
            .addClass('wpwps-toast')
            .addClass(`wpwps-toast-${type}`)
            .html(`
                <i class="fas fa-${icons[type]}"></i>
                <span>${message}</span>
            `);

        // Append toast to container
        $('.wpwps-toast-container').append($toast);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            $toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Populate Printify shops dropdown
     * @param {Array} shops List of shops
     */
    function populatePrintifyShops(shops) {
        $printifyShop.empty();
        shops.forEach(shop => {
            $printifyShop.append(new Option(shop.title, shop.id));
        });
        $printifyShop.prop('disabled', false);
    }

    /**
     * Update the connection status indicator
     * @param {boolean} connected Whether the connection is successful
     */
    function updateConnectionStatus(connected) {
        $connectionStatus
            .removeClass('connected disconnected')
            .addClass(connected ? 'connected' : 'disconnected')
            .html(`
                <i class="fas fa-${connected ? 'check-circle' : 'times-circle'}"></i>
                <span>${connected ? wpwps.i18n.connected : wpwps.i18n.not_connected}</span>
            `);
    }
});