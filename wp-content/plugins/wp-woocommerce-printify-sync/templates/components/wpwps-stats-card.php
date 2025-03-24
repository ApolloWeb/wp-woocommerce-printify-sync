<div class="stats-card">
    <div class="stats-icon">
        <i class="fas fa-<?php echo esc_attr($icon); ?>"></i>
    </div>
    <div class="stats-content">
        <h3 class="stats-value"><?php echo esc_html($value); ?></h3>
        <p class="stats-label"><?php echo esc_html($label); ?></p>
    </div>
    <?php if (isset($trend)): ?>
        <div class="stats-trend <?php echo $trend['direction']; ?>">
            <i class="fas fa-arrow-<?php echo $trend['direction']; ?>"></i>
            <?php echo esc_html($trend['value']); ?>%
        </div>
    <?php endif; ?>
</div>
