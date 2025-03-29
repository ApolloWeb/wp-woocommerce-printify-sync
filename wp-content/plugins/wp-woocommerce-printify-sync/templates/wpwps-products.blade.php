@extends('layout')

@section('title', $title)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                    <button type="button" class="btn btn-primary" id="syncAllProducts">
                        <i class="fas fa-sync"></i> Sync All Products
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="productsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Printify ID</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                            <tr>
                                <td>{{ $product['id'] }}</td>
                                <td>{{ $product['title'] }}</td>
                                <td>{{ $product['printify_id'] }}</td>
                                <td>${{ number_format($product['price'], 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $product['status'] === 'publish' ? 'success' : 'warning' }}">
                                        {{ $product['status'] }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info sync-product" data-printify-id="{{ $product['printify_id'] }}">
                                        <i class="fas fa-sync"></i> Sync
                                    </button>
                                    <a href="{{ admin_url('post.php?post=' . $product['id'] . '&action=edit') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No products found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection