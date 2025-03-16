const WPWPS = window.WPWPS || {};

WPWPS.CurrencySwitcher = {
    init() {
        this.bindEvents();
        this.initializeTooltips();
    },

    bindEvents() {
        $(document).on('click', '.wpwps-currency-switcher .dropdown-item', (e) => {
            e.preventDefault();
            const currency = $(e.currentTarget).data('currency');
            this.switchCurrency(currency);
        });
    },

    initializeTooltips() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    },

    async switchCurrency(currency) {
        try {
            const response = await $.ajax({
                url: wpwpsCurrency.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_switch_currency',
                    nonce: wpwpsCurrency.nonce,
                    currency: currency
                }
            });

            if (response.success) {
                this.showSuccess();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error(response.data.message);
            }
        } catch (error) {
            this.showError(error.message);
        }
    },

    showSuccess() {
        Swal.fire({
            title: 'Currency Updated',
            text: 'Refreshing page...',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1000
        });
    },

    showError(message) {
        Swal.fire({
            title: 'Error',
            text: message,
            icon: 'error',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
};

$(document).ready(() => {
    WPWPS.CurrencySwitcher.init();
});