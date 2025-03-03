document.addEventListener('DOMContentLoaded', function() {
    // Set current datetime
    const currentDatetimeElements = document.querySelectorAll('#current-datetime');
    currentDatetimeElements.forEach(element => {
        element.innerText = "2025-03-02 19:45:38 UTC";
    });
    
    // Tab navigation - fixed implementation
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.settings-tab');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(tab => tab.classList.remove('active'));
            
            // Add active class to current button and tab
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            
            // Log for debugging
            console.log('Tab clicked:', tabId);
        });
    });
    
    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const inputField = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (inputField.type === 'password') {
                inputField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                inputField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Save settings via AJAX
    const saveButtons = document.querySelectorAll('.save-setting');
    saveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const fieldName = this.dataset.field;
            const inputType = this.dataset.type || 'input';
            const statusIndicator = document.querySelector(`#status-${fieldName} .status-indicator`);
            let fieldValue;
            
            // Get the value based on input type
            if (inputType === 'checkbox') {
                const checkbox = document.getElementById(this.dataset.input);
                fieldValue = checkbox.checked ? 'yes' : 'no';
            } else if (inputType === 'radio') {
                const selectedRadio = document.querySelector(`input[name="${fieldName}"]:checked`);
                fieldValue = selectedRadio ? selectedRadio.value : '';
            } else {
                const inputField = document.getElementById(this.dataset.input);
                fieldValue = inputField.value;
            }
            
            // Show saving status
            statusIndicator.textContent = 'Saving...';
            statusIndicator.classList.add('saving');
            
            // AJAX request to save setting
            const data = new FormData();
            data.append('action', 'save_printify_setting');
            data.append('security', printifySyncData.nonce);
            data.append('field_name', fieldName);
            data.append('field_value', fieldValue);
            
            fetch(printifySyncData.ajaxUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusIndicator.textContent = 'Saved';
                    statusIndicator.classList.remove('saving');
                    statusIndicator.classList.add('success');
                    
                    // Show the success notice
                    const notice = document.getElementById('settings-notice');
                    notice.classList.add('show');
                    
                    // Hide the notice after 3 seconds
                    setTimeout(() => {
                        notice.classList.remove('show');
                    }, 3000);
                    
                    // Clear the status after 5 seconds
                    setTimeout(() => {
                        statusIndicator.textContent = '';
                        statusIndicator.classList.remove('success');
                    }, 5000);
                } else {
                    statusIndicator.textContent = 'Error';
                    statusIndicator.classList.remove('saving');
                    statusIndicator.classList.add('error');
                    
                    // Clear the status after 5 seconds
                    setTimeout(() => {
                        statusIndicator.textContent = '';
                        statusIndicator.classList.remove('error');
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error saving setting:', error);
                statusIndicator.textContent = 'Error';
                statusIndicator.classList.remove('saving');
                statusIndicator.classList.add('error');
                
                // Clear the status after 5 seconds
                setTimeout(() => {
                    statusIndicator.textContent = '';
                    statusIndicator.classList.remove('error');
                }, 5000);
            });
        });
    });
    
    // Initialize the first tab (API Keys)
    if (document.querySelector('.tab-btn.active')) {
        const activeTabId = document.querySelector('.tab-btn.active').getAttribute('data-tab');
        document.getElementById(activeTabId).classList.add('active');
    }
});