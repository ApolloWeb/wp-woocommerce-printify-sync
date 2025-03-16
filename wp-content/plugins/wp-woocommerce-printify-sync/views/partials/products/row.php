<tr data-product-id="{{ $product->id }}">
    <td>
        <input type="checkbox" class="form-check-input product-select" value="{{ $product->id }}">
    </td>
    <td>
        <div class="d-flex align-items-center gap-3">
            <img src="{{ $product->thumbnail }}" alt="{{ $product->name }}" class="product-thumbnail">
            <div>
                <h6 class="mb-0">{{ $product->name }}</h6>
                <small class="text-muted">SKU: {{ $product->sku }}</small>
            </div>
        </div>
    </td>
    <td>
        <button class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#variant-modal" data-product-id="{{ $product->id }}">
            {{ count($product->variants) }} variants
        </button>
    </td>
    <td>
        <div class="d-flex align-items-center">
            <i class="fas fa-clock text-muted me-2"></i>
            {{ human_time_diff(strtotime($product->last_synced)) }} ago
        </div>
    </td>
    <td>
        <span class="badge bg-{{ $product->sync_status_color }}">
            {{ $product->sync_status }}
        </span>
    </td>
    <td>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-primary sync-product" title="Sync">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary view-details" title="Details">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger delete-product" title="Delete">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </td>
</tr>