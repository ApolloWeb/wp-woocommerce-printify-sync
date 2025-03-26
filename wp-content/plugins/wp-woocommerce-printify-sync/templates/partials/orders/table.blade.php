<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="orders-table">
            <thead class="bg-light">
                <tr>
                    <th width="1%">
                        <input type="checkbox" class="form-check-input" id="select-all">
                    </th>
                    <th>{{ __('Order', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('Customer', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('Products', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('Total', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('Status', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('Last Sync', 'wp-woocommerce-printify-sync') }}</th>
                    <th width="1%" class="text-end">{{ __('Actions', 'wp-woocommerce-printify-sync') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input order-select" 
                               value="{{ $order['id'] }}">
                    </td>
                    <td>
                        <div class="fw-medium">#{{ $order['order_number'] }}</div>
                        <small class="text-muted">{{ $order['date_created'] }}</small>
                    </td>
                    <td>
                        <div>{{ $order['billing']['first_name'] }} {{ $order['billing']['last_name'] }}</div>
                        <small class="text-muted">{{ $order['billing']['email'] }}</small>
                    </td>
                    <td>
                        <div>{{ count($order['line_items']) }} {{ __('items', 'wp-woocommerce-printify-sync') }}</div>
                        <small class="text-muted">{{ $order['line_items'][0]['name'] }}{{ count($order['line_items']) > 1 ? '...' : '' }}</small>
                    </td>
                    <td>{{ wc_price($order['total']) }}</td>
                    <td>
                        <span class="badge bg-{{ $order['status'] === 'completed' ? 'success' : 
                            ($order['status'] === 'processing' ? 'info' : 
                            ($order['status'] === 'cancelled' ? 'danger' : 'warning')) }}">
                            {{ ucfirst($order['status']) }}
                        </span>
                        @if($order['sync_status'])
                        <span class="badge bg-{{ $order['sync_status'] === 'synced' ? 'info' : 
                            ($order['sync_status'] === 'failed' ? 'danger' : 'warning') }}">
                            {{ ucfirst($order['sync_status']) }}
                        </span>
                        @endif
                    </td>
                    <td>
                        @if($order['last_sync'])
                            <span title="{{ $order['last_sync'] }}">
                                {{ human_time_diff(strtotime($order['last_sync'])) }}
                            </span>
                        @else
                            {{ __('Never', 'wp-woocommerce-printify-sync') }}
                        @endif
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-link btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <button class="dropdown-item sync-order" data-id="{{ $order['id'] }}">
                                    <i class="fas fa-sync me-2"></i>{{ __('Sync Now', 'wp-woocommerce-printify-sync') }}
                                </button>
                                <a href="{{ $order['edit_url'] }}" class="dropdown-item" target="_blank">
                                    <i class="fas fa-edit me-2"></i>{{ __('Edit', 'wp-woocommerce-printify-sync') }}
                                </a>
                                <a href="{{ $order['view_url'] }}" class="dropdown-item" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>{{ __('View', 'wp-woocommerce-printify-sync') }}
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <p class="mb-0">{{ __('No orders found', 'wp-woocommerce-printify-sync') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>