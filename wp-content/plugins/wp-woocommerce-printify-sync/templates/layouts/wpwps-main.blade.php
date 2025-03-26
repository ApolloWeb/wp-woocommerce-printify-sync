<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - WooCommerce Printify Sync</title>
    @include('partials.wpwps-head')
    @yield('additional-css')
</head>
<body class="wpwps-admin">
    @include('partials.wpwps-navbar')
    
    <div class="wpwps-wrapper d-flex">
        @include('partials.wpwps-sidebar')
        
        <div id="wpwps-content" class="flex-grow-1 p-4">
            @include('partials.wpwps-alerts')
            <h1 class="wpwps-page-title mb-4">@yield('page-title')</h1>
            @yield('content')
        </div>
    </div>

    @include('partials.wpwps-footer')
    @yield('additional-js')
</body>
</html>