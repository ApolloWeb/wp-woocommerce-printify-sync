<!-- Sync Order Modal -->
<div class="modal fade" id="sync-order-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Sync Order', 'wp-woocommerce-printify-sync') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Are you sure you want to sync this order? This will:', 'wp-woocommerce-printify-sync') }}</p>
                <ul class="mb-0">
                    <li>{{ __('Update order status', 'wp-woocommerce-printify-sync') }}</li>
                    <li>{{ __('Sync shipping information', 'wp-woocommerce-printify-sync') }}</li>
                    <li>{{ __('Sync tracking details', 'wp-woocommerce-printify-sync') }}</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel', 'wp-woocommerce-printify-sync') }}
                </button>
                <button type="button" class="btn btn-primary" id="confirm-sync">
                    <i class="fas fa-sync me-2"></i>{{ __('Sync Now', 'wp-woocommerce-printify-sync') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Sync Modal -->
<div class="modal fade" id="bulk-sync-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Sync Orders', 'wp-woocommerce-printify-sync') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Are you sure you want to sync the selected orders? This will:', 'wp-woocommerce-printify-sync') }}</p>
                <ul>
                    <li>{{ __('Update order statuses', 'wp-woocommerce-printify-sync') }}</li>
                    <li>{{ __('Sync shipping information', 'wp-woocommerce-printify-sync') }}</li>
                    <li>{{ __('Sync tracking details', 'wp-woocommerce-printify-sync') }}</li>
                </ul>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ __('Selected orders will be queued for sync and processed in the background.', 'wp-woocommerce-printify-sync') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel', 'wp-woocommerce-printify-sync') }}
                </button>
                <button type="button" class="btn btn-primary" id="confirm-bulk-sync">
                    <i class="fas fa-sync me-2"></i>{{ __('Start Sync', 'wp-woocommerce-printify-sync') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Error Details Modal -->
<div class="modal fade" id="error-details-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Error Details', 'wp-woocommerce-printify-sync') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre class="bg-light p-3 rounded" id="error-details"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('Close', 'wp-woocommerce-printify-sync') }}
                </button>
            </div>
        </div>
    </div>
</div>