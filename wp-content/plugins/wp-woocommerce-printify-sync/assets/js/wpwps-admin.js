(function($) {
    'use strict';

    const WPWPS = {
        init() {
            this.initSidebar();
            this.initTooltips();
        },

        initSidebar() {
            $('.wpwps-sidebar-toggle').on('click', () => {
                $('.wpwps-sidebar').toggleClass('collapsed');
                $('.wpwps-main-content').toggleClass('expanded');
            });
        },

        initTooltips() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        },

        // AJAX helper
        api: {
            post(action, data = {}) {
                return $.ajax({
                    url: wppsAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: `wpps_${action}`,
                        _ajax_nonce: wppsAdmin.nonce,
                        ...data
                    }
                });
            }
        },

        // Toast notifications
        toast: {
            show(message, type = 'success') {
                const toast = `
                    <div class="wpwps-toast position-fixed top-0 end-0 p-3" style="z-index: 1056">
                        <div class="toast align-items-center text-white bg-${type}" role="alert">
                            <div class="d-flex">
                                <div class="toast-body">${message}</div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                                        data-bs-dismiss="toast"></button>
                            </div>
                        </div>
                    </div>`;
                
                $(toast).appendTo('body').find('.toast').toast('show');
            }
        }
    };

    // Initialize on DOM ready
    $(document).ready(() => WPWPS.init());

    // Export for other modules
    window.WPWPS = WPWPS;

})(jQuery);
