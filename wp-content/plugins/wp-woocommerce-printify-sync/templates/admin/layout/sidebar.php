<aside class="wpwps-sidebar" id="wpwps-sidebar">
    <div class="wpwps-sidebar-header">
        <img src="<?php echo WPPS_URL; ?>assets/admin/images/logo.svg" alt="Printify Sync" class="wpwps-logo">
        <span class="wpwps-logo-text">Printify Sync</span>
    </div>
    
    <nav class="wpwps-nav">
        <?php
        $current_page = $_GET['page'] ?? '';
        $menu_items = [
            'wpwps-dashboard' => [
                'icon' => 'fas fa-chart-line',
                'text' => 'Dashboard'
            ],
            'wpwps-products' => [
                'icon' => 'fas fa-tshirt',
                'text' => 'Products'
            ],
            'wpwps-orders' => [
                'icon' => 'fas fa-shopping-cart',
                'text' => 'Orders'
            ],
            'wpwps-tickets' => [
                'icon' => 'fas fa-ticket-alt',
                'text' => 'Support Tickets'
            ],
            'wpwps-sync' => [
                'icon' => 'fas fa-sync',
                'text' => 'Sync Status'
            ],
            'wpwps-settings' => [
                'icon' => 'fas fa-cog',
                'text' => 'Settings'
            ]
        ];
        
        foreach ($menu_items as $slug => $item):
            $is_active = $current_page === $slug;
        ?>
        <a href="<?php echo admin_url('admin.php?page=' . $slug); ?>" 
           class="wpwps-nav-item <?php echo $is_active ? 'active' : ''; ?>">
            <i class="<?php echo esc_attr($item['icon']); ?>"></i>
            <span class="wpwps-nav-text"><?php echo esc_html($item['text']); ?></span>
            <?php if ($slug === 'wpwps-tickets' && ($ticket_count = get_option('wpwps_unread_tickets', 0)) > 0): ?>
            <span class="wpwps-nav-badge"><?php echo $ticket_count; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="wpwps-sidebar-footer">
        <div class="wpwps-sync-status">
            <small>Last Sync: <?php echo human_time_diff(get_option('wpwps_last_sync', time())); ?> ago</small>
            <div class="progress" style="height: 3px;">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
        </div>
    </div>
</aside>
