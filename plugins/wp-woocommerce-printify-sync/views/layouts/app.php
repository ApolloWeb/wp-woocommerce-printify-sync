<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Printify Sync' }}</title>
    @yield('styles')
</head>
<body class="wpwps-admin">
    <div class="wpwps-wrapper">
        @include('partials.sidebar')

        <main class="wpwps-main">
            @include('partials.topnav')

            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        <h1 class="content-title">{{ $title ?? '' }}</h1>
                        @yield('header-actions')
                    </div>
                </div>

                <div class="content">
                    <div class="container-fluid">
                        @yield('content')
                    </div>
                </div>
            </div>
        </main>
    </div>

    @include('partials.modals')
    @yield('scripts')
</body>
</html>