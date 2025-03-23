<?php
/**
 * Default email template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div style="margin-bottom: 40px;">
    <?php echo wpautop(wptexturize($content)); ?>
</div>

<?php if (isset($additional_content) && !empty($additional_content)): ?>
    <div style="margin-bottom: 40px;">
        <?php echo wpautop(wptexturize($additional_content)); ?>
    </div>
<?php endif; ?>
