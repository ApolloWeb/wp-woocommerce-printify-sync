jQuery(document).ready(function($) {
    $('#printify-sync-import-btn').click(function(e) {
        e.preventDefault();

        var progressBar = $('#printify-sync-import-progress-bar');
        var progressText = $('#printify-sync-import-progress-text');

        $('#printify-sync-import-progress').show();

        $.ajax({
            url: printifySync.ajax_url,
            method: 'POST',
            data: {
                action: 'printify_sync_import_products',
                security: printifySync.nonce,
            },
            success: function(response) {
                if (response.success) {
                    var totalChunks = response.data.total_chunks;
                    var processedChunks = 0;

                    function processNextChunk() {
                        $.ajax({
                            url: printifySync.ajax_url,
                            method: 'POST',
                            data: {
                                action: 'printify_sync_process_chunk',
                                security: printifySync.nonce,
                            },
                            success: function(response) {
                                if (response.success) {
                                    processedChunks++;
                                    var progress = Math.round((processedChunks / totalChunks) * 100);
                                    progressBar.val(progress);
                                    progressText.text(progress + '%');

                                    if (processedChunks < totalChunks) {
                                        processNextChunk();
                                    } else {
                                        alert('Import completed');
                                    }
                                }
                            }
                        });
                    }

                    processNextChunk();
                }
            }
        });
    });
});