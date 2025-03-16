class StorageSettings {
    constructor() {
        this.bindEvents();
    }

    bindEvents() {
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', (e) => {
                const input = e.target.closest('.input-group').querySelector('input');
                const icon = e.target.closest('button').querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });

        // Test connections
        document.querySelectorAll('.test-connection').forEach(button => {
            button.addEventListener('click', (e) => {
                this.testConnection(e.target.dataset.action);
            });
        });

        // Save settings with encryption
        document.getElementById('printify-settings-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveSettings(new FormData(e.target));
        });
    }

    async testConnection(action) {
        const button = document.querySelector(`[data-action="${action}"]`);
        const originalText = button.innerHTML;
        
        try {
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
            button.disabled = true;

            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: action,
                    nonce: printifySync.nonce,
                    ...this.getConnectionParams(action)
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification('success', data.message);
            } else {
                this.showNotification('error', data.message);
            }

        } catch (error) {
            this.showNotification('error', 'Connection test failed');
            console.error('Test connection error:', error);
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    getConnectionParams(action) {
        const params = {};
        
        if (action === 'test_google_drive') {
            ['client_id', 'client_secret', 'refresh_token', 'folder_id'].forEach(field => {
                params[field] = document.getElementById(`google_drive_${field}`).value;
            });
        } else if (action === 'test_r2_connection') {
            ['account_id', 'access_key_id', 'secret_access_key', 'bucket_name', 'bucket_region'].forEach(field => {
                params[field] = document.getElementById(`r2_${field}`).value;
            });
        }

        return params;
    }

    async saveSettings(formData) {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'save_storage_settings',
                    nonce: printifySync.nonce,
                    data: Object.fromEntries(formData)
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification('success', 'Settings saved successfully');
            } else {
                this.showNotification('error', 'Failed to save settings');
            }

        } catch (error) {
            this.showNotification('error', 'Failed to save settings');
            console.error('Save settings error:', error);
        }
    }

    showNotification(type, message) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'}`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.querySelector('.toast-container').appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
}

// Initialize on document load
document.addEventListener('DOMContentLoaded', () => {
    new StorageSettings();
});