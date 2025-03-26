<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="products-table">
            <thead class="bg-light">
                <tr>
                    <th width="1%">
                        <input type="checkbox" class="form-check-input" id="select-all">
                    </th>
                    <th width="80">{{ __('Image', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('Title', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('SKU', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('Price', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('Status', 'wp-woocommerce-printify-sync') }}</th>
                    <th>{{ __('Last Sync', 'wp-woocommerce-printify-sync') }}</th>
                    <th width="1%" class="text-end">{{ __('Actions', 'wp-woocommerce-printify-sync') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input product-select" 
                               value="{{ $product['id'] }}">
                    </td>
                    <td>
                        <img src="{{ $product['thumbnail_url'] }}" class="rounded" width="60" height="60"
                             alt="{{ $product['title'] }}">
                    </td>
                    <td>
                        <div class="fw-medium">{{ $product['title'] }}</div>
                        <small class="text-muted">ID: {{ $product['id'] }}</small>
                    </td>
                    <td>{{ $product['sku'] }}</td>
                    <td>{{ wc_price($product['price']) }}</td>
                    <td>
                        <span class="badge bg-{{ $product['status'] === 'published' ? 'success' : 'warning' }}">
                            {{ ucfirst($product['status']) }}
                        </span>
                        @if($product['sync_status'])
                        <span class="badge bg-{{ $product['sync_status'] === 'synced' ? 'info' : 
                            ($product['sync_status'] === 'failed' ? 'danger' : 'warning') }}">
                            {{ ucfirst($product['sync_status']) }}
                        </span>
                        @endif
                    </td>
                    <td>
                        @if($product['last_sync'])
                            <span title="{{ $product['last_sync'] }}">
                                {{ human_time_diff(strtotime($product['last_sync'])) }}
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
                                <button class="dropdown-item sync-product" data-id="{{ $product['id'] }}">
                                    <i class="fas fa-sync me-2"></i>{{ __('Sync Now', 'wp-woocommerce-printify-sync') }}
                                </button>
                                <a href="{{ $product['edit_url'] }}" class="dropdown-item" target="_blank">
                                    <i class="fas fa-edit me-2"></i>{{ __('Edit', 'wp-woocommerce-printify-sync') }}
                                </a>
                                <a href="{{ $product['view_url'] }}" class="dropdown-item" target="_blank">
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
                            <i class="fas fa-box fa-2x mb-2"></i>
                            <p class="mb-0">{{ __('No products found', 'wp-woocommerce-printify-sync') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>