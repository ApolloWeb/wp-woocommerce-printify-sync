<?php defined('ABSPATH') || exit; ?>

<div class="wrap wpwps-admin-wrap">
    <h1><?php esc_html_e('Email Template Editor', 'wp-woocommerce-printify-sync'); ?></h1>

    <div class="wpwps-template-editor">
        <div class="editor-grid">
            <div class="template-variables">
                <h3><?php esc_html_e('Available Variables', 'wp-woocommerce-printify-sync'); ?></h3>
                <ul>
                    <?php foreach ($variables as $var => $desc): ?>
                        <li>
                            <code><?php echo esc_html($var); ?></code>
                            <span class="description"><?php echo esc_html($desc); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="template-content">
                <textarea id="template-editor" name="template_content"><?php echo esc_textarea($template_content); ?></textarea>
            </div>

            <div class="template-preview">
                <h3><?php esc_html_e('Preview', 'wp-woocommerce-printify-sync'); ?></h3>
                <div id="preview-area"></div>
            </div>
        </div>
    </div>
</div>
