<div class="wpwps-sidebar mb-4">
    <div class="list-group">
        <a href="@adminUrl('admin.php?page=wpwps-dashboard')" 
           class="list-group-item list-group-item-action {{ request()->get('page') === 'wpwps-dashboard' ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt me-2"></i> @e__('Dashboard')
        </a>
        <a href="@adminUrl('admin.php?page=wpwps-settings')" 
           class="list-group-item list-group-item-action {{ request()->get('page') === 'wpwps-settings' ? 'active' : '' }}">
            <i class="fas fa-cog me-2"></i> @e__('Settings')
        </a>
        <a href="@adminUrl('admin.php?page=wpwps-products')" 
           class="list-group-item list-group-item-action {{ request()->get('page') === 'wpwps-products' ? 'active' : '' }}">
            <i class="fas fa-box me-2"></i> @e__('Products')
        </a>
        <a href="@adminUrl('admin.php?page=wpwps-orders')" 
           class="list-group-item list-group-item-action {{ request()->get('page') === 'wpwps-orders' ? 'active' : '' }}">
            <i class="fas fa-shopping-cart me-2"></i> @e__('Orders')
        </a>
        <a href="@adminUrl('admin.php?page=wpwps-support')" 
           class="list-group-item list-group-item-action {{ request()->get('page') === 'wpwps-support' ? 'active' : '' }}">
            <i class="fas fa-ticket-alt me-2"></i> @e__('Support Tickets')
        </a>
        <a href="@adminUrl('admin.php?page=wpwps-logs')" 
           class="list-group-item list-group-item-action {{ request()->get('page') === 'wpwps-logs' ? 'active' : '' }}">
            <i class="fas fa-file-alt me-2"></i> @e__('Logs')
        </a>
    </div>
    
    @if(isset($sync_status) && $sync_status)
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">@e__('Sync Status')</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span>@e__('Last Sync'):</span>
                <span>{{ $sync_status['last_sync'] ? human_time_diff($sync_status['last_sync'], time()) : __('Never', WPWPS_TEXT_DOMAIN) }}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span>@e__('API Health'):</span>
                <span class="badge bg-{{ isset($api_health) && $api_health['status'] === 'connected' ? 'success' : 'danger' }}">
                    {{ isset($api_health) && $api_health['status'] === 'connected' ? __('Connected', WPWPS_TEXT_DOMAIN) : __('Disconnected', WPWPS_TEXT_DOMAIN) }}
                </span>
            </div>
        </div>
    </div>
    @endif
    
    <div class="mt-4">
        <button class="btn btn-primary w-100 mb-2" id="wpwps-sync-products">
            <i class="fas fa-sync me-2"></i> @e__('Sync Products')
        </button>
        <button class="btn btn-secondary w-100" id="wpwps-sync-orders">
            <i class="fas fa-exchange-alt me-2"></i> @e__('Sync Orders')
        </button>
    </div>
</div>