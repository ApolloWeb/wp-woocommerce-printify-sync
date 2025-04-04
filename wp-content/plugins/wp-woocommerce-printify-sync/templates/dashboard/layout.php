<div class="wpwps-dashboard-wrapper">
    <nav class="wpwps-navbar navbar navbar-expand-lg navbar-glass fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="<?php echo esc_url(WPWPS_PLUGIN_URL . 'assets/images/logo.svg'); ?>" alt="Printify Sync">
            </a>
            
            <!-- Main Navigation -->
            <div class="navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php foreach ($ui['navigation']['main'] as $item): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo esc_url($item['url']); ?>">
                                <i class="fas <?php echo esc_attr($item['icon']); ?> me-1"></i>
                                <?php echo esc_html($item['title']); ?>
                                <?php if (!empty($item['badge'])): ?>
                                    <span class="badge bg-primary rounded-pill ms-1"><?php echo esc_html($item['badge']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- User Menu -->
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <img src="<?php echo get_avatar_url($ui['user']->ID); ?>" class="avatar rounded-circle">
                        <span class="ms-2"><?php echo esc_html($ui['user']->display_name); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($ui['navigation']['user'] as $item): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo esc_url($item['url']); ?>">
                                    <i class="fas <?php echo esc_attr($item['icon']); ?> me-2"></i>
                                    <?php echo esc_html($item['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="wpwps-content">
        <div class="container-fluid py-4">
            <?php echo $data['content'] ?? ''; ?>
        </div>
    </main>

    <div class="toast-container position-fixed top-0 end-0 p-3"></div>
</div>
