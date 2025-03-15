// Common functionality
const WPWPS = {
    currentTime: wpwps.current_time,
    currentUser: wpwps.current_user,
    
    init() {
        this.initializeCommonEvents();
    },

    initializeCommonEvents() {
        jQuery('.wpwps-timestamp').text(this.currentTime);
        jQuery('.wpwps-user').text(this.currentUser);
    }
};

jQuery(document).ready(() => WPWPS.init());