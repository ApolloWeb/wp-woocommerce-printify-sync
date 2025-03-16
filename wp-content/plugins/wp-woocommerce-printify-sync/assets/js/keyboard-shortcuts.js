class KeyboardShortcuts {
    constructor() {
        this.shortcuts = {
            's': this.syncAll,
            'n': this.newProduct,
            'f': this.toggleFilters,
            'd': this.toggleDarkMode,
            'h': this.showHelp
        };

        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('keydown', (e) => {
            // Only trigger if Ctrl/Cmd + key is pressed
            if ((e.ctrlKey || e.metaKey) && this.shortcuts[e.key]) {
                e.preventDefault();
                this.shortcuts[e.key]();
            }
        });
    }

    syncAll() {
        // Trigger sync all action
        document.getElementById('syncAll')?.click();
    }

    newProduct() {
        // Open new product form
        document.getElementById('newProduct')?.click();
    }

    toggleFilters() {
        // Toggle filters visibility
        document.querySelector('.printify-filters')?.classList.toggle('show');
    }

    toggleDarkMode() {
        document.body.classList.toggle('printify-dark-mode');
        localStorage.setItem('printifyDarkMode', 
            document.body.classList.contains('printify-dark-mode')
        );
    }

    showHelp() {
        // Show keyboard shortcuts help modal
        const modal = new bootstrap.Modal(document.getElementById('shortcutsHelp'));
        modal.show();
    }
}

// Initialize on document load
document.addEventListener('DOMContentLoaded', () => {
    new KeyboardShortcuts();
    
    // Restore dark mode preference
    if (localStorage.getItem('printifyDarkMode') === 'true') {
        document.body.classList.add('printify-dark-mode');
    }
});