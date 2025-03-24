<div class="wpwps-widget wpwps-api-health card">
    <div class="card-body">
        <h6 class="card-title mb-4">System Health</h6>
        
        <div class="row g-3">
            <div class="col-6">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-circle {{ $api_status ? 'text-success' : 'text-danger' }}"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0">API Status</h6>
                        <small class="text-muted">{{ $api_status ? 'Operational' : 'Issues Detected' }}</small>
                    </div>
                </div>
            </div>
            
            <div class="col-6">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-circle {{ $webhook_status ? 'text-success' : 'text-danger' }}"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0">Webhooks</h6>
                        <small class="text-muted">{{ $webhook_status ? 'Active' : 'No Recent Activity' }}</small>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($last_errors))
        <div class="mt-3">
            <div class="alert alert-warning mb-0">
                <h6 class="alert-heading">Recent Issues</h6>
                <ul class="list-unstyled small mb-0">
                    @foreach($last_errors as $error)
                    <li><i class="fas fa-exclamation-triangle me-1"></i> {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <div class="mt-3 pt-3 border-top">
            <small class="text-muted d-flex justify-content-between">
                <span>Rate Limit: {{ $rate_limit['remaining'] }} remaining</span>
                <span>Resets in: {{ round(($rate_limit['reset'] - time()) / 60) }}m</span>
            </small>
        </div>
    </div>
</div>
