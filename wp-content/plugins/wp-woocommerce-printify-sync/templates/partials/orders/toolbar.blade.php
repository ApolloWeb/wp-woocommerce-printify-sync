<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex gap-2">
        <select class="form-select" id="status-filter">
            <option value="">{{ __('All Statuses', 'wp-woocommerce-printify-sync') }}</option>
            <option value="pending">{{ __('Pending', 'wp-woocommerce-printify-sync') }}</option>
            <option value="processing">{{ __('Processing', 'wp-woocommerce-printify-sync') }}</option>
            <option value="completed">{{ __('Completed', 'wp-woocommerce-printify-sync') }}</option>
            <option value="cancelled">{{ __('Cancelled', 'wp-woocommerce-printify-sync') }}</option>
            <option value="failed">{{ __('Failed', 'wp-woocommerce-printify-sync') }}</option>
        </select>

        <input type="text" class="form-control" id="order-search" 
               placeholder="{{ __('Search orders...', 'wp-woocommerce-printify-sync') }}">

        <div class="input-group">
            <input type="date" class="form-control" id="date-start">
            <span class="input-group-text">{{ __('to', 'wp-woocommerce-printify-sync') }}</span>
            <input type="date" class="form-control" id="date-end">
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" id="bulk-sync" disabled>
            <i class="fas fa-sync me-2"></i>{{ __('Sync Selected', 'wp-woocommerce-printify-sync') }}
        </button>
        <select class="form-select" id="per-page" style="width: auto;">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>
</div>