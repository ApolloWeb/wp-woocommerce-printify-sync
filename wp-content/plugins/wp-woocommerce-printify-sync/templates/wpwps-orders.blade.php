<div class="wpwps-app">
    <!-- Navigation -->
    @include('partials.shared.navigation')

    <div class="wrap">
        <h1>{{ __('Orders', 'wp-woocommerce-printify-sync') }}</h1>

        <!-- Orders Toolbar -->
        @include('partials.orders.toolbar')

        <!-- Orders Table -->
        @include('partials.orders.table')

        <!-- Orders Pagination -->
        @include('partials.orders.pagination')
    </div>
</div>

<!-- Orders Modals -->
@include('partials.orders.modals')

<!-- Toast Notifications -->
@include('partials.shared.toast')