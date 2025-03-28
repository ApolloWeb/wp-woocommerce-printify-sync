<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @wp_head
</head>
<body class="wp-admin wp-core-ui">
    <div class="wrap">
        <h1>{{ $title ?? 'Printify Sync' }}</h1>
        @yield('content')
    </div>
    @wp_footer
</body>
</html>