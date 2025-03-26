<div class="wpwps-app">
    <!-- Navigation -->
    @include('partials.shared.navigation')

    <div class="wrap">
        <h1>{{ __('Products', 'wp-woocommerce-printify-sync') }}</h1>

        <!-- Products Toolbar -->
        @include('partials.products.toolbar')

        <!-- Products Table -->
        @include('partials.products.table')

        <!-- Products Pagination -->
        @include('partials.products.pagination')
    </div>
</div>

<!-- Products Modals -->
@include('partials.products.modals')

<!-- Toast Notifications -->
@include('partials.shared.toast')