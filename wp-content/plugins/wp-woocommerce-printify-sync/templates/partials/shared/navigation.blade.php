<nav class="navbar navbar-expand sticky-top bg-white border-bottom">
    <div class="container-fluid">
        <h1 class="navbar-brand mb-0">{{ __('Printify Sync', 'wp-woocommerce-printify-sync') }}</h1>
        
        <div class="d-flex align-items-center gap-3">
            <!-- Global Search -->
            <div class="position-relative d-none d-md-block">
                <input type="search" class="form-control form-control-sm ps-4" 
                       placeholder="{{ __('Search...', 'wp-woocommerce-printify-sync') }}">
                <i class="fas fa-search position-absolute start-0 top-50 translate-middle-y ms-2 text-muted"></i>
            </div>
            
            @include('partials.shared.notifications-menu')
            @include('partials.shared.user-menu')
        </div>
    </div>
</nav>