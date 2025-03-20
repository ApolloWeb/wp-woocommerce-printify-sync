/**
 * Debug helper functions to diagnose event binding issues
 */
jQuery(document).ready(function($) {
    // Check if jQuery is working
    console.log('jQuery version:', $.fn.jquery);
    
    // Log all click events to see if they're being captured
    $(document).on('click', '*', function(e) {
        console.log('Element clicked:', this, 'Data attributes:', $(this).data());
    });
    
    // Specifically watch pagination clicks
    $(document).on('click', '.page-link', function(e) {
        console.log('Pagination clicked:', $(this).data('page'), 'Event:', e);
    });
    
    // Add visible indicator for clickable elements
    $('.page-link, button').css('position', 'relative').append('<span style="position:absolute;top:0;right:0;background:green;color:white;font-size:8px;padding:1px;">Clickable</span>');
    
    // Check for any JS errors
    window.onerror = function(message, source, lineno, colno, error) {
        console.error('JavaScript error:', message, 'Source:', source, 'Line:', lineno, 'Error:', error);
    };
});
