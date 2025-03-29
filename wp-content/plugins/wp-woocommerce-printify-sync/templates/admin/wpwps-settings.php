<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-settings">
    <h1><?php echo esc_html($title); ?></h1>

    <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo esc_html($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('wpwps_settings');
        do_settings_sections('wpwps-settings');
        submit_button();
        ?>
    </form>
</div>