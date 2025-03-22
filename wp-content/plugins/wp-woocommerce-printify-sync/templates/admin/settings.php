<?php $this->layout('admin/layout', ['title' => __('Settings', 'wp-woocommerce-printify-sync')]) ?>

<div class="wpps-card p-4">
    <form id="wpps-settings-form" class="needs-validation" novalidate>
        <!-- API Settings -->
        <div class="mb-4">
            <h3 class="h5 mb-3"><?= __('API Configuration', 'wp-woocommerce-printify-sync') ?></h3>
            
            <div class="mb-3">
                <label for="printify_api_key" class="form-label">
                    <?= __('Printify API Key', 'wp-woocommerce-printify-sync') ?>
                </label>
                <div class="input-group">
                    <input type="password" 
                           class="form-control" 
                           id="printify_api_key" 
                           required>
                    <button class="btn btn-outline-secondary" 
                            type="button" 
                            id="toggle_api_key">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <button type="button" 
                        class="btn btn-primary" 
                        id="test_connection">
                    <?= __('Test Connection', 'wp-woocommerce-printify-sync') ?>
                </button>
            </div>
        </div>

        <!-- Sync Settings -->
        <div class="mb-4">
            <h3 class="h5 mb-3"><?= __('Sync Settings', 'wp-woocommerce-printify-sync') ?></h3>
            
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" 
                           type="checkbox" 
                           id="auto_sync_enabled">
                    <label class="form-check-label" for="auto_sync_enabled">
                        <?= __('Enable Automatic Sync', 'wp-woocommerce-printify-sync') ?>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <?= __('Save Settings', 'wp-woocommerce-printify-sync') ?>
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('wpps-settings-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (!form.checkValidity()) {
            event.stopPropagation();
        }
        form.classList.add('was-validated');
        // TODO: Add AJAX save
    });

    // Test connection handler
    document.getElementById('test_connection').addEventListener('click', function() {
        // TODO: Add AJAX test connection
        wppsAdmin.showToast('<?= __('Testing connection...', 'wp-woocommerce-printify-sync') ?>');
    });
});
</script>
