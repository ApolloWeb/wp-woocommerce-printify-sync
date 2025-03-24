<div class="wpwps-card">
    <div class="wpwps-card-header">
        <h3><?php echo esc_html($title); ?></h3>
        <?php if (!empty($actions)): ?>
            <div class="wpwps-card-actions">
                <?php foreach ($actions as $action): ?>
                    <button class="<?php echo esc_attr($action['class']); ?>" 
                            id="<?php echo esc_attr($action['id']); ?>">
                        <i class="<?php echo esc_attr($action['icon']); ?>"></i>
                        <?php echo esc_html($action['text']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="wpwps-card-body">
        <?php if (!empty($stats)): ?>
            <div class="wpwps-stats-grid">
                <?php foreach ($stats as $stat): ?>
                    <div class="wpwps-stat-item">
                        <div class="wpwps-stat-value"><?php echo esc_html($stat['value']); ?></div>
                        <div class="wpwps-stat-label"><?php echo esc_html($stat['label']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
