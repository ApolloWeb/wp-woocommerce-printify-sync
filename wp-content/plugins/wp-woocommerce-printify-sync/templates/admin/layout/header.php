<header class="wpwps-header">
    <div class="wpwps-header-start">
        <button type="button" class="wpwps-menu-toggle" id="wpwps-toggle-sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="wpwps-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search..." class="form-control">
        </div>
    </div>
    
    <div class="wpwps-header-end">
        <div class="wpwps-notifications dropdown">
            <button class="btn btn-link position-relative" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <?php $notification_count = get_option('wpwps_notification_count', 0); ?>
                <?php if ($notification_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $notification_count; ?>
                </span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end wpwps-notifications-menu">
                <div class="wpwps-notifications-header">
                    <h6 class="mb-0">Notifications</h6>
                    <?php if ($notification_count > 0): ?>
                    <button type="button" class="btn btn-link btn-sm text-muted" id="wpwps-mark-all-read">
                        Mark all as read
                    </button>
                    <?php endif; ?>
                </div>
                <div class="wpwps-notifications-body">
                    <?php
                    $notifications = get_option('wpwps_notifications', []);
                    if (empty($notifications)):
                    ?>
                    <div class="wpwps-empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>All caught up!</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                        <a href="<?php echo esc_url($notification['link']); ?>" class="wpwps-notification-item">
                            <div class="wpwps-notification-icon">
                                <i class="<?php echo esc_attr($notification['icon']); ?>"></i>
                            </div>
                            <div class="wpwps-notification-content">
                                <p><?php echo esc_html($notification['message']); ?></p>
                                <small><?php echo esc_html(human_time_diff(strtotime($notification['time']))); ?> ago</small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="wpwps-user-menu dropdown">
            <button class="btn btn-link" type="button" data-bs-toggle="dropdown">
                <img src="<?php echo get_avatar_url(get_current_user_id(), ['size' => 32]); ?>" 
                     alt="User avatar" 
                     class="wpwps-user-avatar">
                <span class="wpwps-user-name"><?php echo wp_get_current_user()->display_name; ?></span>
                <i class="fas fa-chevron-down ms-2"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="<?php echo admin_url('admin.php?page=wpwps-settings'); ?>" class="dropdown-item">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <a href="<?php echo admin_url('admin.php?page=wpwps-logs'); ?>" class="dropdown-item">
                    <i class="fas fa-list me-2"></i> Logs
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo wp_logout_url(admin_url()); ?>" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>
