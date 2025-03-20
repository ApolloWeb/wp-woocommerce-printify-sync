(function($) {
    // Wait for document to be fully loaded
    $(function() {
        console.log('Clear Data script loaded');
        
        // Find the button
        const clearButton = document.getElementById('clear-all-data');
        console.log('Found clear data button:', clearButton);
        
        if (clearButton) {
            // Add direct DOM event listener (no jQuery)
            clearButton.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Clear data button clicked (direct DOM handler)');
                
                if (!confirm('WARNING: This will delete ALL Printify products and orders from WooCommerce. This action cannot be undone. Continue?')) {
                    return;
                }
                
                if (!confirm('Are you absolutely sure? All imported products and orders will be permanently deleted.')) {
                    return;
                }
                
                // Visual feedback
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                this.disabled = true;
                
                // Make sure we have wpwps_data
                if (typeof wpwps_data === 'undefined') {
                    alert('Error: Required data is missing. Please reload the page and try again.');
                    this.innerHTML = originalText;
                    this.disabled = false;
                    return;
                }
                
                // Log for debugging
                console.log('AJAX URL:', wpwps_data.ajax_url);
                console.log('Nonce:', wpwps_data.nonce);
                
                // Make AJAX request using vanilla JS for reliability
                const xhr = new XMLHttpRequest();
                xhr.open('POST', wpwps_data.ajax_url);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        console.log('Response received:', xhr.responseText);
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert('Success: ' + response.data.message);
                                // Update the UI to show zeroes
                                document.querySelectorAll('.wpwps-stat-card .h2').forEach(el => {
                                    el.textContent = '0';
                                });
                            } else {
                                alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            alert('Invalid response received from server');
                        }
                    } else {
                        console.error('Request failed: ' + xhr.status);
                        alert('Request failed: ' + xhr.status);
                    }
                    
                    // Reset button state
                    clearButton.innerHTML = originalText;
                    clearButton.disabled = false;
                };
                
                xhr.onerror = function() {
                    console.error('Request failed');
                    alert('Network error occurred');
                    clearButton.innerHTML = originalText;
                    clearButton.disabled = false;
                };
                
                // Send the request
                xhr.send(new URLSearchParams({
                    'action': 'printify_sync',
                    'action_type': 'clear_all_data',
                    'nonce': wpwps_data.nonce
                }).toString());
            });
            
            console.log('Event listener added to Clear All Data button');
        } else {
            console.error('Clear All Data button not found in the DOM');
        }
    });
})(jQuery);
