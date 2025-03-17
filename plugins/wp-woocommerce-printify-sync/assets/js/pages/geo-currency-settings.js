const WPWPS = window.WPWPS || {};

WPWPS.GeoCurrencySettings = {
    init() {
        this.bindEvents();
    },

    bindEvents() {
        // Toggle password visibility
        $('.toggle-password').on('click', (e) => {
            const button = $(e.currentTarget);
            const input = button.closest('.input-group').find('input');
            const icon = button.find('i');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Test buttons
        $('#test-geolocation').on('click', () => this.testGeolocation());
        $('#test-currency').on('click', () => this.testCurrency());

        // Form submission
        $('#geo-currency-settings').on('submit', (e) => this.handleSubmit(e));
    },

    async testGeolocation() {
        try {
            const response = await $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wpwps_test_geolocation',
                    nonce: wpwpsData.nonce
                }
            });

            this.showTestResults('Geolocation Test', response);
        } catch (error) {
            this.showError('Geolocation test failed:', error);
        }
    },

    async testCurrency() {
        try {
            const response = await $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wpwps_test_currency',
                    nonce: wpwpsData.nonce
                }
            });

            this.showTestResults('Currency Test', response);
        } catch (error) {
            this.showError('Currency test failed:', error);
        }
    },

    showTestResults(title, data) {
        $('#test-results-modal .modal-title').text(title);
        $('#test-results').text(JSON.stringify(data, null, 2));
        new bootstrap.Modal('#test-results-modal').show();
    },

    showError(message, error) {
        Swal.fire({
            title: 'Error',
            text: `${message} ${error.responseJSON?.message || error.statusText}`,
            icon: 'error'
        });
    },

    async handleSubmit(e) {
        e.preventDefault();
        const form = $(e.currentTarget);

        try {
            await $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: form.serialize() + '&action=wpwps_save_geo_currency_settings&nonce=' + wpwpsData.nonce
            });

            Swal.fire({
                title: 'Success',
                text: 'Settings saved successfully',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } catch (error) {
            Swal.fire({
                title: 'Error',
                text: 'Failed to save settings',
                icon: 'error'
            });
        }
    }
};

$(document).ready(() => {
    WPWPS.GeoCurrencySettings.init();
});