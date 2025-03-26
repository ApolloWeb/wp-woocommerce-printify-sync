@extends('layouts.wpwps-main')

@section('title', 'Orders')
@section('page-title', 'Order Management')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="wpwps-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Orders Overview</h4>
                <button type="button" class="btn btn-primary" id="syncOrders">
                    <i class="fas fa-sync-alt me-2"></i>Sync Orders
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    <strong>#{{ $order['id'] }}</strong>
                                    <small class="d-block text-muted">Printify: {{ $order['printify_id'] }}</small>
                                </td>
                                <td>{{ $order['customer'] }}</td>
                                <td>
                                    <select class="form-select form-select-sm status-select" 
                                            data-order-id="{{ $order['id'] }}">
                                        @foreach($statuses as $group => $groupStatuses)
                                            <optgroup label="{{ ucwords(str_replace('_', ' ', $group)) }}">
                                                @foreach($groupStatuses as $status)
                                                    <option value="{{ $status['id'] }}" 
                                                            {{ $order['status'] === $status['id'] ? 'selected' : '' }}>
                                                        {{ $status['label'] }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </td>
                                <td>${{ number_format($order['total'], 2) }}</td>
                                <td>{{ $order['date'] }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ admin_url('post.php?post=' . $order['id'] . '&action=edit') }}" 
                                           class="btn btn-outline-primary"
                                           title="Edit Order">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-primary sync-order"
                                                data-order-id="{{ $order['id'] }}"
                                                title="Sync Order">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-box fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">No orders found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="wpwps-card p-4 mb-4">
            <h5 class="mb-3">Sync Statistics</h5>
            <div class="row g-3">
                <div class="col-6">
                    <div class="border rounded p-3 text-center">
                        <h3 class="mb-1">{{ $sync_stats['total'] }}</h3>
                        <p class="text-muted mb-0 small">Total Orders</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-3 text-center">
                        <h3 class="mb-1">{{ $sync_stats['synced'] }}</h3>
                        <p class="text-muted mb-0 small">Synced</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-3 text-center">
                        <h3 class="mb-1">{{ $sync_stats['pending'] }}</h3>
                        <p class="text-muted mb-0 small">Pending</p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-3 text-center">
                        <h3 class="mb-1">{{ $sync_stats['failed'] }}</h3>
                        <p class="text-muted mb-0 small">Failed</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="wpwps-card p-4">
            <h5 class="mb-3">Order Status Guide</h5>
            <div class="list-group list-group-flush">
                @foreach($statuses as $group => $groupStatuses)
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">{{ ucwords(str_replace('_', ' ', $group)) }}</h6>
                        @foreach($groupStatuses as $status)
                            <div class="list-group-item border-0 px-0 py-1">
                                <small>
                                    <i class="fas fa-circle me-2" style="color: var(--wpwps-{{ $group }})"></i>
                                    {{ $status['label'] }}
                                </small>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('additional-css')
<style>
:root {
    --wpwps-pre-production: #fbbf24;
    --wpwps-production: #3b82f6;
    --wpwps-shipping: #10b981;
    --wpwps-refund-reprint: #ef4444;
}

.status-select {
    min-width: 200px;
}
</style>
@endsection

@section('additional-js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const syncBtn = document.getElementById('syncOrders');
    const statusSelects = document.querySelectorAll('.status-select');
    
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
                    action: 'wpwps_sync_orders',
                    nonce: '{{ wp_create_nonce("wpwps_ajax_nonce") }}'
                })
            });

            const data = await response.json();
            
            if (data.success) {
                showToast('Orders synchronized successfully!', 'success');
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

    // Handle status changes
    statusSelects.forEach(select => {
        select.addEventListener('change', async function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            const originalValue = this.getAttribute('data-original-value');

            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'wpwps_update_order_status',
                        nonce: '{{ wp_create_nonce("wpwps_ajax_nonce") }}',
                        order_id: orderId,
                        status: newStatus
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showToast('Order status updated successfully!', 'success');
                    this.setAttribute('data-original-value', newStatus);
                } else {
                    showToast('Status update failed: ' + data.data.message, 'error');
                    this.value = originalValue;
                }
            } catch (error) {
                showToast('Status update failed: ' + error.message, 'error');
                this.value = originalValue;
            }
        });

        // Store original value
        select.setAttribute('data-original-value', select.value);
    });

    // Handle individual order sync
    document.querySelectorAll('.sync-order').forEach(btn => {
        btn.addEventListener('click', async function() {
            const orderId = this.dataset.orderId;
            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'wpwps_sync_single_order',
                        nonce: '{{ wp_create_nonce("wpwps_ajax_nonce") }}',
                        order_id: orderId
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showToast('Order synchronized successfully!', 'success');
                } else {
                    showToast('Sync failed: ' + data.data.message, 'error');
                }
            } catch (error) {
                showToast('Sync failed: ' + error.message, 'error');
            } finally {
                this.disabled = false;
                this.innerHTML = originalHtml;
            }
        });
    });
});
</script>
@endsection