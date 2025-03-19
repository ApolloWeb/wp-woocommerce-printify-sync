<div class="wpwps-header mb-4">
    <h1 class="wp-heading-inline"><?php echo esc_html($pageTitle); ?></h1>
    <?php if (isset($actionButton)): ?>
        <a href="<?php echo esc_url($actionButton['url']); ?>" class="page-title-action">
            <?php echo esc_html($actionButton['text']); ?>
        </a>
    <?php endif; ?>
</div>
