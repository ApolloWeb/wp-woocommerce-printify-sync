<div class="wrap wpwps-settings-page">
    <h1>{{ __('Printify Sync Settings', 'wp-woocommerce-printify-sync') }}</h1>
    
    <div class="alert alert-info">
        {{ __('Configure your Printify API connection and other plugin settings.', 'wp-woocommerce-printify-sync') }}
    </div>
    
    @include('admin.partials.printify-settings')
    
    @include('admin.partials.openai-settings')
</div>
