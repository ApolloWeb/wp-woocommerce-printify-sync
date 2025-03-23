<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title ?? 'Printify Sync'); ?> - WooCommerce</title>
    <?php do_action('admin_head'); ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="wpwps-admin" data-page="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
    <div class="wpwps-wrapper">
        <?php include WPPS_PATH . 'templates/admin/layout/sidebar.php'; ?>
        
        <div class="wpwps-main">
            <?php include WPPS_PATH . 'templates/admin/layout/header.php'; ?>
            
            <main class="wpwps-content" data-loading="false">
                <?php if (!empty($title)): ?>
                <div class="wpwps-page-header">
                    <h1><?php echo esc_html($title); ?></h1>
                    <?php if (!empty($actions)): ?>
                    <div class="wpwps-page-actions">
                        <?php foreach ($actions as $action): ?>
                            <button type="button" 
                                    class="btn <?php echo esc_attr($action['class'] ?? 'btn-primary'); ?>"
                                    <?php echo !empty($action['id']) ? 'id="' . esc_attr($action['id']) . '"' : ''; ?>
                                    <?php echo !empty($action['data']) ? implode(' ', array_map(function($key, $value) {
                                        return 'data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
                                    }, array_keys($action['data']), $action['data'])) : ''; ?>>
                                <?php if (!empty($action['icon'])): ?>
                                    <i class="<?php echo esc_attr($action['icon']); ?> me-2"></i>
                                <?php endif; ?>
                                <?php echo esc_html($action['text']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div id="wpwps-loading" class="wpwps-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                
                <?php echo $content; ?>
            </main>
            
            <?php include WPPS_PATH . 'templates/admin/layout/footer.php'; ?>
        </div>
    </div>
    
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>
    <?php do_action('admin_footer'); ?>
</body>
</html>
