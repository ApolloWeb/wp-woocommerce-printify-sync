@extends('layout')

@section('content')
<div class="container-fluid">
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-box-open"></i> {{ __('Products', 'wp-woocommerce-printify-sync') }}
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="productsChart"></canvas>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Total Products', 'wp-woocommerce-printify-sync') }}</span>
                            <strong>{{ $stats['products']['total'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Synced', 'wp-woocommerce-printify-sync') }}</span>
                            <strong>{{ $stats['products']['synced'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Failed', 'wp-woocommerce-printify-sync') }}</span>
                            <strong>{{ $stats['products']['failed'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart"></i> {{ __('Orders', 'wp-woocommerce-printify-sync') }}
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart"></canvas>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Pending', 'wp-woocommerce-printify-sync') }}</span>
                            <strong>{{ $stats['orders']['pending'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Processing', 'wp-woocommerce-printify-sync') }}</span>
                            <strong>{{ $stats['orders']['processing'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Completed', 'wp-woocommerce-printify-sync') }}</span>
                            <strong>{{ $stats['orders']['completed'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@wpnonce('wpwps_dashboard_nonce')

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productsCtx = document.getElementById('productsChart').getContext('2d');
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    
    new Chart(productsCtx, {
        type: 'pie',
        data: {
            labels: ['{{ __("Synced", "wp-woocommerce-printify-sync") }}', '{{ __("Failed", "wp-woocommerce-printify-sync") }}'],
            datasets: [{
                data: [{{ $stats['products']['synced'] }}, {{ $stats['products']['failed'] }}],
                backgroundColor: ['#28a745', '#dc3545']
            }]
        }
    });
    
    new Chart(ordersCtx, {
        type: 'pie',
        data: {
            labels: [
                '{{ __("Pending", "wp-woocommerce-printify-sync") }}',
                '{{ __("Processing", "wp-woocommerce-printify-sync") }}',
                '{{ __("Completed", "wp-woocommerce-printify-sync") }}'
            ],
            datasets: [{
                data: [
                    {{ $stats['orders']['pending'] }},
                    {{ $stats['orders']['processing'] }},
                    {{ $stats['orders']['completed'] }}
                ],
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745']
            }]
        }
    });
});
</script>
@endsection