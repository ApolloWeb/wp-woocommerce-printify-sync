/**
 * Products Page JavaScript
 */
jQuery(document).ready(function($) {
    // Filter functionality
    const filterForm = $('.card-wpwps form');
    const productsTable = $('.table');
    
    filterForm.on('submit', function(e) {
        e.preventDefault();
        
        // In a real implementation, this would filter the products
        // For demo purposes, simulate filtering with a loading state
        productsTable.addClass('opacity-50');
        
        // Show toast notification
        showToast('Filtering Products', 'Applying filters...', 'info');
        
        setTimeout(function() {
            productsTable.removeClass('opacity-50');
            showToast('Filter Applied', 'Products filtered successfully', 'success');
        }, 800);
    });
    
    // Product sync functionality
    $('.dropdown-item:contains("Sync")').on('click', function(e) {
        e.preventDefault();
        
        const productRow = $(this).closest('tr');
        const productTitle = productRow.find('a.text-decoration-none').text();
        
        // Add spinning animation to sync icon
        $(this).find('i').addClass('animate');
        
        // Show toast notification
        showToast('Syncing Product', `Syncing "${productTitle}" with Printify...`, 'info');
        
        // In a real implementation, this would call an AJAX endpoint
        // For demo purposes, show success after a delay
        setTimeout(() => {
            $(this).find('i').removeClass('animate');
            
            // Update sync status badge
            productRow.find('td:nth-child(8) .badge')
                .removeClass('bg-warning bg-danger text-dark')
                .addClass('bg-success')
                .text('Synced');
            
            showToast('Sync Complete', `Product "${productTitle}" has been synchronized`, 'success');
        }, 1500);
    });
    
    // Batch actions
    $('#batch-sync').on('click', function() {
        const selectedProducts = $('.product-select:checked');
        const count = selectedProducts.length;
        
        showToast('Batch Sync', `Syncing ${count} products with Printify...`, 'info');
        
        // In a real implementation, this would call an AJAX endpoint
        // For demo purposes, show success after a delay
        setTimeout(function() {
            // Update all selected products to "synced" status
            selectedProducts.each(function() {
                $(this).closest('tr').find('td:nth-child(8) .badge')
                    .removeClass('bg-warning bg-danger text-dark')
                    .addClass('bg-success')
                    .text('Synced');
            });
            
            showToast('Batch Sync Complete', `${count} products have been synchronized`, 'success');
            
            // Reset selection
            $('.product-select, #selectAll').prop('checked', false);
            $('#batch-actions-bar').addClass('d-none');
        }, 2000);
    });
    
    // Delete functionality
    $('.dropdown-item:contains("Delete")').on('click', function(e) {
        e.preventDefault();
        
        const productRow = $(this).closest('tr');
        const productTitle = productRow.find('a.text-decoration-none').text();
        
        if (confirm(`Are you sure you want to delete "${productTitle}"?`)) {
            // Show toast notification
            showToast('Deleting Product', `Deleting "${productTitle}"...`, 'info');
            
            // In a real implementation, this would call an AJAX endpoint
            // For demo purposes, fade out the row
            productRow.fadeOut(400, function() {
                $(this).remove();
                showToast('Product Deleted', `Product "${productTitle}" has been deleted`, 'success');
            });
        }
    });
    
    // Batch delete
    $('#batch-delete').on('click', function() {
        const selectedProducts = $('.product-select:checked');
        const count = selectedProducts.length;
        
        if (confirm(`Are you sure you want to delete ${count} products?`)) {
            showToast('Batch Delete', `Deleting ${count} products...`, 'info');
            
            // In a real implementation, this would call an AJAX endpoint
            // For demo purposes, fade out the rows
            selectedProducts.closest('tr').fadeOut(400, function() {
                $(this).remove();
                
                // Only show toast when all products are removed
                if ($('.product-select:checked').length === 0) {
                    showToast('Batch Delete Complete', `${count} products have been deleted`, 'success');
                    $('#batch-actions-bar').addClass('d-none');
                }
            });
        }
    });
});
