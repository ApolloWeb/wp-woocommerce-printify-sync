const WPWPS = window.WPWPS || {};

WPWPS.Products = {
    settings: {
        perPage: 20,
        currentPage: 1,
        totalProducts: 0
    },

    init(options) {
        this.settings = { ...this.settings, ...options };
        this.initEventListeners();
        this.initializeDataTable();
        this.initializeBatchActions();
    },

    initEventListeners() {
        // Search functionality
        $('#product-search').on('input', debounce(() => {
            this.filterProducts();
        }, 500));

        // Filters
        $('#filter-products').on('click', () => {
            this.toggleFilters();
        });

        // Batch actions
        $('#sync-selected').on('click', () => {
            this.syncSelectedProducts();
        });

        // Individual product actions
        $(document).on('click', '.sync-product', (e) => {
            const productId = $(e.currentTarget).closest('tr').data('product-id');
            this.syncProduct(productId);
        });

        // Select all functionality
        $('#select-all').on('change', (e) => {
            $('.product-select').prop('checked', e.target.checked);
            this.updateBatchActionState();
        });
    },

    initializeDataTable() {
        const options = {
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxurl,
                data: (d) => {
                    return {
                        action: 'wpwps_get_products',
                        ...d,
                        filters: this.getFilters()
                    };
                }
            },
            columns: [
                { data: 'checkbox', orderable: false },
                { data: 'product', orderable: true },
                { data: 'variants', orderable: false },
                { data: 'last_synced', orderable: true },
                { data: 'status', orderable: true },
                { data: 'actions', orderable: false }
            ],
            order: [[3, 'desc']], // Sort by last synced by default
            drawCallback: () => {
                this.initTooltips();
                this.updateBatchActionState();
            }
        };

        this.table = $('#products-table').DataTable(options);
    },

    syncSelectedProducts() {
        const selectedIds = $('.product-select:checked').map(function() {
            return $(this).val();
        }).get();

        if (!selectedIds.length) {
            Swal.fire({
                title: 'No Products Selected',
                text: 'Please select products to sync',
                icon: 'warning'
            });
            return;
        }

        Swal.fire({
            title: 'Sync Selected Products',
            text: `Are you sure you want to sync ${selectedIds.length} products?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, sync them'
        }).then((result) => {
            if (result.isConfirmed) {
                this.startBatchSync(selectedIds);
            }
        });
    },

    startBatchSync(productIds) {
        const modal = new bootstrap.Modal('#sync-progress-modal');
        const progressBar = $('#sync-progress-bar');
        let processed = 0;

        modal.show();

        const syncNext = () => {
            if (processed >= productIds.length) {
                setTimeout(() => {
                    modal.hide();
                    this.table.ajax.reload();
                    Swal.fire({
                        title: 'Sync Complete',
                        text: `Successfully synced ${processed} products`,
                        icon: 'success'
                    });
                }, 1000);
                return;
            }

            const productId = productIds[processed];
            this.syncProduct(productId, false)
                .then(() => {
                    processed++;
                    const progress = (processed / productIds.length) * 100;
                    progressBar.css('width', `${progress}%`);
                    progressBar.text(`${processed}/${productIds.length}`);
                    syncNext();
                })
                .catch((error) => {
                    console.error('Sync failed:', error);
                    processed++;
                    syncNext();
                });
        };

        syncNext();
    },

    syncProduct(productId, showFeedback = true) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wpwps_sync_product',
                    product_id: productId,
                    nonce: wpwpsData.nonce
                }
            })
            .done((response) => {
                if (showFeedback) {
                    this.showSuccessMessage('Product synced successfully');
                    this.table.ajax.reload();
                }
                resolve(response);
            })
            .fail((error) => {
                if (showFeedback) {
                    this.showErrorMessage('Failed to sync product');
                }
                reject(error);
            });
        });
    },

    showSuccessMessage(message) {
        Swal.fire({
            title: 'Success',
            text: message,
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    },

    showErrorMessage(message) {
        Swal.fire({
            title: 'Error',
            text: message,
            icon: 'error',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
};