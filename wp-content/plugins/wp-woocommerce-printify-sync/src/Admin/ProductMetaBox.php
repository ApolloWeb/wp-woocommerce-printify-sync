<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ProductMetaBox
{
    private string $currentTime;
    private string $currentUser;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:40:05';
        $this->currentUser = 'ApolloWeb';

        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post', [$this, 'saveMetaBox']);
    }

    public function addMetaBox(): void
    {
        add_meta_box(
            'wpwps_printify_data',
            'Printify Data',
            [$this, 'renderMetaBox'],
            'product',
            'side',
            'default'
        );
    }

    public function renderMetaBox(\WP_Post $post): void
    {
        $printifyId = get_post_meta($post->ID, '_printify_id', true);
        wp_nonce_field('wpwps_save_printify_data', 'wpwps_printify_nonce');
        ?>
        <p>
            <label for="printify_id">Printify ID:</label>
            <input type="text" 
                   id="printify_id" 
                   name="printify_id" 
                   value="<?php echo esc_attr($printifyId); ?>" 
                   readonly>
        </p>
        <p>
            <strong>Last Updated:</strong> <?php echo esc_html($this->currentTime); ?><br>
            <strong>By:</strong> <?php echo esc_html($this->currentUser); ?>
        </p>
        <p>
            <button type="button" 
                    class="button wpwps-sync-product" 
                    data-product-id="<?php echo esc_attr($post->ID); ?>">
                Sync with Printify
            </button>
        </p>
        <?php
    }

    public function saveMetaBox(int $postId): void
    {
        if (!isset($_POST['wpwps_printify_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['wpwps_printify_nonce'], 'wpwps_save_printify_data')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        // Save Printify data
        if (isset($_POST['printify_id'])) {
            update_post_meta($postId, '_printify_id', sanitize_text_field($_POST['printify_id']));
        }
    }
}