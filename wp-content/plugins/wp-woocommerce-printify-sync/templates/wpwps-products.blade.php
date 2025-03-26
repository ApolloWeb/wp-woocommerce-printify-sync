@extends('layouts.wpwps-main')

@section('title', 'Products')
@section('page-title', 'Product Synchronization')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="wpwps-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Products Overview</h4>
                <button type="button" 
                        class="btn btn-primary" 
                        id="syncProducts">
                    <i class="fas fa-sync-alt me-2"></i>Sync All Products
                </button>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center">
                        <h3 class="mb-2">{{ $total_products }}</h3>
                        <p class="text-muted mb-0">Total Products</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center">
                        <h3 class="mb-2">{{ $synced_products }}</h3>
                        <p class="text-muted mb-0">Synced Products</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center">
                        <h3 class="mb-2">{{ $total_products - $synced_products }}</h3>
                        <p class="text-muted mb-0">Pending Sync</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="wpwps-card p-4">
            <h4 class="mb-4">Sync History</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Created
                            </td>
                            <td>{{ $sync_stats['created'] }}</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: {{ ($sync_stats['created'] / max(1, $total_products)) * 100 }}%">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fas fa-sync text-primary me-2"></i>
                                Updated
                            </td>
                            <td>{{ $sync_stats['updated'] }}</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" 
                                         role="progressbar" 
                                         style="width: {{ ($sync_stats['updated'] / max(1, $total_products)) * 100 }}%">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                Failed
                            </td>
                            <td>{{ $sync_stats['failed'] }}</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" 
                                         role="progressbar" 
                                         style="width: {{ ($sync_stats['failed'] / max(1, $total_products)) * 100 }}%">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if($last_sync)
                <p class="text-muted mt-3 mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Last synchronized: {{ $last_sync }}
                </p>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="wpwps-card p-4 mb-4">
            <h5 class="mb-3">Sync Settings</h5>
            <form id="syncSettingsForm">
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between">
                        Auto-sync
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="autoSync" 
                                   checked>
                        </div>
                    </label>
                    <div class="form-text">Automatically sync new products</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Sync Frequency</label>
                    <select class="form-select">
                        <option value="hourly">Every hour</option>
                        <option value="daily" selected>Daily</option>
                        <option value="weekly">Weekly</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between">
                        Sync Images
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="syncImages" 
                                   checked>
                        </div>
                    </label>
                    <div class="form-text">Download and sync product images</div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </form>
        </div>

        <div class="wpwps-card p-4">
            <h5 class="mb-3">Quick Actions</h5>
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-download me-2"></i>Export Products
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-upload me-2"></i>Import Products
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-trash-alt me-2"></i>Clear Sync History
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('additional-js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const syncBtn = document.getElementById('syncProducts');
    
    syncBtn?.addEventListener('click', async function() {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Syncing...';

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'wpwps_sync_products',
                    nonce: '{{ wp_create_nonce("wpwps_ajax_nonce") }}'
                })
            });

            const data = await response.json();
            
            if (data.success) {
                showToast('Products synchronized successfully!', 'success');
                // Refresh the page to show updated stats
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Sync failed: ' + data.data.message, 'error');
            }
        } catch (error) {
            showToast('Sync failed: ' + error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // Settings form submission
    document.getElementById('syncSettingsForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        showToast('Settings saved successfully!', 'success');
    });
});
</script>
@endsection