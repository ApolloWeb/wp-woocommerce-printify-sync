/**
 * Admin JavaScript for WP WooCommerce Printify Sync
 */
(function($) {
    'use strict';
    
    // Document ready
    $(function() {
        // Initialize tooltips if any
        if (typeof $.fn.tooltip === 'function') {
            $('.wpwprintifysync-tooltip').tooltip();
        }
        
        // Toggle API key visibility
        $('.wpwprintifysync-toggle-visibility').on('click', function(e) {
            e.preventDefault();
            
            var target = $($(this).data('target'));
            var type = target.attr('type');
            
            if (type === 'password') {
                target.attr('type', 'text');
                $(this).find('.dashicons')
                    .removeClass('dashicons-visibility')
                    .addClass('dashicons-hidden');
            } else {
                target.attr('type', 'password');
                $(this).find('.dashicons')
                    .removeClass('dashicons-hidden')
                    .addClass('dashicons-visibility');
            }
        });
    });
})(jQuery);