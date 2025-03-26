<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <!-- Search -->
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="search" class="form-control border-start-0" id="product-search" 
                           placeholder="{{ __('Search products...', 'wp-woocommerce-printify-sync') }}">
                </div>
            </div>

            <!-- Filters -->
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <select class="form-select" id="sync-status-filter">
                        <option value="">{{ __('All Sync Status', 'wp-woocommerce-printify-sync') }}</option>
                        <option value="pending">{{ __('Pending', 'wp-woocommerce-printify-sync') }}</option>
                        <option value="synced">{{ __('Synced', 'wp-woocommerce-printify-sync') }}</option>
                        <option value="failed">{{ __('Failed', 'wp-woocommerce-printify-sync') }}</option>
                    </select>
                    <select class="form-select" id="published-status-filter">
                        <option value="">{{ __('All Publish Status', 'wp-woocommerce-printify-sync') }}</option>
                        <option value="published">{{ __('Published', 'wp-woocommerce-printify-sync') }}</option>
                        <option value="draft">{{ __('Draft', 'wp-woocommerce-printify-sync') }}</option>
                    </select>
                </div>
            </div>

            <!-- Actions -->
            <div class="col-md-4 text-end">
                <button type="button" class="btn btn-primary" id="bulk-sync" disabled>
                    <i class="fas fa-sync me-2"></i>{{ __('Sync Selected', 'wp-woocommerce-printify-sync') }}
                </button>
                <button type="button" class="btn btn-success" id="sync-all">
                    <i class="fas fa-sync-alt me-2"></i>{{ __('Sync All', 'wp-woocommerce-printify-sync') }}
                </button>
            </div>
        </div>
    </div>
</div>