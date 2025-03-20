jQuery(document).ready(function($) {
    let currentPage = 1;
    const perPage = 10;

    // ...existing code...

    // Add event handler for select all checkbox
    $('#select-all').on('change', function() {
        $('.product-select').prop('checked', $(this).is(':checked'));
        updateImportSelectedButton();
    });

    // Enable/disable import selected button based on selections
    $(document).on('change', '.product-select', function() {
        updateImportSelectedButton();
    });

    $('#import-selected').on('click', function() {
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

        const button = $(this);
        const originalHtml = button.html();
        button.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Importing...');

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
                        row.find('.import-product').prop('disabled', true).html('Imported');
                        row.find('.product-select').prop('checked', false);
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
    });

    function updateImportSelectedButton() {
        const selectedCount = $('.product-select:checked').length;
        const button = $('#import-selected');
        button.prop('disabled', !selectedCount);
        button.html(`<i class="fas fa-download"></i> Import Selected (${selectedCount})`);
    }
    
    // ...existing code...
});
