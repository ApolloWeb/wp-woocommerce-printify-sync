<!-- Notifications Menu -->
<div class="dropdown">
    <button class="btn btn-link text-muted position-relative p-0" data-bs-toggle="dropdown">
        <i class="fas fa-bell fs-5"></i>
        @if(isset($notifications_count) && $notifications_count > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            {{ $notifications_count }} <span class="visually-hidden">notifications</span>
        </span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end mt-2">
        <h6 class="dropdown-header">{{ __('Notifications', 'wp-woocommerce-printify-sync') }}</h6>
        <div class="dropdown-divider"></div>
        @forelse($notifications ?? [] as $notification)
        <a class="dropdown-item" href="{{ $notification['link'] ?? '#' }}">
            <small class="text-muted d-block">{{ $notification['time'] }}</small>
            {{ $notification['message'] }}
        </a>
        @empty
        <div class="dropdown-item text-muted">{{ __('No new notifications', 'wp-woocommerce-printify-sync') }}</div>
        @endforelse
    </div>
</div>