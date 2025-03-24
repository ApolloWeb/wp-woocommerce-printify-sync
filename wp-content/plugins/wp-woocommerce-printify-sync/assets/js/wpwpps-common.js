jQuery(document).ready(function($) {
    console.log('WP WooCommerce Printify Sync common JS loaded');

    // Common notification handling
    window.wpwpps = {
        notify: function(message, type = 'info') {
            $('.wpwpps-notice').html(message).attr('class', 'wpwpps-notice ' + type).show();
        }
    };

    // Direct event handler for user avatar dropdown
    $(document).on('click', '.wpwpps-user-avatar', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Toggle the dropdown
        var dropdown = $(this).closest('.wpwpps-user-profile').find('.wpwpps-dropdown');
        dropdown.toggleClass('show');
        
        // Log for debugging
        console.log('User avatar clicked, dropdown toggled');
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.wpwpps-user-profile').length) {
            $('.wpwpps-dropdown').removeClass('show');
        }
    });
});
