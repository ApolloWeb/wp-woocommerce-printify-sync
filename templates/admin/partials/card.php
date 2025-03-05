<?php
/**
 * Card component
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$card_title = isset($card_title) ? $card_title : '';
$card_icon = isset($card_icon) ? $card_icon : '';
$card_class = isset($card_class) ? ' ' . $card_class : '';
$card_actions = isset($card_actions) ? $card_actions : '';
$card_footer = isset($card_footer) ? $card_footer : '';
?>

<div class="card shadow-sm mb-4<?php echo esc_attr($card_class); ?>">
    <?php if (!empty($card_title)) : ?>
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0">
            <?php if (!empty($card_icon)) : ?>
            <i class="fas <?php echo esc_attr($card_icon); ?> me-2"></i>
            <?php endif; ?>
            <?php echo esc_html($card_title); ?>
        </h5>
        <?php if (!empty($card_actions)) : ?>
        <div class="card-actions">
            <?php echo wp_kses_post($card_actions); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="card-body">
        <?php
        // Card content will be inserted here
        if (isset($card_content)) {
            echo wp_kses_post($card_content);
        }
        ?>
    </div>
    <?php if (!empty($card_footer)) : ?>
    <div class="card-footer bg-white">
        <?php echo wp_kses_post($card_footer); ?>
    </div>
    <?php endif; ?>
</div>