<div class="wpwps-widget wpwps-email-queue card">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <div>
                <h6 class="text-muted">Pending Emails</h6>
                <h3>{{ $pending }}</h3>
            </div>
            <div>
                <h6 class="text-muted">Failed Emails</h6>
                <h3 class="text-danger">{{ $failed }}</h3>
            </div>
        </div>
        <small class="text-muted">Last processed: {{ $last_run }}</small>
    </div>
</div>
