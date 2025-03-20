jQuery(document).ready(function($) {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();

    // Close alerts
    $('.alert .close').on('click', function() {
        $(this).closest('.alert').fadeOut();
    });

    // Ensure wpwps_data is always available
    if (typeof wpwps_data === 'undefined') {
        window.wpwps_data = {
            ajax_url: ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: ''
        };
        console.warn('wpwps_data not found, created default object');
    }

    console.log('WPWPS Common JS loaded, ajax_url:', wpwps_data.ajax_url);
});
