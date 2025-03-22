(function($) {
    'use strict';

    // Toast notification system
    const showToast = (message, type = 'success') => {
        const toast = `
            <div class="wpps-toast position-fixed top-0 end-0 p-3" style="z-index: 1056">
                <div class="toast align-items-center text-white bg-${type}" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>`;
        
        $(toast).appendTo('body').find('.toast').toast('show');
    };

    // Chart.js defaults
    Chart.defaults.font.family = 'Inter';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 6;

    // Sidebar toggle
    $('.wpps-sidebar-toggle').on('click', function() {
        $('.wpps-sidebar').toggleClass('collapsed');
        $('.wpps-main-content').toggleClass('expanded');
    });

    // Initialize tooltips and popovers
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));

    // Export functions
    window.wppsAdmin = {
        showToast: showToast
    };

})(jQuery);
