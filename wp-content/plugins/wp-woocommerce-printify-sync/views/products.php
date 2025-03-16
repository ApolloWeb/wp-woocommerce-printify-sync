@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ plugin_asset('css/pages/products.css') }}">
@endsection

@section('header-actions')
    <div class="d-flex gap-3">
        <div class="input-group w-auto">
            <input type="text" class="form-control" id="product-search" placeholder="Search products...">
            <button class="btn btn-outline-secondary" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
        <button class="btn btn-primary" id="sync-selected">
            <i class="fas fa-sync"></i> Sync Selected
        </button>
        <button class="btn btn-outline-primary" id="filter-products">
            <i class="fas fa-filter"></i> Filters
        </button>
    </div>
@endsection

@section('content')
    <div class="wpwps-card">
        <div class="card-body">
            <!-- Filters Panel -->
            @include('partials.products.filters')

            <!-- Products Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="select-all">
                            </th>
                            <th>Product</th>
                            <th>Variants</th>
                            <th>Last Synced</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="products-table">
                        @foreach($products as $product)
                            @include('partials.products.row', ['product' => $product])
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @include('partials.products.pagination')
        </div>
    </div>

    <!-- Modals -->
    @include('partials.products.sync-modal')
    @include('partials.products.variant-modal')
@endsection

@section('scripts')
    <script src="{{ plugin_asset('js/pages/products.js') }}"></script>
    <script>
        WPWPS.Products.init({
            totalProducts: {{ $totalProducts }},
            currentPage: {{ $currentPage }},
            perPage: {{ $perPage }}
        });
    </script>
@endsection