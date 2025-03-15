public function renderCleanupPage(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $ui = new UIHelper();
    ?>
    <div class="wrap wpwps-wrapper">
        <h1>Data Cleanup</h1>
        
        <div class="wpwps-alerts"></div>

        <?php $ui->renderAlert(
            '<strong>Warning:</strong> This will permanently delete all Printify sync data!',
            'warning'
        ); ?>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Cleanup Options</h5>
                    <div>
                        <span class="text-muted me-2">
                            <?php echo $ui->renderTooltip(
                                '<i class="dashicons dashicons-clock"></i> ' . $this->currentTime,
                                'Current Time (UTC)'
                            ); ?>
                        </span>
                        <span class="text-muted">
                            <?php echo $ui->renderTooltip(
                                '<i class="dashicons dashicons-admin-users"></i> ' . $this->currentUser,
                                'Current User'
                            ); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="wpwps-cleanup-form">
                    <?php wp_nonce_field('wpwps_cleanup', 'wpwps_cleanup_nonce'); ?>
                    
                    <div class="row">
                        <?php foreach ($this->getCleanupOptions() as $option): ?>
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="cleanup_items[]" 
                                           value="<?php echo esc_attr($option['value']); ?>" 
                                           id="<?php echo esc_attr($option['value']); ?>">
                                    <label class="form-check-label" 
                                           for="<?php echo esc_attr($option['value']); ?>">
                                        <?php echo $ui->renderTooltip(
                                            esc_html($option['label']),
                                            esc_attr($option['description'])
                                        ); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn btn-danger mt-3" 
                            data-bs-toggle="modal" 
                            data-bs-target="#cleanupConfirmModal">
                        Clean Selected Data
                    </button>
                </form>
            </div>
        </div>

        <?php
        $ui->renderModal('cleanupConfirmModal', 'Confirm Cleanup', '
            <p>Are you absolutely sure you want to proceed with the cleanup?</p>
            <p>This action cannot be undone!</p>
            <div class="form-group">
                <label for="cleanup-confirmation">Type "CONFIRM" to proceed:</label>
                <input type="text" class="form-control" id="cleanup-confirmation">
            </div>
        ', [
            'footer' => '
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-cleanup" disabled>
                    Proceed with Cleanup
                </button>
            '
        ]);
        ?>
    </div>
    <?php
}

private function getCleanupOptions(): array
{
    return [
        [
            'value' => 'products',
            'label' => 'Products',
            'description' => 'Delete all Printify products and their variations'
        ],
        [
            'value' => 'images',
            'label' => 'Images',
            'description' => 'Delete all product images imported from Printify'
        ],
        [
            'value' => 'taxonomies',
            'label' => 'Product Types',
            'description' => 'Delete all product type categories'
        ],
        [
            'value' => 'meta',
            'label' => 'Product Meta',
            'description' => 'Delete all Printify-related product metadata'
        ],
        [
            'value' => 'logs',
            'label' => 'Sync Logs',
            'description' => 'Delete all sync and error logs'
        ],
        [
            'value' => 'settings',
            'label' => 'Settings',
            'description' => 'Reset all plugin settings to defaults'
        ]
    ];
}