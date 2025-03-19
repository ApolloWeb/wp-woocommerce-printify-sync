<?php if (isset($alerts) && !empty($alerts)): ?>
    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?php echo esc_attr($alert['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo esc_html($alert['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
