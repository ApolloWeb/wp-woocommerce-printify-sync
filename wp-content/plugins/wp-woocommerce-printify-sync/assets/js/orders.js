(function($) {
    'use strict';

    const WPWPS_Orders = {
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initModals();
        },

        bindEvents: function() {
            $('.sync-order').on('click', this.handleSync.bind(this));
            $('.cancel-order').on('click', this.handleCancel.bind(this));
            $('.refund-order').on('click', this.handleRefund.bind(this));
            $('#confirmRefund').on('click', this.processRefund.bind(this));
            $('.view-tracking').on('click', this.viewTracking.bind(this));
            $('#syncAllOrders').on('click', this.syncAll.bind(this));
        },

        initTooltips: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        },

        initModals: function() {
            this.refundModal = new bootstrap.Modal('#refundModal');
            this.trackingModal = new bootstrap.Modal('#trackingModal');
        },

        handleSync: function(e) {
            e.preventDefault();
            const $row = $(e.currentTarget).closest('tr');
            const orderId = $row.data('order-id');

            this.syncOrder(orderId)
                .then(response => {
                    this.showToast('success', wpwpsOrders.i18n.success);
                    this.updateOrderRow($row, response.data.order);
                })
                .catch(error => {
                    this.showToast('error', error.message);
                });
        },

        handleCancel: function(e) {
            e.preventDefault();
            const $row = $(e.currentTarget).closest('tr');
            const orderId = $row.data('order-id');

            if (confirm(wpwpsOrders.i18n.confirmCancel)) {
                this.cancelOrder(orderId)
                    .then(() => {
                        this.showToast('success', wpwpsOrders.i18n.success);
                        $row.fadeOut(() => $row.remove());
                    })
                    .catch(error => {
                        this.showToast('error', error.message);
                    });
            }
        },

        handleRefund: function(e) {
            e.preventDefault();
            const $row = $(e.currentTarget).closest('tr');
            const orderId = $row.data('order-id');
            
            $('#refundForm')[0].reset();
            this.currentOrderId = orderId;
            this.refundModal.show();
        },

        processRefund: function() {
            const formData = new FormData($('#refundForm')[0]);
            formData.append('order_id', this.currentOrderId);
            formData.append('action', 'wpwps_refund_order');
            formData.append('nonce', wpwpsOrders.nonce);

            this.showLoading('#confirmRefund');

            $.ajax({
                url: wpwpsOrders.ajaxUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: response => {
                    if (response.success) {
                        this.refundModal.hide();
                        this.showToast('success', response.data.message);
                        this.updateOrderRow($(`tr[data-order-id="${this.currentOrderId}"]`), response.data.order);
                    } else {
                        this.showToast('error', response.data.message);
                    }
                },
                error: () => {
                    this.showToast('error', wpwpsOrders.i18n.error);
                },
                complete: () => {
                    this.hideLoading('#confirmRefund');
                }
            });
        },

        viewTracking: function(e) {
            e.preventDefault();
            const orderId = $(e.currentTarget).closest('tr').data('order-id');

            this.getTracking(orderId)
                .then(response => {
                    $('.tracking-timeline').html(this.renderTrackingTimeline(response.data.tracking));
                    this.trackingModal.show();
                })
                .catch(error => {
                    this.showToast('error', error.message);
                });
        },

        syncAll: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);

            this.showLoading($button);

            $.ajax({
                url: wpwpsOrders.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_sync_all_orders',
                    nonce: wpwpsOrders.nonce
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
                    this.showToast('error', wpwpsOrders.i18n.error);
                },
                complete: () => {
                    this.hideLoading($button);
                }
            });
        },

        // AJAX Helpers
        syncOrder: function(orderId) {
            return $.ajax({
                url: wpwpsOrders.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_sync_order',
                    nonce: wpwpsOrders.nonce,
                    order_id: orderId
                }
            });
        },

        cancelOrder: function(orderId) {
            return $.ajax({
                url: wpwpsOrders.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_cancel_order',
                    nonce: wpwpsOrders.nonce,
                    order_id: orderId
                }
            });
        },

        getTracking: function(orderId) {
            return $.ajax({
                url: wpwpsOrders.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'wpwps_get_tracking',
                    nonce: wpwpsOrders.nonce,
                    order_id: orderId
                }
            });
        },

        // UI Helpers
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
        },

        updateOrderRow: function($row, order) {
            // Update status
            $row.find('.badge').removeClass().addClass(`badge bg-${order.status_color}`).text(order.status_label);
            
            // Update Printify status
            $row.find('.printify-status').html(`
                <span class="status-dot bg-${order.printify_status_color}"></span>
                ${order.printify_status}
            `);

            // Update tracking if available
            if (order.tracking_number) {
                $row.find('.printify-status').append(`
                    <small class="d-block mt-1">
                        <i class="fas fa-truck me-1"></i>
                        ${order.