<?php
/**
 * Main navigation component for the admin dashboard
 */
defined('ABSPATH') || exit;
?>

<nav class="main-nav">
    <ul>
        <li class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'printify-sync-dashboard') ? 'active' : ''; ?>">
            <a href="admin.php?page=printify-sync-dashboard"><i class="fas fa-home"></i> Dashboard</a>
        </li>
        <li class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'printify-sync-products') ? 'active' : ''; ?>">
            <a href="admin.php?page=printify-sync-products"><i class="fas fa-shopping-cart"></i> Products</a>
        </li>
        <li class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'printify-sync-exchange-rates') ? 'active' : ''; ?>">
            <a href="admin.php?page=printify-sync-exchange-rates"><i class="fas fa-exchange-alt"></i> Exchange Rates</a>
        </li>
        <li class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'printify-sync-orders') ? 'active' : ''; ?>">
            <a href="admin.php?page=printify-sync-orders"><i class="fas fa-truck"></i> Orders</a>
        </li>
        <li class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'printify-sync-shops') ? 'active' : ''; ?>">
            <a href="admin.php?page=printify-sync-shops"><i class="fas fa-store"></i> Shops</a>
        </li>
        <li class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'printify-sync-logs') ? 'active' : ''; ?>">
            <a href="admin.php?page=printify-sync-logs"><i class="fas fa-list-alt"></i> Log Viewer</a>
        </li>
        <li class="<?php echo (isset($_GET['page']) && $_GET['page'] === 'printify-sync-settings') ? 'active' : ''; ?>">
            <a href="admin.php?page=printify-sync-settings"><i class="fas fa-cog"></i> Settings</a>
        </li>
    </ul>
</nav>