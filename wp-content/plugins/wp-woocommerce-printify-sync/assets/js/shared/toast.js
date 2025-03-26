/**
 * Toast Notification Module
 */
const WPWPSToast = {
    toast: null,

    init() {
        this.toast = new bootstrap.Toast(document.getElementById('wpwps-toast'));
    },

    show(title, message, type = 'success') {
        const toastEl = document.getElementById('wpwps-toast');
        const titleEl = document.getElementById('toast-title');
        const messageEl = document.getElementById('toast-message');

        // Reset classes
        toastEl.className = 'toast';
        toastEl.classList.add(type === 'error' ? 'bg-danger' : 'bg-success', 'text-white');

        // Set content
        titleEl.textContent = title;
        messageEl.textContent = message;

        // Show toast
        this.toast.show();
    },

    success(title, message) {
        this.show(title, message, 'success');
    },

    error(title, message) {
        this.show(title, message, 'error');
    }
};

// Export for use in other files
window.WPWPSToast = WPWPSToast;