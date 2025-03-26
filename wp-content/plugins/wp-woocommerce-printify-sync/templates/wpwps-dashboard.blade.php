<?php /* @var array $user */ ?>
<!-- Admin Notices -->
<div class="wpwps-notices mb-4">
    @if($api_health['printify']['healthy'] === false)
    <div class="alert alert-danger d-flex align-items-center fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <div>
            {{ __('Printify API connection is currently unavailable.', 'wp-woocommerce-printify-sync') }}
            <a href="#" class="alert-link" data-error="{{ $api_health['printify']['error'] }}">View details</a>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    
    @if($api_health['webhook']['healthy'] === false)
    <div class="alert alert-warning d-flex align-items-center fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <div>
            {{ __('Webhook communication is experiencing issues.', 'wp-woocommerce-printify-sync') }}
            <a href="#" class="alert-link">Check webhook settings</a>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
</div>

<div class="wpwps-app">
    <!-- Navigation -->
    @include('partials.shared.navigation')

    <div class="wrap">
        <h1>{{ __('Printify Sync Dashboard', 'wp-woocommerce-printify-sync') }}</h1>

        <!-- Status Widgets -->
        @include('partials.dashboard.status-widgets')

        <!-- Charts -->
        @include('partials.dashboard.charts')
    </div>
</div>

<!-- Modals -->
@include('partials.dashboard.modals')

<!-- Toast Notifications -->
@include('partials.shared.toast')