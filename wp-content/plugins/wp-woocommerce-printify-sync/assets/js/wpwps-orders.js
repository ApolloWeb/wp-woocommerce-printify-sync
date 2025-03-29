document.addEventListener('DOMContentLoaded', function() {
    const refreshButton = document.getElementById('refreshOrders');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            const button = this;
            button.disabled = true;
            
            jQuery.ajax({
                url: wpwpsOrders.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpwps_refresh_orders',
                    nonce: wpwpsOrders.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to refresh orders');
                    }
                },
                error: function() {
                    alert('Failed to refresh orders');
                },
                complete: function() {
                    button.disabled = false;
                }
            });
        });
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});