<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
    <div class="container-fluid">
        <span class="navbar-brand">
            <i class="fas fa-sync"></i> {{ __('WP WooCommerce Printify Sync', 'wp-woocommerce-printify-sync') }}
        </span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link{{ request()->get('page') === 'wpwps-dashboard' ? ' active' : '' }}" href="admin.php?page=wpwps-dashboard">
                        <i class="fas fa-tachometer-alt"></i> {{ __('Dashboard', 'wp-woocommerce-printify-sync') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ request()->get('page') === 'wpwps-settings' ? ' active' : '' }}" href="admin.php?page=wpwps-settings">
                        <i class="fas fa-cog"></i> {{ __('Settings', 'wp-woocommerce-printify-sync') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>