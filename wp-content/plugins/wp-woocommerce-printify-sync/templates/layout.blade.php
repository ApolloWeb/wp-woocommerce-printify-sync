<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
</head>
<body class="wp-admin wp-core-ui">
    <div class="wrap">
        @include('partials.dashboard.header')
        
        <div class="container-fluid mt-4">
            @yield('content')
        </div>

        @include('partials.dashboard.footer')
    </div>
</body>
</html>