/**
 * AJAX debugging helper
 */
jQuery(document).ready(function($) {
    // Intercept AJAX events for debugging
    $(document).ajaxSend(function(event, jqXHR, settings) {
        console.log('AJAX Request Sent:', {
            url: settings.url,
            type: settings.type,
            data: settings.data
        });
    });
    
    $(document).ajaxSuccess(function(event, jqXHR, settings, data) {
        console.log('AJAX Request Successful:', {
            url: settings.url,
            type: settings.type,
            data: settings.data,
            response: data
        });
    });
    
    $(document).ajaxError(function(event, jqXHR, settings, error) {
        console.error('AJAX Request Failed:', {
            url: settings.url,
            type: settings.type,
            data: settings.data,
            status: jqXHR.status,
            statusText: jqXHR.statusText,
            error: error,
            responseText: jqXHR.responseText
        });
    });
    
    // Check if wpwps_data is available
    if (typeof wpwps_data !== 'undefined') {
        console.log('wpwps_data is available:', wpwps_data);
    } else {
        console.warn('wpwps_data is not defined!');
    }
    
    // List all buttons on the page for debugging
    setTimeout(function() {
        console.log('All buttons on page:');
        $('button').each(function() {
            console.log(`- Button #${$(this).attr('id') || 'no-id'} with text: ${$(this).text().trim()}`);
        });
    }, 1000);
});
