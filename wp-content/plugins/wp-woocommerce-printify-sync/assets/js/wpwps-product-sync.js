/**
 * Product sync page JavaScript
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

(function($) {
    'use strict';

    const ProductSync = {
        state: {
            page: 1,
            perPage: 20,
            search: '',
            sortBy: 'date',
            sortOrder: 'desc',
            loading: false,
            products: []
        },

        init() {
            this.initializeHandlers();
            this.loadProducts();
            this.startAutoRefresh();
        },

        initializeHandlers() {
            // Sync buttons
            $('#wpwps-import-products').on('click', () => this.importProducts());
            $('#wpwps-sync-all-products').on('click', () => this.syncAllProducts());

            // Search handler
            $('#wpwps-product-search').on('input', 
                wpwpsAdmin.debounce(() => this.handleSearch(), 500)
            );

            // Pagination handlers
            $('.wpwps-pagination-prev').on('click', () => this.changePage('prev'));
            $('.wpwps-pagination-next').on('click', () => this.changePage('next'));
        },

        async loadProducts() {
            this.setLoading(true);

            try {
                const response = await wpwpsAdmin.apiRequest('get_products', {
                    page: this.state.page,
                    per_page: this.state.perPage,
                    search: this.state.search,
                    sort_by: this.state.sortBy,
                    sort_order: this.state.sortOrder
                }, 'GET');

                if (response.success) {
                    this.updateProductTable(response.data.products);
                    this.updatePagination(response.data);
                }
            } catch (error) {
                wpwpsToast.error(wpwps.i18n.products_load_error);
            } finally {
                this.setLoading(false);
            }
        },

        setLoading(loading) {
            this.state.loading = loading;
            this.updateLoadingState();
        },

        updateLoadingState() {
            const $container = $('.wpwps-product-table');
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
        if ($('.wpwps-product-sync').length) {
            ProductSync.init();
        }
    });

})(jQuery);