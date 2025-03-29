document.addEventListener('DOMContentLoaded', function() {
    // Handle individual product sync
    document.querySelectorAll('.sync-product').forEach(button => {
        button.addEventListener('click', function() {
            const printifyId = this.dataset.printifyId;
            const button = this;
            button.disabled = true;
            
            jQuery.ajax({
                url: wpwpsProducts.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpwps_sync_product',
                    nonce: wpwpsProducts.nonce,
                    printify_id: printifyId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Product synced successfully');
                        location.reload();
                    } else {
                        alert('Failed to sync product');
                    }
                },
                error: function() {
                    alert('Failed to sync product');
                },
                complete: function() {
                    button.disabled = false;
                }
            });
        });
    });

    // Handle sync all products
    const syncAllButton = document.getElementById('syncAllProducts');
    if (syncAllButton) {
        syncAllButton.addEventListener('click', function() {
            if (!confirm('Are you sure you want to sync all products? This may take a while.')) {
                return;
            }

            const button = this;
            button.disabled = true;
            
            jQuery.ajax({
                url: wpwpsProducts.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpwps_sync_all_products',
                    nonce: wpwpsProducts.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('All products synced successfully');
                        location.reload();
                    } else {
                        alert('Failed to sync all products');
                    }
                },
                error: function() {
                    alert('Failed to sync all products');
                },
                complete: function() {
                    button.disabled = false;
                }
            });
        });
    }
});