jQuery(document).ready(function($) {
    let currentPage = 1;
    const perPage = 10;
    let selectedProducts = new Set();

    function showLoading(button) {
        button.prop('disabled', true)
              .html('<i class="fas fa-spinner fa-spin"></i> Loading...');
    }

    function hideLoading(button, originalHtml) {
        button.prop('disabled', false)
              .html(originalHtml);
    }

    function showAlert(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${type === 'success' ? '<i class="fas fa-check-circle me-2"></i>' : ''}
                ${type === 'danger' ? '<i class="fas fa-exclamation-circle me-2"></i>' : ''}
                ${type === 'warning' ? '<i class="fas fa-exclamation-triangle me-2"></i>' : ''}
                ${type === 'info' ? '<i class="fas fa-info-circle me-2"></i>' : ''}
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#products-alerts').html(alertHtml);
    }

    function updatePagination(data) {
        const totalPages = Math.ceil(data.total / perPage);
        let paginationHtml = '';
        
        // First/Prev buttons
        paginationHtml += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="1">&laquo;</a>
            </li>
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">&lsaquo;</a>
            </li>
        `;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        // Next/Last buttons
        paginationHtml += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">&rsaquo;</a>
            </li>
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${totalPages}">&raquo;</a>
            </li>
        `;

        $('#products-pagination').html(paginationHtml);
        $('#showing-start').text(((currentPage - 1) * perPage) + 1);
        $('#showing-end').text(Math.min(currentPage * perPage, data.total));
        $('#total-products').text(data.total);
    }

    function fetchProducts(page = 1, refreshCache = false) {
        currentPage = page;
        const button = $('#fetch-products');
        const originalHtml = button.html();
        showLoading(button);

        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'GET',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_products',
                nonce: wpwps_data.nonce,
                page: page,
                per_page: perPage,
                refresh_cache: refreshCache
            },
            success: function(response) {
                if (response.success) {
                    renderProducts(response.data);
                    updatePagination(response.data);
                } else {
                    showAlert(response.data.message || 'Error fetching products', 'danger');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Network error while fetching products: ' + error, 'danger');
                console.error('Error:', {xhr, status, error});
            },
            complete: function() {
                hideLoading(button, originalHtml);
            }
        });
    }

    function renderProducts(data) {
        const tbody = $('#products-table tbody');
        tbody.empty();

        data.products.forEach(product => {
            const row = `
                <tr>
                    <td><input type="checkbox" class="product-select" value="${product.printify_id}"></td>
                    <td>
                        <img src="${product.thumbnail}" alt="${product.title}" 
                            style="width: 50px; height: 50px; object-fit: cover;">
                    </td>
                    <td>${product.title}</td>
                    <td>${product.printify_id}</td>
                    <td>
                        <span class="badge bg-${product.status === 'active' ? 'success' : 'secondary'}">
                            ${product.status}
                        </span>
                    </td>
                    <td>${product.last_updated}</td>
                    <td>
                        ${!product.is_imported ? 
                            `<button type="button" class="btn btn-sm btn-outline-primary import-single" 
                                data-id="${product.printify_id}">
                                <i class="fas fa-download"></i> Import
                            </button>` : 
                            `<a href="post.php?post=${product.woo_product_id}&action=edit" 
                                class="btn btn-sm btn-outline-success">
                                <i class="fas fa-external-link-alt"></i> View
                            </a>`
                        }
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Update select all checkbox
        $('#select-all').prop('checked', false);
        selectedProducts.clear();
        $('#import-selected').prop('disabled', true);
    }

    // Event Handlers
    $('#fetch-products').on('click', function() {
        fetchProducts(1, true);
    });

    $('#clear-cache').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        showLoading(button);

        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'clear_cache',
                nonce: wpwps_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAlert('Cache cleared successfully!', 'success');
                    $('#products-table tbody').html('<tr><td colspan="7" class="text-center">Cache cleared. Click "Fetch Products" to load products from Printify</td></tr>');
                    $('#products-pagination').empty();
                    updatePagination({total: 0, current_page: 1});
                } else {
                    showAlert(response.data.message || 'Error clearing cache', 'danger');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Network error while clearing cache: ' + error, 'danger');
            },
            complete: function() {
                hideLoading(button, originalHtml);
            }
        });
    });

    // Initialize handlers for dynamic elements
    $(document).on('change', '#select-all', function() {
        $('.product-select').prop('checked', $(this).is(':checked'));
        if (this.checked) {
            $('.product-select').each(function() {
                selectedProducts.add($(this).val());
            });
        } else {
            selectedProducts.clear();
        }
        $('#import-selected').prop('disabled', selectedProducts.size === 0);
    });

    $(document).on('change', '.product-select', function() {
        if (this.checked) {
            selectedProducts.add($(this).val());
        } else {
            selectedProducts.delete($(this).val());
            $('#select-all').prop('checked', false);
        }
        $('#import-selected').prop('disabled', selectedProducts.size === 0);
    });

    $(document).on('click', '.import-single', function() {
        const button = $(this);
        const productId = button.data('id');
        const originalHtml = button.html();
        showLoading(button);

        importProduct(productId, button, originalHtml);
    });

    $('#import-selected').on('click', function() {
        if (selectedProducts.size === 0) {
            showAlert('No products selected for import.', 'warning');
            return;
        }

        const button = $(this);
        const originalHtml = button.html();
        showLoading(button);

        const promises = Array.from(selectedProducts).map(productId => {
            return new Promise((resolve, reject) => {
                importProduct(productId, null, null, resolve, reject);
            });
        });

        Promise.all(promises).then(() => {
            showAlert('All selected products imported successfully!', 'success');
            fetchProducts(currentPage); // Refresh the current page
        }).catch(error => {
            showAlert('Error importing some products: ' + error, 'danger');
        }).finally(() => {
            hideLoading(button, originalHtml);
        });
    });

    function importProduct(productId, button = null, originalHtml = null, resolve = null, reject = null) {
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'import_product_to_woo',
                nonce: wpwps_data.nonce,
                printify_id: productId
            },
            success: function(response) {
                if (response.success) {
                    if (button) {
                        button.replaceWith(
                            `<a href="post.php?post=${response.data.woo_product_id}&action=edit" 
                                class="btn btn-sm btn-outline-success">
                                <i class="fas fa-external-link-alt"></i> View
                            </a>`
                        );
                    }
                    if (resolve) resolve();
                } else {
                    if (button) {
                        showAlert(response.data.message || 'Error importing product', 'danger');
                    }
                    if (reject) reject(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = 'Network error while importing product: ' + error;
                if (button) {
                    showAlert(errorMsg, 'danger');
                }
                if (reject) reject(errorMsg);
            },
            complete: function() {
                if (button && originalHtml) {
                    hideLoading(button, originalHtml);
                }
            }
        });
    }

    // Initialize page
    $('#products-table tbody').html('<tr><td colspan="7" class="text-center">Click "Fetch Products" to load products from Printify</td></tr>');
    $('#import-selected').prop('disabled', true);

    // Handle pagination clicks
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && !isNaN(page) && !$(this).parent().hasClass('disabled')) {
            fetchProducts(parseInt(page));
        }
    });

    // Handle filters
    $('#products-filter-form').on('submit', function(e) {
        e.preventDefault();
        fetchProducts(1, false); // Reset to first page with new filters
    });
});
