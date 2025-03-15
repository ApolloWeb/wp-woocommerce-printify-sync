jQuery(document).ready(function($) {
    const importButton = $('#start-import');
    const progressBar = $('#import-progress');
    const progressFill = $('.progress-fill');
    const importLog = $('#import-log');
    let importRunning = false;

    importButton.on('click', function() {
        if (importRunning) return;
        
        const productIds = $('#product-ids').val().trim();
        if (!productIds) {
            addLogEntry('Please enter product IDs', 'error');
            return;
        }

        importRunning = true;
        importButton.prop('disabled', true);
        progressBar.show();
        
        startImport(productIds);
    });

    function startImport(productIds) {
        $.ajax({
            url: wpwps.ajax_url,
            type: 'POST',
            data: {
                action: 'wpwps_import_products',
                nonce: wpwps.nonce,
                product_ids: productIds,
                use_r2: $('#use-r2').is(':checked')
            },
            success: function(response) {
                if (response.success) {
                    monitorProgress();
                    addLogEntry('Import started', 'success');
                } else {
                    handleError(response.data.message);
                }
            },
            error: function() {
                handleError('Failed to start import');
            }
        });
    }

    function monitorProgress() {
        const checkStatus = setInterval(function() {
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_check_import_status',
                    nonce: wpwps.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateProgress(response.data);
                        
                        if (response.data.completed) {
                            clearInterval(checkStatus);
                            importComplete();
                        }
                    }
                }
            });
        }, 2000);
    }

    function updateProgress(status) {
        const percent = (status.processed / status.total) * 100;
        progressFill.css('width', percent + '%');
        $('#imported').text(status.processed);
        $('#total').text(status.total);

        if (status.latest_log) {
            addLogEntry(status.latest_log.message, status.latest_log.type);
        }
    }

    function importComplete() {
        importRunning = false;
        importButton.prop('disabled', false);
        addLogEntry('Import completed', 'success');
    }

    function handleError(message) {
        importRunning = false;
        importButton.prop('disabled', false);
        progressBar.hide();
        addLogEntry(message, 'error');
    }

    function addLogEntry(message, type) {
        const entry = $('<div>')
            .addClass('log-entry')
            .addClass('log-' + type)
            .text(message);
        
        importLog.prepend(entry);
    }
});