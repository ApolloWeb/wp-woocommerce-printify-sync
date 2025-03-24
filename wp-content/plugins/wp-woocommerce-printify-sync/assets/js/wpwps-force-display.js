jQuery(document).ready(function($) {
    console.log('WPWPS Force Display script loaded');
    
    // Check if content is empty and add placeholder content
    if ($('.wpwps-content').length === 0 || $('.wpwps-content').is(':empty')) {
        console.log('Content is empty - adding placeholder');
        
        $('.wrap.wpwps-admin').append(
            $('<div class="wpwps-content wpwps-force-display">')
                .append('<h2>Emergency Content</h2>')
                .append('<p>The main content failed to load. This is emergency content.</p>')
                .append('<div class="card p-4 my-4 bg-light">')
                .append('<button class="btn btn-primary" id="reload-page">Reload Page</button>')
        );
        
        $('#reload-page').on('click', function() {
            window.location.reload();
        });
    }
    
    // Add jQuery debugging info
    console.log('jQuery version:', $.fn.jquery);
    console.log('Bootstrap loaded:', typeof $.fn.modal !== 'undefined');
    console.log('ChartJS loaded:', typeof Chart !== 'undefined');
});
