<!DOCTYPE html>
<html lang="<?php echo get_locale(); ?>" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?php echo esc_html($title ?? 'Printify Sync'); ?> - WooCommerce</title>
    <?php do_action('admin_head'); ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="wpwps-admin" data-page="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
    <a href="#main-content" class="wpwps-skip-link screen-reader-text">
        <?php esc_html_e('Skip to main content', 'wp-woocommerce-printify-sync'); ?>
    </a>

    <div class="wpwps-wrapper" role="application">
        <?php include WPPS_PATH . 'templates/admin/layout/sidebar.php'; ?>
        
        <div class="wpwps-main">
            <?php include WPPS_PATH . 'templates/admin/layout/header.php'; ?>
            
            <main id="main-content" class="wpwps-content" role="main" tabindex="-1">
                <?php if (!empty($title)): ?>
                <div class="wpwps-page-header" role="banner">
                    <h1><?php echo esc_html($title); ?></h1>
                    <?php if (!empty($actions)): ?>
                    <div class="wpwps-page-actions" role="toolbar" aria-label="<?php esc_attr_e('Page actions', 'wp-woocommerce-printify-sync'); ?>">
                        <?php foreach ($actions as $action): ?>
                            <button type="button" 
                                    class="btn <?php echo esc_attr($action['class'] ?? 'btn-primary'); ?>"
                                    <?php echo !empty($action['id']) ? 'id="' . esc_attr($action['id']) . '"' : ''; ?>
                                    aria-label="<?php echo esc_attr($action['aria_label'] ?? $action['text']); ?>"
                                    <?php echo !empty($action['description']) ? 'aria-description="' . esc_attr($action['description']) . '"' : ''; ?>>
                                <?php if (!empty($action['icon'])): ?>
                                    <i class="<?php echo esc_attr($action['icon']); ?> me-2" aria-hidden="true"></i>
                                <?php endif; ?>
                                <span><?php echo esc_html($action['text']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php echo $content; ?>
            </main>
            
            <?php include WPPS_PATH . 'templates/admin/layout/footer.php'; ?>
        </div>
    </div>
    
    <div class="toast-container position-fixed top-0 end-0 p-3" role="status" aria-live="polite"></div>
    <?php do_action('admin_footer'); ?>
</body>
</html>
