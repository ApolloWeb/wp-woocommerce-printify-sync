<div class="wpps-widget">
    <h2>{{ __('Sync Status', 'wp-woocommerce-printify-sync') }}</h2>
    
    <div class="wpps-sync-status">
        <p>{{ __('Last sync:', 'wp-woocommerce-printify-sync') }} <strong>{{ $lastSync }}</strong></p>
        
        <button id="wpps-sync-button" class="button button-primary">
            {{ __('Sync Products Now', 'wp-woocommerce-printify-sync') }}
        </button>
        
        <div id="wpps-sync-progress" class="wpps-progress-bar" style="display: none;">
            <div class="wpps-progress-bar-inner"></div>
        </div>
    </div>
    
    <div id="wpps-sync-response"></div>
    
    @if(count($syncErrors) > 0)
        <div class="wpps-error-list">
            <h3>{{ __('Recent Sync Errors', 'wp-woocommerce-printify-sync') }}</h3>
            <ul>
                @foreach($syncErrors as $error)
                    <li>{{ $error['message'] }} - {{ $error['date'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>