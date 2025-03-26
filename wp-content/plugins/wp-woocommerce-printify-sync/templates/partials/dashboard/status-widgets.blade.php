<!-- Status Summary Row -->
<div class="row mt-4">
    <!-- Email Queue Widget -->
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    <i class="fas fa-envelope me-2"></i>{{ __('Email Queue', 'wp-woocommerce-printify-sync') }}
                </h6>
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ __('Queued', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge bg-primary" id="email-queued">{{ $email_queue['queued'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ __('Sent (24h)', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge bg-success" id="email-sent">{{ $email_queue['sent_24h'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Failed (24h)', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge bg-danger" id="email-failed">{{ $email_queue['failed_24h'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Queue Widget -->
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    <i class="fas fa-upload me-2"></i>{{ __('Import Queue', 'wp-woocommerce-printify-sync') }}
                </h6>
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ __('Pending', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge bg-warning" id="import-pending">{{ $import_queue['pending'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ __('Running', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge bg-info" id="import-running">{{ $import_queue['running'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Completed (24h)', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge bg-success" id="import-completed">{{ $import_queue['completed_24h'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync Status Widget -->
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    <i class="fas fa-sync me-2"></i>{{ __('Sync Status', 'wp-woocommerce-printify-sync') }}
                </h6>
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ __('Status', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge" id="sync-status" 
                            data-status="{{ $sync_status['status'] }}">{{ ucfirst($sync_status['status']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ __('Last Sync', 'wp-woocommerce-printify-sync') }}</span>
                        <span id="sync-last">{{ $sync_status['last_sync'] }}</span>
                    </div>
                    @if($sync_status['status'] === 'running')
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                            role="progressbar" 
                            style="width: {{ $sync_status['progress'] }}%"
                            id="sync-progress">
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- API Health Widget -->
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    <i class="fas fa-heartbeat me-2"></i>{{ __('API Health', 'wp-woocommerce-printify-sync') }}
                </h6>
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ __('Printify API', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge" id="api-health-printify" 
                            data-healthy="{{ $api_health['printify']['healthy'] ? 'true' : 'false' }}">
                            @if($api_health['printify']['healthy'])
                                <i class="fas fa-check-circle text-success"></i>
                            @else
                                <i class="fas fa-exclamation-circle text-danger"></i>
                            @endif
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ __('Webhooks', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge" id="api-health-webhook" 
                            data-healthy="{{ $api_health['webhook']['healthy'] ? 'true' : 'false' }}">
                            @if($api_health['webhook']['healthy'])
                                <i class="fas fa-check-circle text-success"></i>
                            @else
                                <i class="fas fa-exclamation-circle text-danger"></i>
                            @endif
                        </span>
                    </div>
                    @if($api_health['printify']['rate_limit'])
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Rate Limit', 'wp-woocommerce-printify-sync') }}</span>
                        <span class="badge bg-info" id="api-rate-limit">{{ $api_health['printify']['rate_limit'] }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>