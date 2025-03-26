<div class="wpwps-sidebar" id="wpwpsSidebar">
    <div class="px-3 py-4">
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a href="{{ admin_url('admin.php?page=wpwps-dashboard') }}" 
                   class="nav-link {{ request()->is('*dashboard*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line fa-fw me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="{{ admin_url('admin.php?page=wpwps-products') }}"
                   class="nav-link {{ request()->is('*products*') ? 'active' : '' }}">
                    <i class="fas fa-box fa-fw me-2"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="{{ admin_url('admin.php?page=wpwps-orders') }}"
                   class="nav-link {{ request()->is('*orders*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart fa-fw me-2"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="{{ admin_url('admin.php?page=wpwps-shipping') }}"
                   class="nav-link {{ request()->is('*shipping*') ? 'active' : '' }}">
                    <i class="fas fa-truck fa-fw me-2"></i>
                    <span>Shipping</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="{{ admin_url('admin.php?page=wpwps-tickets') }}"
                   class="nav-link {{ request()->is('*tickets*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt fa-fw me-2"></i>
                    <span>Support</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ admin_url('admin.php?page=wpwps-settings') }}"
                   class="nav-link {{ request()->is('*settings*') ? 'active' : '' }}">
                    <i class="fas fa-cog fa-fw me-2"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="position-absolute bottom-0 start-0 w-100 p-3 border-top">
        <button id="collapseSidebar" class="btn btn-link text-decoration-none w-100">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
</div>

<script>
document.getElementById('collapseSidebar')?.addEventListener('click', function() {
    document.getElementById('wpwpsSidebar').classList.toggle('collapsed');
    document.getElementById('wpwpsContent').classList.toggle('expanded');
});
</script>

<style>
.wpwps-sidebar {
    width: 250px;
    transition: all 0.3s ease;
}

.wpwps-sidebar.collapsed {
    width: 60px;
}

.wpwps-sidebar.collapsed span {
    display: none;
}

.wpwps-sidebar .nav-link {
    color: var(--wpwps-gray);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
}

.wpwps-sidebar .nav-link:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.wpwps-sidebar .nav-link.active {
    background-color: var(--wpwps-primary);
    color: white;
}
</style>