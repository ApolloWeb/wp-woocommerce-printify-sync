<?php
/**
 * Navigation component with notifications dropdown
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 * @version 1.2.5
 * @date 2025-03-03 13:58:43
 */
defined('ABSPATH') || exit;

// Determine current page
$current_page = $_GET['page'] ?? 'printify-sync-dashboard';

// Demo notification data
$notifications = [
    [
        'id' => 1,
        'message' => 'New order #ORD-7836 received',
        'type' => 'order',
        'time' => '10 minutes ago',
        'read' => false,
        'link' => '?page=printify-sync-orders'
    ],
    [
        'id' => 2,
        'message' => 'Product sync completed: 35 products updated',
        'type' => 'sync',
        'time' => '2 hours ago',
        'read' => false,
        'link' => '?page=printify-sync-products'
    ],
    [
        'id' => 3,
        'message' => 'Printify API rate limit warning',
        'type' => 'warning',
        'time' => 'Yesterday',
        'read' => true,
        'link' => '?page=printify-sync-settings'
    ]
];

// Count unread notifications
$unread_count = array_reduce($notifications, function($carry, $item) {
    return $carry + (!$item['read'] ? 1 : 0);
}, 0);
?>

<nav class="main-nav">
    <ul>
        <li class="<?php echo $current_page === 'printify-sync-dashboard' ? 'active' : ''; ?>">
            <a href="?page=printify-sync-dashboard">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="<?php echo $current_page === 'printify-sync-products' ? 'active' : ''; ?>">
            <a href="?page=printify-sync-products">
                <i class="fas fa-tshirt"></i> Products
            </a>
        </li>
        <li class="<?php echo $current_page === 'printify-sync-orders' ? 'active' : ''; ?>">
            <a href="?page=printify-sync-orders">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
        </li>
        <li class="<?php echo $current_page === 'printify-sync-shops' ? 'active' : ''; ?>">
            <a href="?page=printify-sync-shops">
                <i class="fas fa-store"></i> Shops
            </a>
        </li>
        <li class="<?php echo $current_page === 'printify-sync-settings' ? 'active' : ''; ?>">
            <a href="?page=printify-sync-settings">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
    </ul>
    
    <div class="notifications-container">
        <div class="notifications-icon">
            <i class="fas fa-bell"></i>
            <?php if ($unread_count > 0): ?>
                <span class="notifications-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
        
        <div class="notifications-dropdown">
            <div class="notifications-header">
                <h3>Notifications</h3>
                <a href="#" class="mark-all-read">Mark all as read</a>
            </div>
            
            <div class="notifications-list">
                <?php if (empty($notifications)): ?>
                    <div class="notification-empty">No notifications</div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <a href="<?php echo esc_url($notification['link']); ?>" class="notification-item <?php echo !$notification['read'] ? 'unread' : ''; ?>">
                            <div class="notification-icon 
                                <?php 
                                    switch ($notification['type']) {
                                        case 'order':
                                            echo 'order-icon';
                                            break;
                                        case 'sync':
                                            echo 'sync-icon';
                                            break;
                                        case 'warning':
                                            echo 'warning-icon';
                                            break;
                                        default:
                                            echo 'default-icon';
                                    }
                                ?>
                            ">
                                <i class="fas 
                                    <?php
                                        switch ($notification['type']) {
                                            case 'order':
                                                echo 'fa-shopping-cart';
                                                break;
                                            case 'sync':
                                                echo 'fa-sync-alt';
                                                break;
                                            case 'warning':
                                                echo 'fa-exclamation-triangle';
                                                break;
                                            default:
                                                echo 'fa-bell';
                                        }
                                    ?>
                                "></i>
                            </div>
                            <div class="notification-content">
                                <p class="notification-message"><?php echo esc_html($notification['message']); ?></p>
                                <span class="notification-time"><?php echo esc_html($notification['time']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="notifications-footer">
                <a href="?page=printify-sync-notifications" class="view-all">View all notifications</a>
            </div>
        </div>
    </div>
    
    <div class="user-avatar">
        <div class="avatar-circle">AW</div>
        <div class="user-dropdown">
            <div class="user-dropdown-header">
                <div class="user-info">
                    <h4>ApolloWeb</h4>
                    <p>Administrator</p>
                </div>
            </div>
            <div class="user-dropdown-menu">
                <a href="?page=printify-sync-profile"><i class="fas fa-user-circle"></i> My Profile</a>
                <a href="?page=printify-sync-settings"><i class="fas fa-cog"></i> Settings</a>
                <hr>
                <a href="<?php echo wp_logout_url(); ?>" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>