<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="{{ WPWPS_URL }}assets/images/logo.svg" alt="Printify" height="30">
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link {{ $active_menu === 'dashboard' ? 'active' : '' }}" href="admin.php?page=wpwps-dashboard">
                        <i class="fas fa-chart-line"></i> {{ __('Dashboard', 'wp-woocommerce-printify-sync') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $active_menu === 'settings' ? 'active' : '' }}" href="admin.php?page=wpwps-settings">
                        <i class="fas fa-cog"></i> {{ __('Settings', 'wp-woocommerce-printify-sync') }}
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="https://help.printify.com" target="_blank">
                        <i class="fas fa-question-circle"></i> {{ __('Help', 'wp-woocommerce-printify-sync') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>