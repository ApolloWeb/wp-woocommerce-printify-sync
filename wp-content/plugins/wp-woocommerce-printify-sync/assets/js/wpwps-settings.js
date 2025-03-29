(function($) {
    'use strict';

    const WPWPSSettings = {
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },

        bindEvents: function() {
            $('.wpwps-test-api-key').on('click', this.testApiConnection);
            $('input[name="wpwps_settings[sync_products]"]').on('change', this.toggleSyncOptions);
            $('input[name="wpwps_settings[logging_enabled]"]').on('change', this.toggleLoggingOptions);
            $('#saveSettingsButton').on('click', function(e) {
                e.preventDefault();
                const button = $(this);
                button.prop('disabled', true);

                const settings = {
                    apiKey: $('#apiKey').val(),
                    endpoint: $('#endpoint').val(),
                };

                $.ajax({
                    url: wpwpsSettings.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'save_settings',
                        nonce: wpwpsSettings.nonce,
                        settings: settings,
                    },
                    success: function(response) {
                        if (response.success) {
                            WPWPSSettings.showToast('success', response.data.message);
                        } else {
                            WPWPSSettings.showToast('error', response.data.message);
                        }
                    },
                    error: function() {
                        WPWPSSettings.showToast('error', 'Failed to save settings');
                    },
                    complete: function() {
                        button.prop('disabled', false);
                    },
                });
            });
        },

        initTooltips: function() {
            $('.wpwps-tooltip').tipTip({
                attribute: 'data-tip',
                fadeIn: 50,
                fadeOut: 50,
                delay: 200
            });
        },

        testApiConnection: function(e) {
            e.preventDefault();
            const button = $(this);
            const apiKey = $('input[name="wpwps_settings[printify_api_key]"]').val();

            if (!apiKey) {
                WPWPSSettings.showToast('error', 'Please enter an API key.');
                return;
            }

            button.prop('disabled', true);
            
            $.ajax({
                url: wpwps_settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_test_api_connection',
                    nonce: wpwps_settings.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        WPWPSSettings.showToast('success', 'Connection successful.');
                    } else {
                        WPWPSSettings.showToast('error', response.data.message || 'Connection failed.');
                    }
                },
                error: function() {
                    WPWPSSettings.showToast('error', 'Connection failed.');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        },

        toggleSyncOptions: function() {
            const isChecked = $(this).is(':checked');
            $('[data-depends-on="sync_products"]').toggle(isChecked);
        },

        toggleLoggingOptions: function() {
            const isChecked = $(this).is(':checked');
            $('[data-depends-on="logging_enabled"]').toggle(isChecked);
        },

        showToast: function(type, message) {
            const toast = $(
                `<div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>`
            );

            $('#wpwps-toast-container').append(toast);
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();

            toast.on('hidden.bs.toast', function() {
                $(this).remove();
            });
        }
    };

    $(document).ready(function() {
        WPWPSSettings.init();
    });
})(jQuery);