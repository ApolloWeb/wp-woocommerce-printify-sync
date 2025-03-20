/**
 * Debug helper functions to diagnose event binding issues
 */
jQuery(document).ready(function($) {
    // Check if jQuery is working
    console.log('jQuery version:', $.fn.jquery);
    
    // Log all click events to see if they're being captured
    $(document).on('click', '*', function(e) {
        const tagName = $(this).prop('tagName').toLowerCase();
        const id = $(this).attr('id') || 'no-id';
        const classes = $(this).attr('class') || 'no-class';
    });
    
    // Check for any JS errors
    window.onerror = function(message, source, lineno, colno, error) {
        console.error('JavaScript error:', message, 'Source:', source, 'Line:', lineno, 'Error:', error);
    };
});
