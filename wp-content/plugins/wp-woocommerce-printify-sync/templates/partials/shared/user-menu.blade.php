<!-- User Profile Menu -->
<div class="dropdown">
    <button class="btn btn-link p-0 d-flex align-items-center gap-2" data-bs-toggle="dropdown">
        <img src="{{ $user['avatar'] }}" class="rounded-circle" width="32" height="32" 
             alt="{{ $user['display_name'] }}">
        <div class="d-none d-lg-block text-start">
            <div class="fw-medium">{{ $user['display_name'] }}</div>
            <small class="text-muted">{{ ucfirst($user['role']) }}</small>
        </div>
        <i class="fas fa-chevron-down text-muted ms-1"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end mt-2">
        <a class="dropdown-item" href="#">
            <i class="fas fa-user me-2"></i> {{ __('Profile', 'wp-woocommerce-printify-sync') }}
        </a>
        <a class="dropdown-item" href="{{ admin_url('admin.php?page=wpwps-settings') }}">
            <i class="fas fa-cog me-2"></i> {{ __('Settings', 'wp-woocommerce-printify-sync') }}
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="{{ wp_logout_url() }}">
            <i class="fas fa-sign-out-alt me-2"></i> {{ __('Logout', 'wp-woocommerce-printify-sync') }}
        </a>
    </div>
</div>