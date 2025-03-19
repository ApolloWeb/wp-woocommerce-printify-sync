jQuery(document).ready(function($) {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();

    // Close alerts
    $('.alert .close').on('click', function() {
        $(this).closest('.alert').fadeOut();
    });

    // Common functionality
    console.log('WPWPS Common JS loaded');
});
