jQuery(document).ready(function($) {
    // Initialize toast notifications
    WPWPSToast.init();

    let currentPage = 1;
    let itemsPerPage = 10;
    let selectedProducts = new Set();
    let productToSync = null;

    // Initialize Bootstrap components
    const syncModal = new bootstrap.Modal('#sync-product-modal');
    const bulkSyncModal = new bootstrap.Modal('#bulk-sync-modal');
    const errorModal = new bootstrap.Modal('#error-details-modal');

    // Products table loading state
    function setTableLoading(loading) {
        const table = $('#products-table');
        if (loading) {
            table.addClass('table-loading');
        } else {
            table.removeClass('table-loading');
        }
    }

    // Load products with filters
    function loadProducts() {
        setTableLoading(true);

        $.ajax({
            url: wpwps_products.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_get_products',
                nonce: wpwps_products.nonce,
                page: currentPage,
                per_page: itemsPerPage,
                search: $('#product-search').val(),
                sync_status: $('#sync-status-filter').val(),
                publish_status: $('#published-status-filter').val()
            },
            success: function(response) {
                if (response.success) {
                    updateProductsTable(response.data.products);
                    updatePagination(response.data.total, response.data.total_pages);
                } else {
                    handleError(response.data);
                }
            },
            error: function(xhr) {
                handleError({
                    message: 'Failed to load products',
                    code: xhr.status
                });
            },
            complete: function() {
                setTableLoading(false);
            }
        });
    }

    // Update products table
    function updateProductsTable(products) {
        const tbody = $('#products-table tbody');
        tbody.empty();

        if (products.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-box fa-2x mb-2"></i>
                            <p class="mb-0">${wpwps_products.text.no_products}</p>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        products.forEach(function(product) {
            const tr = $('<tr>');
            tr.html(`
                <td>
                    <input type="checkbox" class="form-check-input product-select" 
                           value="${product.id}" ${selectedProducts.has(product.id) ? 'checked' : ''}>
                </td>
                <td>
                    <img src="${product.thumbnail_url}" class="rounded" width="60" height="60"
                         alt="${product.title}">
                </td>
                <td>
                    <div class="fw-medium">${product.title}</div>
                    <small class="text-muted">ID: ${product.id}</small>
                </td>
                <td>${product.sku}</td>
                <td>${product.price}</td>
                <td>
                    <span class="badge bg-${product.status === 'published' ? 'success' : 'warning'}">
                        ${product.status.charAt(0).toUpperCase() + product.status.slice(1)}
                    </span>
                    ${product.sync_status ? `
                        <span class="badge bg-${product.sync_status === 'synced' ? 'info' : 
                            (product.sync_status === 'failed' ? 'danger' : 'warning')}">
                            ${product.sync_status.charAt(0).toUpperCase() + product.sync_status.slice(1)}
                        </span>
                    ` : ''}
                </td>
                <td>
                    ${product.last_sync ? `
                        <span title="${product.last_sync}">${product.last_sync_human}</span>
                    ` : 'Never'}
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-link btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <button class="dropdown-item sync-product" data-id="${product.id}">
                                <i class="fas fa-sync me-2"></i>Sync Now
                            </button>
                            <a href="${product.edit_url}" class="dropdown-item" target="_blank">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a>
                            <a href="${product.view_url}" class="dropdown-item" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>View
                            </a>
                        </div>
                    </div>
                </td>
            `);
            tbody.append(tr);
        });

        updateBulkActions();
    }

    // Update pagination
    function updatePagination(total, totalPages) {
        $('#showing-start').text((currentPage - 1) * itemsPerPage + 1);
        $('#showing-end').text(Math.min(currentPage * itemsPerPage, total));
        $('#total-items').text(total);

        const pagination = $('.pagination');
        pagination.find('li:not(:first-child):not(:last-child)').remove();

        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);

        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        for (let i = startPage; i <= endPage; i++) {
            const li = $('<li>').addClass('page-item');
            if (i === currentPage) {
                li.addClass('active');
            }
            li.append(
                $('<a>').addClass('page-link').attr('href', '#').text(i)
            );
            pagination.find('li:last').before(li);
        }

        $('#prev-page').toggleClass('disabled', currentPage === 1);
        $('#next-page').toggleClass('disabled', currentPage === totalPages);
    }

    // Update bulk actions state
    function updateBulkActions() {
        const bulkSyncBtn = $('#bulk-sync');
        bulkSyncBtn.prop('disabled', selectedProducts.size === 0);
        if (selectedProducts.size > 0) {
            bulkSyncBtn.html(`
                <i class="fas fa-sync me-2"></i>Sync Selected (${selectedProducts.size})
            `);
        } else {
            bulkSyncBtn.html('<i class="fas fa-sync me-2"></i>Sync Selected');
        }
    }

    // Handle errors
    function handleError(error) {
        let message = error.message || 'An unknown error occurred';
        if (error.code) {
            message = `Error ${error.code}: ${message}`;
        }
        
        WPWPSToast.error('Error', message);

        if (error.details) {
            $('#error-details').text(JSON.stringify(error.details, null, 2));
            errorModal.show();
        }
    }

    // Event Handlers
    $('#product-search').on('keyup', $.debounce(500, function() {
        currentPage = 1;
        loadProducts();
    }));

    $('#sync-status-filter, #published-status-filter').on('change', function() {
        currentPage = 1;
        loadProducts();
    });

    $('#per-page').on('change', function() {
        itemsPerPage = parseInt($(this).val());
        currentPage = 1;
        loadProducts();
    });

    $('.pagination').on('click', '.page-link', function(e) {
        e.preventDefault();
        const li = $(this).parent();
        
        if (li.hasClass('disabled')) return;

        if (li.attr('id') === 'prev-page') {
            currentPage--;
        } else if (li.attr('id') === 'next-page') {
            currentPage++;
        } else {
            currentPage = parseInt($(this).text());
        }

        loadProducts();
    });

    $('#select-all').on('change', function() {
        const checked = $(this).prop('checked');
        $('.product-select').prop('checked', checked);
        
        if (checked) {
            $('.product-select').each(function() {
                selectedProducts.add($(this).val());
            });
        } else {
            selectedProducts.clear();
        }

        updateBulkActions();
    });

    $(document).on('change', '.product-select', function() {
        const productId = $(this).val();
        if ($(this).prop('checked')) {
            selectedProducts.add(productId);
        } else {
            selectedProducts.delete(productId);
            $('#select-all').prop('checked', false);
        }
        updateBulkActions();
    });

    $(document).on('click', '.sync-product', function() {
        productToSync = $(this).data('id');
        syncModal.show();
    });

    $('#bulk-sync').on('click', function() {
        bulkSyncModal.show();
    });

    $('#confirm-sync').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        button.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...').prop('disabled', true);

        $.ajax({
            url: wpwps_products.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_sync_product',
                nonce: wpwps_products.nonce,
                product_id: productToSync
            },
            success: function(response) {
                if (response.success) {
                    syncModal.hide();
                    WPWPSToast.success('Success', response.data.message);
                    loadProducts();
                } else {
                    handleError(response.data);
                }
            },
            error: function(xhr) {
                handleError({
                    message: 'Failed to sync product',
                    code: xhr.status
                });
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });

    $('#confirm-bulk-sync').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        button.html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...').prop('disabled', true);

        $.ajax({
            url: wpwps_products.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_bulk_sync_products',
                nonce: wpwps_products.nonce,
                product_ids: Array.from(selectedProducts)
            },
            success: function(response) {
                if (response.success) {
                    bulkSyncModal.hide();
                    selectedProducts.clear();
                    updateBulkActions();
                    WPWPSToast.success('Success', response.data.message);
                    loadProducts();
                } else {
                    handleError(response.data);
                }
            },
            error: function(xhr) {
                handleError({
                    message: 'Failed to sync products',
                    code: xhr.status
                });
            },
            complete: function() {
                button.html(originalText).prop('disabled', false);
            }
        });
    });

    // Initial load
    loadProducts();
});