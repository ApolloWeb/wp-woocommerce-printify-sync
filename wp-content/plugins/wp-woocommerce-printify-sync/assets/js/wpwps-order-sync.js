/**
 * Order sync JavaScript functionality
 */
(function($) {
    'use strict';

    const OrderSync = {
        state: {
            page: 1,
            perPage: 15,
            search: '',
            sortBy: 'date',
            sortOrder: 'desc',
            loading: false,
            orders: []
        },

        init() {
            this.initializeHandlers();
            this.loadOrders();
            this.startAutoRefresh();
        },

        initializeHandlers() {
            // Import button
            $('#wpwps-import-orders').on('click', () => this.importOrders());
            
            // Search handler
            $('#wpwps-order-search').on('input',
                wpwpsAdmin.debounce(() => this.handleSearch(), 500)
            );

            // Pagination handlers
            $('.wpwps-pagination-prev').on('click', () => this.changePage('prev'));
            $('.wpwps-pagination-next').on('click', () => this.changePage('next'));

            // Sync button handler
            $(document).on('click', '.wpwps-sync-order', (e) => {
                const $btn = $(e.currentTarget);
                this.syncOrder(
                    $btn.data('order-id'),
                    $btn.data('printify-id')
                );
            });
        },

        async loadOrders() {
            this.setLoading(true);

            try {
                const response = await wpwpsAdmin.apiRequest('get_orders', {
                    page: this.state.page,
                    per_page: this.state.perPage,
                    search: this.state.search,
                    sort_by: this.state.sortBy,
                    sort_order: this.state.sortOrder
                }, 'GET');

                if (response.success) {
                    this.updateOrderTable(response.data.orders);
                    this.updatePagination(response.data);
                }
            } catch (error) {
                wpwpsToast.error(wpwps.i18n.orders_load_error);
            } finally {
                this.setLoading(false);
            }
        },

        setLoading(loading) {
            this.state.loading = loading;
            this.updateLoadingState();
        },

        updateLoadingState() {
            const $container = $('.wpwps-order-table');
            if (this.state.loading) {
                $container.addClass('wpwps-loading');
            } else {
                $container.removeClass('wpwps-loading');
            }
        },

        startAutoRefresh() {
            setInterval(() => {
                if (!this.state.loading) {
                    this.refreshStats();
                }
            }, 30000);
        }
    };

    // Initialize on document ready
    $(document).ready(() => {
        if ($('.wpwps-order-sync').length) {
            OrderSync.init();
        }
    });

})(jQuery);