<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @wp_head
</head>
<body class="wpwps-admin">
    <div class="wrap">
        @include('partials.dashboard.header')
        
        <div class="wpwps-content">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h1 class="h3 mb-0">{{ $title ?? 'Printify Sync' }}</h1>
                        <p class="text-muted">{{ $subtitle ?? 'Manage your Printify and WooCommerce integration' }}</p>
                    </div>
                </div>
                
                @yield('content')
            </div>
        </div>
        
        @include('partials.dashboard.footer')
    </div>
    
    <!-- Toast container for notifications -->
    <div id="wpwps-toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050"></div>
    
    @wp_footer
</body>
</html>