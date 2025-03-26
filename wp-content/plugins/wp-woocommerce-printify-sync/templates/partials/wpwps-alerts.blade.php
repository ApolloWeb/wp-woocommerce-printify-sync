@if(isset($alerts) && count($alerts))
    <div class="wpwps-alerts position-fixed top-0 end-0 p-3" style="z-index: 1100; margin-top: 4rem;">
        @foreach($alerts as $alert)
            <div class="toast show mb-2 wpwps-toast" role="alert">
                <div class="toast-header">
                    <i class="fas fa-{{ $alert['icon'] ?? 'info-circle' }} me-2"></i>
                    <strong class="me-auto">{{ $alert['title'] ?? 'Notification' }}</strong>
                    <small class="text-muted">{{ $alert['time'] ?? 'just now' }}</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ $alert['message'] }}
                </div>
            </div>
        @endforeach
    </div>
@endif

<style>
.wpwps-toast {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.wpwps-toast .toast-header {
    background: transparent;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.wpwps-toast.success .toast-header i {
    color: #28a745;
}

.wpwps-toast.error .toast-header i {
    color: #dc3545;
}

.wpwps-toast.warning .toast-header i {
    color: #ffc107;
}

.wpwps-toast.info .toast-header i {
    color: var(--wpwps-primary);
}
</style>