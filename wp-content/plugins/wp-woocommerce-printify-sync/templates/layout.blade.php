<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ get_admin_page_title() }}</title>
</head>
<body>
    <div class="wrap wpwps-admin">
        <!-- Sticky Navbar -->
        <nav class="navbar navbar-expand-lg sticky-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="?page=wpwps-dashboard">
                    <i class="fa fa-sync-alt me-2"></i>
                    Printify Sync
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#wpwpsNavbar" 
                    aria-controls="wpwpsNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa fa-bars"></i>
                </button>
                
                <div class="collapse navbar-collapse" id="wpwpsNavbar">
                    <div class="navbar-search ms-auto me-2">
                        <div class="position-relative">
                            <input type="text" class="form-control" placeholder="Search..." id="navbarSearch">
                            <i class="fa fa-search search-icon"></i>
                        </div>
                    </div>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item position-relative">
                            <a href="#" class="nav-link" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                    3
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notificationsDropdown" style="width: 320px;">
                                <div class="dropdown-header bg-light p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="dropdown-title mb-0">Notifications</h6>
                                        <a href="#" class="text-muted small">Mark all as read</a>
                                    </div>
                                </div>
                                <div class="notifications-body" style="max-height: 300px; overflow-y: auto;">
                                    <a href="#" class="dropdown-item p-3 border-bottom">
                                        <div class="d-flex align-items-center">
                                            <div class="notification-icon bg-primary text-white rounded-circle p-2 me-3">
                                                <i class="fa fa-sync-alt"></i>
                                            </div>
                                            <div class="notification-content">
                                                <p class="mb-1 fw-bold">Sync Completed</p>
                                                <p class="text-muted small mb-0">5 products synchronized</p>
                                                <span class="text-muted smaller">Just now</span>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="dropdown-item p-3 border-bottom">
                                        <div class="d-flex align-items-center">
                                            <div class="notification-icon bg-success text-white rounded-circle p-2 me-3">
                                                <i class="fa fa-shopping-cart"></i>
                                            </div>
                                            <div class="notification-content">
                                                <p class="mb-1 fw-bold">New Order</p>
                                                <p class="text-muted small mb-0">Order #1234 received</p>
                                                <span class="text-muted smaller">2 hours ago</span>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="dropdown-item p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="notification-icon bg-warning text-white rounded-circle p-2 me-3">
                                                <i class="fa fa-exclamation-triangle"></i>
                                            </div>
                                            <div class="notification-content">
                                                <p class="mb-1 fw-bold">API Warning</p>
                                                <p class="text-muted small mb-0">Rate limit warning</p>
                                                <span class="text-muted smaller">Yesterday</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="dropdown-footer bg-light p-2 text-center">
                                    <a href="?page=wpwps-logs" class="small">View All Notifications</a>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex align-items-center" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="https://www.gravatar.com/avatar/{{ md5(wp_get_current_user()->user_email) }}?s=32&d=mp" class="user-avatar me-2" alt="User">
                                <span class="d-none d-lg-block">{{ wp_get_current_user()->display_name }}</span>
                                <i class="fa fa-chevron-down ms-1"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="{{ admin_url('profile.php') }}"><i class="fa fa-user me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="?page=wpwps-settings"><i class="fa fa-cog me-2"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ wp_logout_url() }}"><i class="fa fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-0">
            <div class="row g-0">
                <!-- Content -->
                <div class="col-md-12 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="mb-0">{{ get_admin_page_title() }}</h1>
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
                    
                    <div class="content-wrapper">
                        @yield('content')
                    </div>
                    
                    <footer class="mt-4 text-muted">
                        <small>WP WooCommerce Printify Sync v{{ WPWPS_VERSION }}</small>
                    </footer>
                </div>
            </div>
        </div>

        <!-- Toast Notifications Container -->
        <div class="wpwps-toast-container"></div>
    </div>
</body>
</html>