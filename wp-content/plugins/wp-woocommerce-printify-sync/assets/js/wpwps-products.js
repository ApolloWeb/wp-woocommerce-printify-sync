jQuery(document).ready(function($) {
    // Set per page to 50 (max from API)
    const perPage = 50;

    // Initialize with better error handling
    function initButtonHandlers() {
        console.log('Initializing button handlers for products page');
        
        // Fetch products button - more robust event handling
        $(document).on('click', '#fetch-products', function(e) {
            e.preventDefault();
            console.log('Fetch products button clicked');
            fetchProducts(true);
            return false;
        });
        
        // Clear cache button
        $(document).on('click', '#clear-cache', function(e) {
            e.preventDefault();
            console.log('Clear cache button clicked');
            fetchProducts(true);
            return false;
        });
        
        // Select all checkbox
        $(document).on('change', '#select-all', function() {
            $('.product-select').prop('checked', $(this).is(':checked'));
            updateImportSelectedButton();
        });
    
        // Import selected button
        $(document).on('click', '#import-selected', function(e) {
            e.preventDefault();
            console.log('Import selected button clicked');
            importSelectedProducts();
            return false;
        });
        
        // Individual product checkbox
        $(document).on('change', '.product-select', function() {
            updateImportSelectedButton();
        });
        
        console.log('Button handlers initialized');
    }
    
    // Call initialization after document is fully loaded
    initButtonHandlers();
    
    // Function to fetch products with improved error handling (simplified to single page)
    function fetchProducts(refreshCache = false) {
        const button = $('#fetch-products');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        $('#products-table tbody').html('<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading products from Printify...</td></tr>');
        
        console.log('Fetching up to 50 products, refresh cache:', refreshCache);
        
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_products',
                nonce: wpwps_data.nonce,
                page: 1,
                per_page: perPage,
                refresh_cache: refreshCache ? 'true' : 'false'
            },
            success: function(response) {
                console.log('Products API response:', response);
                if (response.success) {
                    updateProductsTable(response.data);
                    initImportButtons();
                } else {
                    handleError(response.data?.message || 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error, xhr.responseText);
                try {
                    const response = JSON.parse(xhr.responseText);
                    handleError('Failed to fetch products: ' + (response.data?.message || error));
                } catch (e) {
                    handleError('Failed to fetch products: ' + error);
                }
            },
            complete: function() {
                button.prop('disabled', false).html(originalHtml);
            }
        });
    }
    
    // Function to initialize import buttons after table is updated
    function initImportButtons() {
        $('.import-product').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Import button clicked for product');
            
            const button = $(this);
            const productId = button.data('id');
            const originalHtml = button.html();
            
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
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
                        button.html('<i class="fas fa-check"></i> Imported').addClass('btn-success').removeClass('btn-primary');
                        button.closest('tr').addClass('bg-light');
                    } else {
                        button.html('<i class="fas fa-times"></i> Failed').addClass('btn-danger').removeClass('btn-primary');
                        alert('Import failed: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    button.html('<i class="fas fa-times"></i> Error').addClass('btn-danger').removeClass('btn-primary');
                    alert('Failed to import due to network error');
                },
                complete: function() {
                    setTimeout(function() {
                        if (button.hasClass('btn-danger')) {
                            button.prop('disabled', false).html(originalHtml).removeClass('btn-danger').addClass('btn-primary');
                        }
                    }, 3000);
                }
            });
        });
    }
    
    // Function to import selected products
    function importSelectedProducts() {
        const selectedIds = $('.product-select:checked').map(function() {
            return $(this).data('id');
        }).get();

        if (!selectedIds.length) {
            alert('Please select products to import');
            return;
        }

        if (!confirm(`Are you sure you want to import ${selectedIds.length} products?`)) {
            return;
        }

        const button = $('#import-selected');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');

        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'POST',
            data: {
                action: 'printify_sync',
                action_type: 'bulk_import_products',
                nonce: wpwps_data.nonce,
                printify_ids: selectedIds
            },
            success: function(response) {
                if (response.success) {
                    // Update UI to show imported status
                    response.data.imported.forEach(function(item) {
                        const row = $(`.product-select[data-id="${item.printify_id}"]`).closest('tr');
                        row.find('.import-product').prop('disabled', true).html('<i class="fas fa-check"></i> Imported').addClass('btn-success').removeClass('btn-primary');
                        row.find('.product-select').prop('checked', false);
                        row.addClass('bg-light');
                    });
                    alert(response.data.message);
                } else {
                    alert('Import failed: ' + response.data.message);
                }
            },
            error: function() {
                alert('Import failed due to network error');
            },
            complete: function() {
                button.prop('disabled', false).html(originalHtml);
                updateImportSelectedButton();
            }
        });
    }

    // Update products table with fetched data
    function updateProductsTable(data) {
        const products = data.products || [];
        const tbody = $('#products-table tbody');
        tbody.empty();
        
        // Update totals immediately regardless of products returned
        const totalProducts = parseInt(data.total) || 0;
        $('#total-products').text(totalProducts);
        
        if (products.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center">No products found. Try refreshing or checking your Printify settings.</td></tr>');
            
            // Reset product count
            $('#showing-start').text('0');
            $('#showing-end').text('0');
            return;
        }
        
        // Update showing count with proper values - now showing all products at once
        const actualCount = products.length;
        $('#showing-start').text('1');
        $('#showing-end').text(actualCount);
        
        products.forEach(function(product) {
            const isImported = product.is_imported;
            const importButtonHtml = isImported ? 
                `<button class="btn btn-sm btn-success import-product" disabled data-id="${product.printify_id}"><i class="fas fa-check"></i> Imported</button>` : 
                `<button class="btn btn-sm btn-primary import-product" data-id="${product.printify_id}"><i class="fas fa-download"></i> Import</button>`;
            
            const row = `
                <tr${isImported ? ' class="bg-light"' : ''}>
                    <td><input type="checkbox" class="product-select" data-id="${product.printify_id}"${isImported ? ' disabled' : ''}></td>
                    <td><img src="${product.thumbnail || ''}" alt="${product.title}" width="50" height="50" class="img-thumbnail"></td>
                    <td>${product.title}</td>
                    <td>${product.printify_id}</td>
                    <td><span class="badge bg-${product.status === 'active' ? 'success' : 'secondary'}">${product.status}</span></td>
                    <td>${product.last_updated}</td>
                    <td>${importButtonHtml}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    // Handle errors
    function handleError(message) {
        $('#products-table tbody').html(`
            <tr>
                <td colspan="7" class="text-center text-danger">
                    <i class="fas fa-exclamation-circle"></i> ${message}
                </td>
            </tr>
        `);
        
        // Show an alert
        $('#products-alerts').html(`
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        // Reset product counts
        $('#showing-start').text('0');
        $('#showing-end').text('0');
        $('#total-products').text('0');
    }
    
    // Update import selected button
    function updateImportSelectedButton() {
        const selectedCount = $('.product-select:checked').length;
        const button = $('#import-selected');
        button.prop('disabled', !selectedCount);
        button.html(`<i class="fas fa-download"></i> Import Selected (${selectedCount})`);
    }
    
    // Note: Auto-loading is intentionally removed to prevent products from loading on page load
});
