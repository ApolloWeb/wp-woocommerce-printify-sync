<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ get_admin_page_title() }}</title>
</head>
<body>
    <div class="wrap wpwps-admin">
        <div class="container-fluid px-0">
            <div class="row g-0">
                <!-- Content -->
                <div class="col-md-12 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>{{ get_admin_page_title() }}</h1>
                        <div class="wpwps-actions">
                            @yield('actions')
                        </div>
                    </div>
                    
                    @if(isset($saved) && $saved)
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle me-2"></i> @__('Changes saved successfully.')
                        </div>
                    @endif
                    
                    @if(isset($error) && !empty($error))
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-circle me-2"></i> {{ $error }}
                        </div>
                    @endif
                    
                    <div class="card">
                        <div class="card-body">
                            @yield('content')
                        </div>
                    </div>
                    
                    <footer class="mt-4 text-muted">
                        <small>WP WooCommerce Printify Sync v{{ WPWPS_VERSION }}</small>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</body>
</html>