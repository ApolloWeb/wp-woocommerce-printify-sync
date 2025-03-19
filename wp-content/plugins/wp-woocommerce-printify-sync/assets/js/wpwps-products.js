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

    function updatePagination(data) {
        const totalPages = Math.ceil(data.total / data.per_page);
        let paginationHtml = '';

        // Only show pagination if we have data and more than 0 total
        if (data.total > 0 && totalPages > 0) {
            // Previous button
            paginationHtml += `
                <li class="page-item ${data.current_page <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${Math.max(1, data.current_page - 1)}">Previous</a>
                </li>
            `;
            
            // First page always shows
            paginationHtml += `
                <li class="page-item ${data.current_page === 1 ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>
            `;

            // Show dots if there are pages between first and current
            if (data.current_page > 3) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            // Show surrounding pages
            for (let i = Math.max(2, data.current_page - 1); i <= Math.min(totalPages - 1, data.current_page + 1); i++) {
                if (i === 1 || i === totalPages) continue; // Skip if it's first or last page as they're handled separately
                paginationHtml += `
                    <li class="page-item ${data.current_page === i ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            // Show dots if there are pages between current and last
            if (data.current_page < totalPages - 2) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            // Last page if there's more than one page
            if (totalPages > 1) {
                paginationHtml += `
                    <li class="page-item ${data.current_page === totalPages ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                    </li>
                `;
            }
            
            // Next button
            paginationHtml += `
                <li class="page-item ${data.current_page >= totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${Math.min(totalPages, data.current_page + 1)}">Next</a>
                </li>
            `;
        }
        
        $('#products-pagination').html(paginationHtml);
        
        // Update count text
        if (data.total > 0) {
            const start = ((data.current_page - 1) * data.per_page) + 1;
            const end = Math.min(data.current_page * data.per_page, data.total);
            $('#showing-start').text(start);
            $('#showing-end').text(end);
        } else {
            $('#showing-start').text(0);
            $('#showing-end').text(0);
        }
        $('#total-products').text(data.total || 0);
    }

    function renderProducts(products) {
        const tbody = $('#products-table tbody');
        tbody.empty();
        
        if (!Array.isArray(products) || products.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center">No products found</td></tr>');
            return;
        }
        
        products.forEach(product => {
            const row = `
                <tr>
                    <td>
                        <input type="checkbox" class="product-select" 
                               value="${product.printify_id}"
                               ${selectedProducts.has(product.printify_id) ? 'checked' : ''}>
                    </td>
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
                        ${product.is_imported ? 
                            `<a href="/wp-admin/post.php?post=${product.woo_product_id}&action=edit" 
                                class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>` :
                            `<button type="button" class="btn btn-sm btn-outline-success import-single" 
                                     data-id="${product.printify_id}">
                                <i class="fas fa-download"></i> Import
                            </button>`
                        }
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function fetchProducts(page = 1) {
        currentPage = page;
        const button = $('#fetch-products');
        const originalHtml = button.html();
        showLoading(button);
        
        console.log('Fetching products page:', page); // Debug log
        
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_products',
                nonce: wpwps_data.nonce,
                page: page,
                per_page: perPage
            },
            success: function(response) {
                console.log('API Response:', response); // Debug log
                
                if (response.success && response.data && Array.isArray(response.data.products)) {
                    renderProducts(response.data.products);
                    updatePagination(response.data);
                } else {
                    const message = response.data?.message || 'Failed to fetch products';
                    console.error('API Error:', message); // Debug log
                    alert(message);
                    $('#products-table tbody').html(
                        '<tr><td colspan="7" class="text-center">Error: ' + message + '</td></tr>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax Error:', {xhr, status, error}); // Debug log
                alert('An error occurred while fetching products');
                $('#products-table tbody').html(
                    '<tr><td colspan="7" class="text-center">Error: Failed to fetch products</td></tr>'
                );
            },
            complete: function() {
                hideLoading(button, originalHtml);
            }
        });
    }

    function importProducts(productIds) {
        const button = $('#import-selected');
        const originalHtml = button.html();
        showLoading(button);
        
        const importNext = (index) => {
            if (index >= productIds.length) {
                hideLoading(button, originalHtml);
                fetchProducts(currentPage); // Refresh the list
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'printify_sync',
                    action_type: 'import_product_to_woo',
                    nonce: wpwps_data.nonce,
                    printify_id: productIds[index]
                },
                success: function(response) {
                    if (response.success) {
                        selectedProducts.delete(productIds[index]);
                        importNext(index + 1);
                    } else {
                        alert(response.data.message || 'Failed to import product');
                        hideLoading(button, originalHtml);
                    }
                },
                error: function() {
                    alert('An error occurred while importing product');
                    hideLoading(button, originalHtml);
                }
            });
        };
        
        importNext(0);
    }

    // Add Import All button next to Fetch Products
    function addImportAllButton() {
        const importAllBtn = $(`
            <button type="button" class="btn btn-success btn-sm me-2" id="import-all">
                <i class="fas fa-download"></i> Import All
            </button>
        `);
        $('#fetch-products').after(importAllBtn);
    }

    function importAllProducts(page = 1) {
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_products',
                nonce: wpwps_data.nonce,
                page: page,
                per_page: perPage,
                published_only: false
            },
            success: function(response) {
                if (response.success) {
                    // Import current page products
                    const productIds = response.data.products.map(p => p.printify_id);
                    importProducts(productIds);
                    
                    // If there are more pages, continue importing
                    if (page < response.data.last_page) {
                        importAllProducts(page + 1);
                    }
                } else {
                    alert(response.data.message || 'Failed to fetch products');
                }
            },
            error: function() {
                alert('An error occurred while fetching products');
            }
        });
    }

    // Add Import All button handler
    $(document).on('click', '#import-all', function() {
        if (confirm('Are you sure you want to import all products? This may take a while.')) {
            importAllProducts(1);
        }
    });

    // Initialize
    addImportAllButton();

    // Event Handlers
    $('#fetch-products').on('click', function() {
        fetchProducts(1);
    });

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && !isNaN(page) && !$(this).parent().hasClass('disabled')) {
            fetchProducts(parseInt(page));
        }
    });

    $('#select-all').on('change', function() {
        const checked = $(this).prop('checked');
        $('.product-select:not(:disabled)').prop('checked', checked).trigger('change');
    });

    $(document).on('change', '.product-select', function() {
        const id = $(this).val();
        if ($(this).prop('checked')) {
            selectedProducts.add(id);
        } else {
            selectedProducts.delete(id);
        }
        $('#import-selected').prop('disabled', selectedProducts.size === 0);
    });

    $('#import-selected').on('click', function() {
        if (selectedProducts.size > 0) {
            importProducts(Array.from(selectedProducts));
        }
    });

    $(document).on('click', '.import-single', function() {
        const id = $(this).data('id');
        importProducts([id]);
    });
});
