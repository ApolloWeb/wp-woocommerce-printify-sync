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

    // Define formatCurrency globally
    window.formatCurrency = function(amount) {
        // Ensure amount is a number
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount)) return 'N/A';
        
        const currency = window.wpwps_data.currency || 'GBP';
        // Use WooCommerce's currency symbols or fall back to defaults
        const symbols = window.wpwps_data.currency_symbols || {
            'GBP': '£',
            'USD': '$',
            'EUR': '€'
        };
        
        // Check if the amount needs to be divided by 100
        const valueToFormat = numAmount.toString().includes('.') ? 
            numAmount : 
            (numAmount / 100);
        
        // Format the amount with proper decimal places
        return `${symbols[currency] || symbols['GBP']}${valueToFormat.toFixed(2)}`;
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
