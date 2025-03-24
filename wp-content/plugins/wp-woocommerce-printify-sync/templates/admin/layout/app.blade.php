<div class="wpwps-admin-wrapper" role="application">
    <!-- Navbar -->
    <nav class="wpwps-navbar navbar navbar-expand-lg sticky-top" role="navigation" aria-label="Main navigation">
        <div class="container-fluid">
            <button class="wpwps-sidebar-toggle btn btn-link" aria-label="Toggle sidebar" aria-expanded="true">
                <i class="fas fa-bars" aria-hidden="true"></i>
                <span class="visually-hidden">Toggle Sidebar</span>
            </button>
            
            <div class="d-flex align-items-center flex-grow-1">
                <!-- Search -->
                <div class="wpwps-search position-relative me-auto">
                    <label for="global-search" class="visually-hidden">Search</label>
                    <i class="fas fa-search search-icon" aria-hidden="true"></i>
                    <input type="search" id="global-search" class="form-control" placeholder="Search..." 
                           aria-label="Search" role="searchbox">
                </div>
                
                <!-- Notifications -->
                <div class="dropdown mx-3">
                    <button class="btn btn-link position-relative" data-bs-toggle="dropdown" 
                            aria-label="Notifications" aria-expanded="false">
                        <i class="fas fa-bell" aria-hidden="true"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                              role="status" aria-live="polite">
                            2
                            <span class="visually-hidden">unread notifications</span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" role="menu" aria-label="Notification list">
                        <h6 class="dropdown-header">Notifications</h6>
                        <div class="dropdown-divider"></div>
                        <!-- Notification items go here -->
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn btn-link d-flex align-items-center" data-bs-toggle="dropdown" 
                            aria-label="User menu" aria-expanded="false">
                        <img src="{{ get_avatar_url(get_current_user_id()) }}" 
                             class="rounded-circle me-2" width="32" height="32" 
                             alt="{{ wp_get_current_user()->display_name }}'s avatar">
                        <span class="d-none d-lg-block">
                            {{ wp_get_current_user()->display_name }}
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" role="menu" aria-label="User menu">
                        <h6 class="dropdown-header">{{ wp_get_current_user()->roles[0] }}</h6>
                        <a href="#" class="dropdown-item" role="menuitem">
                            <i class="fas fa-user me-2" aria-hidden="true"></i> Profile
                        </a>
                        <a href="{{ wp_logout_url() }}" class="dropdown-item" role="menuitem">
                            <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="wpwps-sidebar" role="navigation" aria-label="Sidebar navigation">
        <div class="nav flex-column" role="menubar">
            <a href="#" class="nav-link" role="menuitem">
                <i class="fas fa-home" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-link" role="menuitem">
                <i class="fas fa-tshirt" aria-hidden="true"></i>
                <span>Products</span>
            </a>
            <!-- Add more menu items -->
        </div>
    </div>

    <!-- Main Content -->
    <main class="wpwps-main" role="main" aria-label="Main content">
        <div class="container-fluid py-4">
            {!! $content !!}
        </div>
    </main>
</div>
