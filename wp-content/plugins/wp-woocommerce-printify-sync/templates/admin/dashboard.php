<div class="wrap wpps-dashboard">
    <h1><i class="fas fa-tshirt wpps-icon"></i> {{ $title }}</h1>
    
    @if($isConnected)
        <div class="notice notice-success">
            <p><i class="fas fa-check-circle wpps-icon"></i> {{ __('Connected to Printify API', 'wp-woocommerce-printify-sync') }}</p>
        </div>
    @else
        <div class="notice notice-error">
            <p><i class="fas fa-exclamation-triangle wpps-icon"></i> {{ __('Not connected to Printify API', 'wp-woocommerce-printify-sync') }}</p>
        </div>
    @endif
    
    <div class="wpps-stats-container">
        <div class="wpps-stats-card">
            <h3><i class="fas fa-box-open wpps-icon"></i> {{ __('Products', 'wp-woocommerce-printify-sync') }}</h3>
            <p class="wpps-stats-number">{{ $totalProducts }}</p>
            <a href="{{ admin_url('admin.php?page=wpps-products') }}" class="button">
                <i class="fas fa-eye wpps-icon"></i> {{ __('View Products', 'wp-woocommerce-printify-sync') }}
            </a>
        </div>
        
        <div class="wpps-stats-card">
            <h3><i class="fas fa-shopping-cart wpps-icon"></i> {{ __('Orders', 'wp-woocommerce-printify-sync') }}</h3>
            <p class="wpps-stats-number">{{ $totalOrders }}</p>
            <a href="{{ admin_url('edit.php?post_type=shop_order') }}" class="button">
                <i class="fas fa-eye wpps-icon"></i> {{ __('View Orders', 'wp-woocommerce-printify-sync') }}
            </a>
        </div>
        
        <div class="wpps-stats-card">
            <h3><i class="fas fa-dollar-sign wpps-icon"></i> {{ __('Revenue', 'wp-woocommerce-printify-sync') }}</h3>
            <p class="wpps-stats-number">{!! wc_price($totalRevenue) !!}</p>
            <a href="{{ admin_url('admin.php?page=wc-reports&tab=orders&range=month') }}" class="button">
                <i class="fas fa-chart-line wpps-icon"></i> {{ __('View Reports', 'wp-woocommerce-printify-sync') }}
            </a>
        </div>
    </div>
    
    <div class="wpps-widget">
        <h2><i class="fas fa-sync wpps-icon"></i> {{ __('Sync Status', 'wp-woocommerce-printify-sync') }}</h2>
        
        <div class="wpps-sync-status">
            <p><i class="far fa-clock wpps-icon"></i> {{ __('Last sync:', 'wp-woocommerce-printify-sync') }} <strong>{{ $lastSync }}</strong></p>
            
            <button id="wpps-sync-button" class="button button-primary">
                <i class="fas fa-sync-alt wpps-icon"></i> {{ __('Sync Products Now', 'wp-woocommerce-printify-sync') }}
            </button>
        </div>
        
        <div id="wpps-sync-progress" class="wpps-progress-bar" style="display: none;">
            <div class="wpps-progress-bar-inner"></div>
        </div>
        
        <div id="wpps-sync-response"></div>
    </div>
    
    <div class="wpps-widget">
        <h2><i class="fas fa-shopping-bag wpps-icon"></i> {{ __('Recent Orders', 'wp-woocommerce-printify-sync') }}</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag wpps-icon"></i> {{ __('Order ID', 'wp-woocommerce-printify-sync') }}</th>
                    <th><i class="far fa-calendar-alt wpps-icon"></i> {{ __('Date', 'wp-woocommerce-printify-sync') }}</th>
                    <th><i class="fas fa-info-circle wpps-icon"></i> {{ __('Status', 'wp-woocommerce-printify-sync') }}</th>
                    <th><i class="fas fa-money-bill-wave wpps-icon"></i> {{ __('Total', 'wp-woocommerce-printify-sync') }}</th>
                </tr>
            </thead>
            <tbody>
                @if(count($recentOrders) > 0)
                    @foreach($recentOrders as $order)
                        <tr>
                            <td>
                                <a href="{{ admin_url('post.php?post=' . $order->get_id() . '&action=edit') }}">
                                    #{{ $order->get_order_number() }}
                                </a>
                            </td>
                            <td>{{ $order->get_date_created()->date_i18n(get_option('date_format')) }}</td>
                            <td>
                                @php
                                    $status = $order->get_status();
                                    $statusClass = '';
                                    
                                    if ($status === 'completed') {
                                        $statusClass = 'wpps-status-success';
                                    } elseif ($status === 'processing') {
                                        $statusClass = 'wpps-status-warning';
                                    } elseif ($status === 'failed' || $status === 'cancelled') {
                                        $statusClass = 'wpps-status-error';
                                    }
                                @endphp
                                <span class="wpps-status {{ $statusClass }}">
                                    {{ wc_get_order_status_name($status) }}
                                </span>
                            </td>
                            <td>{!! $order->get_formatted_order_total() !!}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4">
                            <i class="fas fa-info-circle wpps-icon"></i> {{ __('No orders found', 'wp-woocommerce-printify-sync') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>