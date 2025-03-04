<?php
/**
 * Header Navigation Template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get current page slug
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'wp-woocommerce-printify-sync';

// Check environment setting
$environment = get_option('printify_sync_environment', 'production');
$is_dev = $environment === 'development';

// Define menu items
$menu_items = [
    'wp-woocommerce-printify-sync' => [
        'title' => 'Dashboard',
        'icon' => 'fas fa-tachometer-alt'
    ],
    'printify-shops' => [
        'title' => 'Shops',
        'icon' => 'fas fa-store'
    ],
    'printify-products' => [
        'title' => 'Products',
        'icon' => 'fas fa-shirt'
    ],
    'printify-orders' => [
        'title' => 'Orders',
        'icon' => 'fas fa-shopping-cart'
    ],
    'printify-exchange-rates' => [
        'title' => 'Exchange Rates',
        'icon' => 'fas fa-exchange-alt'
    ],
    'printify-logs' => [
        'title' => 'Logs',
        'icon' => 'fas fa-clipboard-list'
    ],
    'printify-tickets' => [
        'title' => 'Tickets',
        'icon' => 'fas fa-ticket-alt'
    ],
    'printify-settings' => [
        'title' => 'Settings',
        'icon' => 'fas fa-cog'
    ]
];

// Add Postman in debug/development mode
if ($is_dev) {
    $menu_items['printify-postman'] = [
        'title' => 'API Postman',
        'icon' => 'fas fa-paper-plane'
    ];
}

// Get current user initial
$current_user = function_exists('printify_sync_get_current_user') ? 
    printify_sync_get_current_user() : 'ApolloWeb';
$user_initial = strtoupper(substr($current_user, 0, 1));

// Format the current datetime
$current_datetime = function_exists('printify_sync_get_current_datetime') ?
    printify_sync_get_current_datetime() : '2025-03-04 10:39:02';
$formatted_date = date('M j, Y', strtotime($current_datetime));
$formatted_time = date('g:i A', strtotime($current_datetime));
?>

<div class="printify-header-wrapper">
    <div class="printify-header">
        <div class="printify-logo">
            <h2><i class="fas fa-tshirt"></i> Printify Sync</h2>
        </div>
        
        <button type="button" id="burgerMenuBtn" class="burger-menu">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="printify-header-actions">
            <div class="printify-user-info">
                <div class="printify-user-avatar">
                    <?php echo esc_html($user_initial); ?>
                </div>
                <div class="printify-user-details">
                    <div class="printify-user-name">
                        <?php echo esc_html($current_user); ?>
                    </div>
                    <div class="printify-date-time">
                        <?php echo esc_html($formatted_date . ' · ' . $formatted_time); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="environment-indicator <?php echo $is_dev ? 'alert-warning' : 'alert-success'; ?>">
        <i class="fas <?php echo $is_dev ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
        <strong><?php echo $is_dev ? 'Development' : 'Production'; ?> Environment</strong>
        <span class="environment-details">
            <?php if ($is_dev): ?>
                Debug features are enabled. Do not use in production.
            <?php else: ?>
                Ready for live use.
            <?php endif; ?>
        </span>
    </div>
    
    <nav class="printify-nav" id="printifyNav">
        <ul class="printify-nav-menu">
            <?php foreach ($menu_items as $slug => $item) : ?>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=' . $slug); ?>" 
                       class="<?php echo $current_page === $slug ? 'active' : ''; ?>">
                        <i class="<?php echo esc_attr($item['icon']); ?>"></i>
                        <span><?php echo esc_html($item['title']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</div>

<style>
.printify-header-wrapper {
    margin-bottom: 20px;
}

.printify-header {
    display: flex;
    background: #7f54b3;
    padding: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 4px 4px 0 0;
    align-items: center;
    height: 60px;
    position: relative;
}

.printify-logo {
    display: flex;
    align-items: center;
    padding: 0 20px;
    border-right: 1px solid rgba(255, 255, 255, 0.2);
    height: 100%;
    flex-shrink: 0;
}

.printify-logo h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #fff;
    display: flex;
    align-items: center;
    white-space: nowrap;
}

.printify-logo h2 i {
    margin-right: 8px;
}

.burger-menu {
    display: none;
    background: none;
    border: none;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    margin-left: auto;
    padding: 0 15px;
}

.environment-badge {
    margin-left: 10px;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
}

.environment-badge.development {
    background-color: #f8d7da;
    color: #721c24;
}

.environment-indicator {
    background: #d4edda;
    color: #155724;
    padding: 8px 15px;
    width: 100%;
    display: flex;
    align-items: center;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.environment-indicator.alert-warning {
    background-color: #fff3cd;
    color: #856404;
}

.environment-indicator.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.environment-indicator i {
    margin-right: 8px;
}

.environment-indicator strong {
    margin-right: 12px;
}

.environment-details {
    font-size: 14px;
}

.printify-nav {
    background: #7f54b3;
    color: #fff;
}

.printify-nav-menu {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    height: 100%;
    flex-wrap: wrap;
}

.printify-nav-menu li {
    margin: 0;
}

.printify-nav-menu li a {
    display: flex;
    align-items: center;
    padding: 0 16px;
    height: 60px;
    text-decoration: none;
    color: #fff;
    font-weight: 500;
    transition: all 0.2s ease;
}

.printify-nav-menu li a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.printify-nav-menu li a.active {
    background-color: rgba(255, 255, 255, 0.2);
    color: #fff;
    box-shadow: inset 0 -3px 0 #fff;
}

.printify-nav-menu li a i {
    margin-right: 8px;
    font-size: 14px;
}

.printify-header-actions {
    padding: 0 20px;
    border-left: 1px solid rgba(255, 255, 255, 0.2);
    height: 100%;
    display: flex;
    align-items: center;
    margin-left: auto;
}

.printify-user-info {
    display: flex;
    align-items: center;
}

.printify-user-avatar {
    width: 36px;
    height: 36px;
    background: #fff;
    color: #7f54b3;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 10px;
}

.printify-user-name {
    font-weight: 600;
    color: #fff;
    font-size: 14px;
}

.printify-date-time {
    color: #ddd;
    font-size: 12px;
}

@media (max-width: 782px) {
    .burger-menu {
        display: block;
    }
    
    .printify-header-actions {
        display: none;
    }
    
    .printify-nav {
        display: none;
    }
    
    .printify-nav.show {
        display: block;
    }
    
    .printify-nav-menu {
        flex-direction: column;
    }
    
    .printify-nav-menu li {
        width: 100%;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const burgerBtn = document.getElementById('burgerMenuBtn');
    const nav = document.getElementById('printifyNav');
    
    if (burgerBtn && nav) {
        burgerBtn.addEventListener('click', function() {
            nav.classList.toggle('show');
        });
    }
});
</script>