document.addEventListener('DOMContentLoaded', function() {
    // New ticket form handling
    const newTicketForm = document.getElementById('newTicketForm');
    if (newTicketForm) {
        newTicketForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            jQuery.ajax({
                url: wpwpsTickets.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpwps_create_ticket',
                    nonce: wpwpsTickets.nonce,
                    subject: form.querySelector('#subject').value,
                    message: form.querySelector('#message').value,
                    order_id: form.querySelector('#order_id').value
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = '?page=wpwps-tickets&action=view&ticket_id=' + response.data.ticket.id;
                    } else {
                        alert('Failed to create ticket');
                    }
                },
                error: function() {
                    alert('Failed to create ticket');
                },
                complete: function() {
                    submitButton.disabled = false;
                }
            });
        });
    }

    // Reply form handling
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            jQuery.ajax({
                url: wpwpsTickets.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpwps_update_ticket',
                    nonce: wpwpsTickets.nonce,
                    ticket_id: form.querySelector('[name="ticket_id"]').value,
                    response: form.querySelector('#response').value,
                    status: form.querySelector('#status').value
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to update ticket');
                    }
                },
                error: function() {
                    alert('Failed to update ticket');
                },
                complete: function() {
                    submitButton.disabled = false;
                }
            });
        });
    }

    // AI response generation
    const generateButton = document.getElementById('generateResponse');
    if (generateButton) {
        generateButton.addEventListener('click', function() {
            const button = this;
            const responseField = document.getElementById('response');
            if (!responseField) return;

            button.disabled = true;
            
            jQuery.ajax({
                url: wpwpsTickets.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpwps_generate_response',
                    nonce: wpwpsTickets.nonce,
                    ticket_id: document.querySelector('[name="ticket_id"]').value
                },
                success: function(response) {
                    if (response.success) {
                        responseField.value = response.data.response;
                    } else {
                        alert('Failed to generate response');
                    }
                },
                error: function() {
                    alert('Failed to generate response');
                },
                complete: function() {
                    button.disabled = false;
                }
            });
        });
    }
});