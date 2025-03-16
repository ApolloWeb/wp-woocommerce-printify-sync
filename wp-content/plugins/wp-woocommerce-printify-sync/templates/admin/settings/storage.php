<div class="wrap printify-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php" id="printify-settings-form">
        <?php settings_fields('printify_storage_settings'); ?>

        <div class="settings-container">
            <?php foreach ($sections as $section): ?>
                <div class="card settings-section mb-4" id="<?php echo esc_attr($section['id']); ?>">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <button class="btn btn-link w-100 text-start d-flex justify-content-between align-items-center" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#collapse-<?php echo esc_attr($section['id']); ?>">
                                <span>
                                    <i class="<?php echo esc_attr($section['icon']); ?> me-2"></i>
                                    <?php echo esc_html($section['title']); ?>
                                </span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </h5>
                    </div>

                    <div id="collapse-<?php echo esc_attr($section['id']); ?>" 
                         class="collapse <?php echo $section['expanded'] ? 'show' : ''; ?>">
                        <div class="card-body">
                            <div class="settings-fields">
                                <?php foreach ($section['fields'] as $field): ?>
                                    <div class="mb-3">
                                        <label for="<?php echo esc_attr($field['id']); ?>" class="form-label">
                                            <?php echo esc_html($field['title']); ?>
                                        </label>

                                        <?php if ($field['type'] === 'password'): ?>
                                            <div class="input-group">
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="<?php echo esc_attr($field['id']); ?>"
                                                       name="<?php echo esc_attr($field['id']); ?>"
                                                       value="<?php echo esc_attr(get_option($field['id'])); ?>"
                                                >
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="<?php echo esc_attr($field['id']); ?>"
                                                   name="<?php echo esc_attr($field['id']); ?>"
                                                   value="<?php echo esc_attr(get_option($field['id'])); ?>"
                                            >
                                        <?php endif; ?>

                                        <?php if (!empty($field['desc'])): ?>
                                            <div class="form-text"><?php echo esc_html($field['desc']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($section['test_button']): ?>
                                    <button type="button" 
                                            class="btn btn-outline-primary test-connection"
                                            data-action="<?php echo esc_attr($section['test_action']); ?>">
                                        <i class="fas fa-vial me-2"></i>
                                        <?php _e('Test Connection', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="settings-footer">
            <button type="submit" class="button button-primary">
                <i class="fas fa-save me-2"></i>
                <?php _e('Save Settings', 'wp-woocommerce-printify-sync'); ?>
            </button>
        </div>
    </form>
</div>