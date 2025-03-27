<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ get_admin_page_title() }}</title>
    <?php wp_head(); ?>
</head>
<body class="wpwps">
    <div class="wpwps-container">
        @yield('content')
    </div>
    @yield('scripts')
    <?php wp_footer(); ?>
</body>
</html>