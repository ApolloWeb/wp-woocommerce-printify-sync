(function($) {
    'use strict';

    const WPWPS_Logs = {
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initModals();
            this.initSyntaxHighlighting();
        },

        bindEvents: function() {
            $('#clearLogs').on('click', this.handleClearLogs.bind(this));
            $('#exportLogs').on('click', this.handleExportLogs.bind(this));
            $('.view-context').on('click', this.handleViewContext.bind(this));
        },

        initTooltips: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        },

        initModals: function() {
            this.contextModal = new bootstrap.Modal('#contextModal');
        },

        initSyntaxHighlighting: function() {
            if (typeof hljs !== 'undefined') {
                hljs.highlightAll();
            }
        },

        handleClearLogs: function(e) {
            e.preventDefault();

            if (!confirm(wpwpsLogs.i18n.confirmClear)) {
                return;
            }

            this.showLoading('#clearLogs');

            $.ajax({
                url: wpwpsLogs.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_clear_logs',
                    nonce: wpwpsLogs.nonce
                },
                success: response => {
                    if (response.success) {
                        this.showToast('success', response.data.message);
                        window.location.reload();
                    } else {
                        this.showToast('error', response.data.message);
                    }
                },
                error: () => {
                    this.showToast('error', wpwpsLogs.i18n.error);
                },
                complete: () => {
                    this.hideLoading('#clearLogs');
                }
            });
        },

        handleExportLogs: function(e) {
            e.preventDefault();
            
            this.showLoading('#exportLogs');

            $.ajax({
                url: wpwpsLogs.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_export_logs',
                    nonce: wpwpsLogs.nonce
                },
                success: response => {
                    if (response.success) {
                        this.showToast('success', response.data.message);
                        window.location.href = response.data.download_url;
                    } else {
                        this.showToast('error', response.data.message);
                    }
                },
                error: () => {
                    this.showToast('error', wpwpsLogs.i18n.error);
                },
                complete: () => {
                    this.hideLoading('#exportLogs');
                }
            });
        },

        handleViewContext: function(e) {
            e.preventDefault();
            const $row = $(e.currentTarget).closest('tr');
            const context = $row.find('.log-context').html();
            
            $('.context-content').html(context);
            this.contextModal.show();
            
            if (typeof hljs !== 'undefined') {
                hljs.highlightElement($('.context-content')[0]);
            }
        },

        showToast: function(type, message) {
            const toast = $(`
                <div class="toast align-items-center text-white bg-${type}" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `);

            $('.toast-container').append(toast);
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();

            toast.on('hidden.bs.toast', () => toast.remove());
        },

        showLoading: function(selector) {
            const $el = $(selector);
            $el.prop('disabled', true)
               .addClass('position-relative')
               .append('<span class="spinner-border spinner-border-sm position-absolute end-0 me-2"></span>');
        },

        hideLoading: function(selector) {
            const $el = $(selector);
            $el.prop('disabled', false)
               .removeClass('position-relative')
               .find('.spinner-border')
               .remove();
        }
    };

    $(document).ready(() => WPWPS_Logs.init());

})(jQuery);