<nav class="wpps-sidebar-nav">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $this->section === 'dashboard' ? 'active' : '' ?>" 
               href="<?= admin_url('admin.php?page=wpps-dashboard') ?>">
                <i class="fas fa-tachometer-alt me-2"></i>
                <?= __('Dashboard', 'wp-woocommerce-printify-sync') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $this->section === 'products' ? 'active' : '' ?>" 
               href="<?= admin_url('admin.php?page=wpps-products') ?>">
                <i class="fas fa-tshirt me-2"></i>
                <?= __('Products', 'wp-woocommerce-printify-sync') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $this->section === 'orders' ? 'active' : '' ?>" 
               href="<?= admin_url('admin.php?page=wpps-orders') ?>">
                <i class="fas fa-shopping-cart me-2"></i>
                <?= __('Orders', 'wp-woocommerce-printify-sync') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $this->section === 'settings' ? 'active' : '' ?>" 
               href="<?= admin_url('admin.php?page=wpps-settings') ?>">
                <i class="fas fa-cog me-2"></i>
                <?= __('Settings', 'wp-woocommerce-printify-sync') ?>
            </a>
        </li>
    </ul>
</nav>
