<div class="wpwps-widget wpwps-email-queue">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">{{ __('Email Queue Status', 'wp-woocommerce-printify-sync') }}</h5>
                <span class="badge bg-{{ $email_queue['pending'] > 0 ? 'warning' : 'success' }}">
                    {{ $email_queue['pending'] }} {{ __('Pending', 'wp-woocommerce-printify-sync') }}
                </span>
            </div>
            
            <div class="row g-3">
                <div class="col-4">
                    <div class="text-center">
                        <h3>{{ $email_queue['processed'] }}</h3>
                        <small>{{ __('Processed (24h)', 'wp-woocommerce-printify-sync') }}</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="text-center">
                        <h3>{{ $email_queue['pending'] }}</h3>
                        <small>{{ __('Pending', 'wp-woocommerce-printify-sync') }}</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="text-center">
                        <h3>{{ $email_queue['failed'] }}</h3>
                        <small>{{ __('Failed', 'wp-woocommerce-printify-sync') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
