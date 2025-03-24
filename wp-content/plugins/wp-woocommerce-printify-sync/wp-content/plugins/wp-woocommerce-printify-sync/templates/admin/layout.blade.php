<div class="wrap wpwps-admin-wrapper">
    <!-- Top Navbar -->
    <nav class="wpwps-navbar navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tshirt me-2"></i>
                Printify Sync
            </a>
            
            <!-- Search Form -->
            <form class="navbar-search d-flex position-relative me-auto ms-4">
                <i class="fas fa-search icon"></i>
                <input class="form-control me-2" type="search" placeholder="Search..." aria-label="Search" name="search">
            </form>
            
            <!-- Right-aligned navbar items -->
            <div class="d-flex align-items-center">
                <!-- Notifications Dropdown -->
                <div class="wpwps-notifications dropdown me-3">
                    <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notifications-badge" style="display:none;">
                            0
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header d-flex justify-content-between align-items-center py-2">
                            <span class="fw-bold">Notifications</span>
                            <a href="#" class="text-decoration-none small">Mark all as read</a>
                        </div>
                        <div class="notifications-menu">
                            <!-- Notifications will be populated via JavaScript -->
                        </div>
                        <div class="dropdown-footer text-center p-2 border-top">
                            <a href="#" class="text-decoration-none small">View all notifications</a>
                        </div>
                    </div>
                </div>
                
                <!-- User Dropdown -->
                <div class="wpwps-user-dropdown dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ wpwpsUI.current_user.avatar }}" alt="User" class="avatar">
                        <div class="d-none d-md-block">
                            <div class="fw-bold">{{ wpwpsUI.current_user.name }}</div>
                            <div class="small text-muted">{{ wpwpsUI.current_user.role }}</div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ wp_logout_url(admin_url()) }}"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="wpwps-admin-container">
        <!-- Sidebar -->
        <aside class="wpwps-sidebar">
            <div class="d-flex justify-content-end p-2">
                <button class="sidebar-toggle btn btn-sm">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ $active_page === 'dashboard' ? 'active' : '' }}" href="{{ admin_url('admin.php?page=wpwps-dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $active_page === 'products' ? 'active' : '' }}" href="{{ admin_url('admin.php?page=wpwps-product-sync') }}">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $active_page === 'orders' ? 'active' : '' }}" href="{{ admin_url('admin.php?page=wpwps-order-sync') }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $active_page === 'tickets' ? 'active' : '' }}" href="{{ admin_url('admin.php?page=wpwps-tickets') }}">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Tickets</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $active_page === 'settings' ? 'active' : '' }}" href="{{ admin_url('admin.php?page=wpwps-settings') }}">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="wpwps-main-content">
            @yield('content')
        </main>
    </div>
</div>
