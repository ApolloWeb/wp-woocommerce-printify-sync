jQuery(document).ready(function($) {
    let currentPage = 1;
    const perPage = 10;
    let eventsInitialized = false;

    function showLoading(button) {
        button.prop('disabled', true)
              .html('<i class="fas fa-spinner fa-spin"></i> Loading...');
    }

    function hideLoading(button, originalHtml) {
        button.prop('disabled', false)
              .html(originalHtml);
    }

    // Show alert messages
    function showAlert(message, type = 'info') {
        return `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }

    // Remove the automatic cache clearing on load
    // Instead, just show the initial message
    $('#orders-table tbody').html('<tr><td colspan="8" class="text-center">Click "Fetch Orders" to load orders from Printify</td></tr>');

    // Initialize - Hide the orders counter until orders are loaded
    $('#orders-count').hide();

    function fetchOrders(page = 1, refreshCache = false) {
        currentPage = page;
        const button = $('#fetch-orders');
        const originalHtml = button.html();
        showLoading(button);

        $('#orders-table tbody').html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading orders from Printify...</td></tr>');
        
        $.ajax({
            url: wpwps_data.ajax_url,
            type: 'GET',
            data: {
                action: 'printify_sync',
                action_type: 'fetch_printify_orders',
                nonce: wpwps_data.nonce,
                page: page,
                per_page: perPage,
                refresh_cache: refreshCache
            },
            success: function(response) {
                if (response.success) {
                    renderOrders(response.data.orders);
                    updatePagination(response.data);
                    
                    // Show the orders counter after successful fetch
                    $('#orders-count').show();
                } else {
                    $('#orders-alerts').html(showAlert(response.data.message || 'Failed to fetch orders', 'danger'));
                }
            },
            error: function() {
                $('#orders-alerts').html(showAlert('Network error while fetching orders', 'danger'));
            },
            complete: function() {
                hideLoading(button, originalHtml);
            }
        });
    }

    function updatePagination(data) {
        const totalPages = Math.max(1, Math.ceil(data.total / perPage));
        let paginationHtml = '';
        
        // Only generate pagination if there are orders
        if (data.total === 0) {
            $('#orders-pagination').empty();
            $('#orders-count').hide();
            return;
        }
        
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
        // For small number of pages, show all
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
        } else {
            // For larger number of pages, show 1, 2, ..., current-1, current, current+1, ..., totalPages-1, totalPages
            // Always show first page
            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>
            `;
            
            // Show second page or ellipsis
            if (currentPage > 3) {
                paginationHtml += `
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                `;
            } else if (currentPage !== 2) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="2">2</a>
                    </li>
                `;
            }
            
            // Show pages around current page
            for (let i = Math.max(3, currentPage - 1); i <= Math.min(totalPages - 2, currentPage + 1); i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
            
            // Show second-to-last page or ellipsis
            if (currentPage < totalPages - 2) {
                paginationHtml += `
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                `;
            } else if (currentPage !== totalPages - 1) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${totalPages - 1}">${totalPages - 1}</a>
                    </li>
                `;
            }
            
            // Always show last page
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
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

        $('#orders-pagination').html(paginationHtml);
        
        // Update counters
        const start = data.total > 0 ? ((currentPage - 1) * perPage) + 1 : 0;
        const end = Math.min(currentPage * perPage, data.total);
        
        $('#showing-start').text(start);
        $('#showing-end').text(end);
        $('#total-orders').text(data.total);
    }

    // Initialize event handlers
    function initializeEvents() {
        if (eventsInitialized) {
            return;
        }
        
        eventsInitialized = true;
        
        // Add "Clear Cache" button handler
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
                        $('#orders-alerts').html(showAlert('Cache cleared. Click "Fetch Orders" to load fresh data.', 'info'));
                        
                        // Reset table
                        $('#orders-table tbody').html('<tr><td colspan="8" class="text-center">Cache cleared. Click "Fetch Orders" to load orders from Printify</td></tr>');
                        
                        // Reset pagination
                        $('#orders-pagination').empty();
                        
                        // Hide orders counter when cache is cleared
                        $('#orders-count').hide();
                        
                        // Reset counters
                        $('#showing-start').text('0');
                        $('#showing-end').text('0');
                        $('#total-orders').text('0');
                    } else {
                        $('#orders-alerts').html(showAlert(response.data.message || 'Error clearing cache', 'danger'));
                    }
                },
                error: function() {
                    $('#orders-alerts').html(showAlert('Network error while clearing cache', 'danger'));
                },
                complete: function() {
                    hideLoading(button, originalHtml);
                }
            });
        });
        
        // Fetch orders button
        $('#fetch-orders').on('click', function() {
            fetchOrders(1, true);
        });
        
        // Pagination click handler
        $(document).on('click', '.page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && !isNaN(page) && !$(this).parent().hasClass('disabled')) {
                fetchOrders(parseInt(page));
            }
        });
    }
    
    // Initialize events once
    initializeEvents();
});
