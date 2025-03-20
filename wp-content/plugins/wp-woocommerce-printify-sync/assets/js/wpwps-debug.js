jQuery(document).ready(function($) {
    console.log('WPWPS Debug loaded');
    
    // Show all available data
    console.log('wpwps_data:', window.wpwps_data);
    
    // Check for the button
    const clearButton = $('#clear-all-data');
    console.log('Clear Data button found:', clearButton.length > 0);
    
    // Add direct click handler for testing
    clearButton.on('click', function() {
        console.log('Clear button clicked directly');
    });
    
    // Monitor all AJAX calls
    $(document).ajaxSend(function(e, xhr, settings) {
        console.log('AJAX call sent:', settings);
    });
    
    $(document).ajaxError(function(e, xhr, settings, error) {
        console.error('AJAX error:', error, xhr.responseText);
    });
    
    // Test AJAX functionality
    $('#wpwps-test-ajax').on('click', function() {
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'test_connection',
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                console.log('Test AJAX response:', response);
                alert('AJAX test completed, check console');
            },
            error: function(xhr, status, error) {
                console.error('Test AJAX error:', xhr, status, error);
                alert('AJAX test failed: ' + error);
            }
        });
    });
});
