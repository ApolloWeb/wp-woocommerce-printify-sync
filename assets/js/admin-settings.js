/**
 * Printify Sync Settings Management
 * Handles AJAX form submissions and UI interactions
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    tippy('[data-tippy-content]', {
        theme: 'light-border',
        arrow: true
    });
    
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
    
    // Close notification
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('close-notification')) {
            const notification = e.target.closest('.settings-notification');
            notification.classList.add('hidden');
        }
    });
    
    // Save settings functions
    const saveButtons = document.querySelectorAll('[id^="save-"][id$="-settings"]');
    saveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const section = this.getAttribute('data-section');
            saveSettings(section, this);
        });
    });
    
    // Test connection functions
    const testButtons = document.querySelectorAll('[id^="test-"][id$="-api"]');
    testButtons.forEach(button => {
        button.addEventListener('click', function() {
            const section = this.id.replace('test-', '').replace('-api', '');
            testConnection(section, this);
        });
    });
    
    /**
     * Save settings via AJAX
     * 
     * @param {string} section The settings section being saved
     * @param {HTMLElement} button The button element that was clicked
     */
    function saveSettings(section, button) {
        // Show loading state
        const originalButtonText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        button.disabled = true;
        
        // Collect form data based on section
        let formData = new FormData();
        formData.append('action', 'printify_sync_save_settings');
        formData.append('nonce', printifySyncSettings.nonce);
        formData.append('section', section);
        
        // Add section-specific fields
        switch(section) {
            case 'printify':
                formData.append('api_key', document.getElementById('printify_api_key').value);
                formData.append('endpoint', document.getElementById('printify_endpoint').value);
                break;
            case 'geolocation':
                formData.append('api_key', document.getElementById('geolocation_api_key').value);
                break;
            case 'currency':
                formData.append('api_key', document.getElementById('currency_api_key').value);
                break;
            case 'postman':
                formData.append('api_key', document.getElementById('postman_api_key').value);
                break;
            default:
                showNotification('error', 'Unknown settings section');
                return;
        }
        
        // Send AJAX request
        fetch(printifySyncSettings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.data.message);
                
                // Update field with masked value if provided
                if (data.data.masked_value && section !== 'printify') {
                    document.getElementById(`${section}_api_key`).value = data.data.masked_value;
                } else if (data.data.masked_api_key) {
                    document.getElementById('printify_api_key').value = data.data.masked_api_key;
                }
            } else {
                showNotification('error', data.data);
            }
        })
        .catch(error => {
            showNotification('error', 'An error occurred while saving settings');
            console.error('Settings save error:', error);
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalButtonText;
            button.disabled = false;
        });
    }
    
    /**
     * Test API connection
     * 
     * @param {string} section The API section to test
     * @param {HTMLElement} button The button element that was clicked
     */
    function testConnection(section, button) {
        // Show loading state
        const originalButtonText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        button.disabled = true;
        
        // Prepare request data
        let formData = new FormData();
        formData.append('action', 'printify_sync_test_connection');
        formData.append('nonce', printifySyncSettings.nonce);
        formData.append('section', section);
        
        // Send AJAX request
        fetch(printifySyncSettings.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.data.message);
                button.innerHTML = '<i class="fas fa-check"></i> Connection Success';
                setTimeout(() => {
                    button.innerHTML = originalButtonText;
                    button.disabled = false;
                }, 2000);
            } else {
                showNotification('error', data.data);
                button.innerHTML = '<i class="fas fa-times"></i> Connection Failed';
                setTimeout(() => {
                    button.innerHTML = originalButtonText;
                    button.disabled = false;
                }, 2000);
            }
        })
        .catch(error => {
            showNotification('error', 'An error occurred while testing connection');
            console.error('Connection test error:', error);
            button.innerHTML = originalButtonText;
            button.disabled = false;
        });
    }
    
    /**
     * Show notification message
     * 
     * @param {string} type The notification type ('success' or 'error')
     * @param {string} message The message to display
     */
    function showNotification(type, message) {
        const notificationEl = document.getElementById('settings-notification');
        notificationEl.className = 'settings-notification ' + type;
        
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        notificationEl.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
            <button type="button" class="close-notification">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Scroll to notification
        notificationEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (!notificationEl.classList.contains('hidden')) {
                notificationEl.classList.add('hidden');
            }
        }, 5000);
    }
});