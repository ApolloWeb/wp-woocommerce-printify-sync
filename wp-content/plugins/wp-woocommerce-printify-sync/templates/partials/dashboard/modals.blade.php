<!-- Error Details Modal -->
<div class="modal fade" id="error-details-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Error Details', 'wp-woocommerce-printify-sync') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre class="bg-light p-3 rounded" id="error-details"></pre>
            </div>
        </div>
    </div>
</div>

<!-- Activity Feed Modal -->
<div class="modal fade" id="activity-details-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-history me-2"></i>
                    {{ __('Activity History', 'wp-woocommerce-printify-sync') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="activity-feed-full p-3" style="max-height: 600px;">
                    <!-- Activity items will be dynamically loaded here -->
                    <div class="placeholder-glow">
                        <div class="placeholder w-100 mb-2"></div>
                        <div class="placeholder w-75 mb-2"></div>
                        <div class="placeholder w-100 mb-2"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" id="load-more-activity">
                    <i class="fas fa-sync me-2"></i>{{ __('Load More', 'wp-woocommerce-printify-sync') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Notifications Modal -->
<div class="modal fade" id="notifications-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-bell me-2"></i>
                    {{ __('Notifications', 'wp-woocommerce-printify-sync') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="list-group list-group-flush">
                    <!-- Notifications will be dynamically loaded here -->
                    @forelse($notifications ?? [] as $notification)
                    <a href="{{ $notification['link'] ?? '#' }}" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ $notification['title'] }}</h6>
                            <small class="text-muted">{{ $notification['time'] }}</small>
                        </div>
                        <p class="mb-1">{{ $notification['message'] }}</p>
                    </a>
                    @empty
                    <div class="list-group-item">
                        <p class="text-muted mb-0">{{ __('No notifications to display', 'wp-woocommerce-printify-sync') }}</p>
                    </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary">
                    {{ __('View All', 'wp-woocommerce-printify-sync') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="help-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-question-circle me-2"></i>
                    {{ __('Quick Help', 'wp-woocommerce-printify-sync') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="fas fa-book me-2"></i>
                        {{ __('Documentation', 'wp-woocommerce-printify-sync') }}
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="fas fa-video me-2"></i>
                        {{ __('Video Tutorials', 'wp-woocommerce-printify-sync') }}
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="fas fa-life-ring me-2"></i>
                        {{ __('Support', 'wp-woocommerce-printify-sync') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>