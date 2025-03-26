<nav class="wpwps-navbar navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-sync-alt me-2"></i>
            WC Printify Sync
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#wpwpsNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="wpwpsNavbar">
            <form class="d-flex ms-auto me-3">
                <div class="input-group">
                    <input type="search" class="form-control" placeholder="Search..." aria-label="Search">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <ul class="navbar-nav align-items-center">
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell fa-lg"></i>
                        @if($unreadNotifications ?? false)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $unreadNotifications }}
                            </span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        @forelse($notifications ?? [] as $notification)
                            <li><a class="dropdown-item" href="#">{{ $notification }}</a></li>
                        @empty
                            <li><span class="dropdown-item-text">No new notifications</span></li>
                        @endforelse
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <img src="{{ get_avatar_url(get_current_user_id()) }}" 
                             alt="User Avatar" 
                             class="rounded-circle me-2" 
                             width="32" 
                             height="32">
                        <span class="d-none d-sm-inline">
                            {{ wp_get_current_user()->display_name }}
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ wp_logout_url() }}"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>