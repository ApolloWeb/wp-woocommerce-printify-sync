jQuery(document).ready(function($) {
    // Webhook creation handler
    $('#create-webhook-btn').on('click', function() {
        const webhookUrl = $('#webhook-url').val();
        const events = [];
        
        $('.webhook-event-checkbox:checked').each(function() {
            events.push($(this).val());
        });
        
        if (!webhookUrl || events.length === 0) {
            alert('Please provide a webhook URL and select at least one event');
            return;
        }
        
        // Show loading indicator
        $('#webhook-status').html('<span class="loading">Creating webhook...</span>');
        
        // Send AJAX request to create webhook
        wp.ajax.post('wpwprintifysync_create_webhook', {
            nonce: wpwprintifysyncPostman.nonce,
            url: webhookUrl,
            events: events,
            description: $('#webhook-description').val()
        }).done(function(response) {
            // Update UI with webhook ID and status
            $('#webhook-status').html('<span class="success">Webhook created! ID: ' + response.id + '</span>');
            $('#webhook-id').val(response.id);
            
            // Add webhook to history
            addWebhookToHistory(response);
            
            // Enable testing
            $('#test-webhook-btn').prop('disabled', false);
        }).fail(function(error) {
            $('#webhook-status').html('<span class="error">Error: ' + error.message + '</span>');
        });
    });
    
    // Webhook testing functionality
    $('#test-webhook-btn').on('click', function() {
        // Implementation for webhook testing
    });
});