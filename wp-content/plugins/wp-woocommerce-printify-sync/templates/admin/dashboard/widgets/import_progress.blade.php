<div class="wpwps-widget wpwps-import-progress card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="card-title mb-0">Import Progress</h6>
            <span class="badge bg-primary">{{ $imported }} / {{ $total }}</span>
        </div>
        
        <div class="progress mb-3" style="height: 20px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" 
                 style="width: {{ $progress }}%"
                 aria-valuenow="{{ $progress }}" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                {{ number_format($progress, 1) }}%
            </div>
        </div>

        <div class="d-flex justify-content-between text-muted small">
            <span>Pending Items: {{ $pending }}</span>
            <span><i class="fas fa-clock me-1"></i> Auto-refreshing</span>
        </div>
    </div>
</div>
