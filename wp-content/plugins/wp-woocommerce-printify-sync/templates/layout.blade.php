<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Printify Sync</title>
    <!-- WordPress admin already loads jQuery and other common scripts -->
</head>
<body>
    <div class="wrap wpwps-admin">
        <div class="wpwps-container">
            <div class="wpwps-header mb-4">
                <div class="d-flex align-items-center">
                    <div class="wpwps-logo me-3">
                        <i class="fas fa-sync fa-2x"></i>
                    </div>
                    <div>
                        <h1 class="wp-heading-inline">@yield('page_title', 'WP WooCommerce Printify Sync')</h1>
                    </div>
                </div>
            </div>
            
            <div class="wpwps-content-container">
                <div class="row">
                    <div class="col-md-2">
                        @include('partials.dashboard.sidebar')
                    </div>
                    <div class="col-md-10">
                        @if(isset($notices) && !empty($notices))
                            @foreach($notices as $notice)
                                <div class="alert alert-{{ $notice['type'] }} alert-dismissible fade show" role="alert">
                                    {!! $notice['message'] !!}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endforeach
                        @endif
                        
                        @yield('content')
                    </div>
                </div>
            </div>
            
            <div class="wpwps-footer mt-5 pt-4 border-top">
                <div class="row">
                    <div class="col-md-6">
                        <p>WP WooCommerce Printify Sync v{{ WPWPS_PLUGIN_VERSION }}</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="wpwps-social-icons">
                            <a href="https://facebook.com/apolloweb" target="_blank" rel="noopener noreferrer" class="me-2">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a href="https://instagram.com/apolloweb" target="_blank" rel="noopener noreferrer" class="me-2">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="https://tiktok.com/@apolloweb" target="_blank" rel="noopener noreferrer" class="me-2">
                                <i class="fab fa-tiktok"></i>
                            </a>
                            <a href="https://youtube.com/apolloweb" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @yield('scripts')
</body>
</html>