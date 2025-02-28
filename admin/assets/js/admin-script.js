/**
 * JavaScript file: admin-script.js for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 * Time: 02:14:58
 */
(function($) {
    'use strict';
    
    console.log('Printify Sync: Admin script loaded');
    
    const PrintifyAdmin = {
        init: function() {
            console.log('Printify Sync: Admin module initialized');
            // Add any global initialization here
            this.initTooltips();
        },
        
        initTooltips: function() {
            // Add tooltips to elements with data-tooltip attribute
            $('.printify-tooltip').hover(function() {
                const tooltipText = $(this).data('tooltip');
                if (tooltipText) {
                    $('<div class="printify-tooltip-popup">' + tooltipText + '</div>')
                        .appendTo('body')
                        .css({
                            top: $(this).offset().top - 30,
                            left: $(this).offset().left + $(this).width() / 2
                        });
                }
            }, function() {
                $('.printify-tooltip-popup').remove();
            });
        }
    };
    
    $(document).ready(function() {
        PrintifyAdmin.init();
    });
    
})(jQuery);
