class PrintifyAdmin {
    constructor() {
        this.initialize();
    }

    initialize() {
        this.setupMenuHighlighting();
        this.initializeStats();
        this.bindEvents();
    }

    setupMenuHighlighting() {
        const currentUrl = window.location.href;
        const menuItems = document.querySelectorAll('#adminmenu a');

        menuItems.forEach(item => {
            if (currentUrl.includes(item.getAttribute('href'))) {
                item.closest('li').classList.add('current');
            }
        });
    }

    initializeStats() {
        this.updateStats();
        // Update stats every 30 seconds
        setInterval(() => this.updateStats(), 30000);
    }

    updateStats() {
        $.ajax({
            url: wpwpsAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'wpwps_get_dashboard_stats',
                nonce: wpwpsAdmin.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateStatsDisplay(response.data);
                }
            }
        });
    }

    updateStatsDisplay(data) {
        // Update products synced
        $('#products-synced').text(data.products_synced);

        // Update orders processing
        $('#orders-processing').text(data.orders_processing);

        // Update API status
        const apiStatus = $('#api-status');
        apiStatus.find('.status-indicator')
            .removeClass('online offline warning')
            .addClass(data.api_status.state);
        apiStatus.find('.status-text').text(data.api_status.message);
    }

    bindEvents() {
        // Handle navigation tabs
        $('.wpwps-nav-tabs .nav-tab').on('click', (e) => {
            e.preventDefault();
            this.handleTabChange($(e.currentTarget));
        });

        // Handle refresh buttons
        $('.wpwps-refresh').on('click', (e) => {
            e.preventDefault();
            this.updateStats();
        });
    }

    handleTabChange($tab) {
        const target = $tab.data('target');
        
        // Update active tab
        $('.wpwps-nav-tabs .nav-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active');

        // Update content
        $('.wpwps-tab-content').hide();
        $(`#${target}`).show();

        // Update URL without reload
        const newUrl = $tab.attr('href');
        window.history.pushState({ path: newUrl }, '', newUrl);
    }
}

// Initialize admin functionality
jQuery(document).ready(function($) {
    window.printifyAdmin = new PrintifyAdmin();
});