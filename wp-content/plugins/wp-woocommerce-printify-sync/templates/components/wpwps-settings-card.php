<?php defined('ABSPATH') || exit; ?>
<div class="card h-100 settings-card" role="region" aria-label="<?php echo esc_attr($title); ?>">
    <div class="card-header d-flex align-items-center">
        <i class="fas fa-<?php echo esc_attr($icon); ?> me-2" aria-hidden="true"></i>
        <h2 class="h5 mb-0"><?php echo esc_html($title); ?></h2>
        <?php if (isset($badge)): ?>
            <span class="badge bg-<?php echo esc_attr($badge['type']); ?> ms-auto" role="status">
                <?php echo esc_html($badge['text']); ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php 
        // Create a new scope for included template
        (function() use ($settings, $credit_balance, $content) {
            include $content;
        })();
        ?>
    </div>
</div>
