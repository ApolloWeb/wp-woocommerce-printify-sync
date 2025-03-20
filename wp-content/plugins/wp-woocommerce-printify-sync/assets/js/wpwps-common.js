// Define formatCurrency globally first, then initialize other jQuery functions
(function(window, $) {
    // Ensure wpwps_data is available
    if (typeof window.wpwps_data === 'undefined') {
        window.wpwps_data = {
            ajax_url: (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php',
            nonce: '',
            currency: 'GBP', // Default to GBP
            currency_symbols: {
                'GBP': '£',
                'USD': '$',
                'EUR': '€'
            }
        };
        console.warn('wpwps_data not found, created default object');
    }

    // Define global formatCurrency function - COMPLETE REWRITE
    window.formatCurrency = function(amount) {
        // Log for debugging
        console.log('formatCurrency input:', amount, typeof amount);
        
        // Ensure we have a number
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount)) {
            console.warn('Invalid amount for formatCurrency:', amount);
            return '£0.00';
        }
        
        // Get currency symbol
        const currency = window.wpwps_data?.currency || 'GBP';
        const symbols = window.wpwps_data?.currency_symbols || {
            'GBP': '£',
            'USD': '$',
            'EUR': '€'
        };
        
        // Format to 2 decimal places - NO dividing by 100 here
        // The server already handled that conversion
        return symbols[currency] + numAmount.toFixed(2);
    };

    console.log('WPWPS Currency formatter loaded');

    // Document ready handler
    $(function() {
        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();

        // Initialize popovers
        $('[data-bs-toggle="popover"]').popover();

        // Close alerts
        $('.alert .close').on('click', function() {
            $(this).closest('.alert').fadeOut();
        });

        console.log('WPWPS Common JS loaded, ajax_url:', window.wpwps_data.ajax_url);
    });
})(window, jQuery);
