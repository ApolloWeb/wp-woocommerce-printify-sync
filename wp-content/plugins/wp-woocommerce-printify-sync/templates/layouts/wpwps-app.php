<?php 
defined('ABSPATH') || exit; 

// Default variables with fallbacks to prevent PHP notices
$has_low_credit = $has_low_credit ?? false;
$page_title = $page_title ?? get_admin_page_title();
$user_avatar = $user_avatar ?? '';
$user_name = $user_name ?? '';
$user_edit_url = $user_edit_url ?? '';

// Debug information for troubleshooting
$debug_mode = defined('WP_DEBUG') && WP_DEBUG;
$current_screen = function_exists('get_current_screen') ? get_current_screen() : null;
?>
<!--- Begin wpwps-app layout template -->
<div class="wrap wpwps-admin">
    <?php if ($debug_mode): ?>
    <div class="wpwps-layout-debug" style="background:#f8f9fa; padding:10px; margin-bottom:20px; border-left:4px solid #007cba;">
        <h4>Layout Debug Info</h4>
        <p><strong>Layout file:</strong> <?php echo esc_html(__FILE__); ?></p>
        <p><strong>Page title:</strong> <?php echo esc_html($page_title); ?></p>
        <p><strong>Has content:</strong> <?php echo isset($content) ? 'Yes ('.strlen($content).' chars)' : 'No'; ?></p>
        <p><strong>Sections:</strong> <?php echo esc_html(implode(', ', array_keys($sections ?? []))); ?></p>
        <?php if (isset($debug_data) && is_array($debug_data)): ?>
        <p><strong>Source:</strong> <?php echo esc_html($debug_data['template_source'] ?? 'unknown'); ?></p>
        <p><strong>Time:</strong> <?php echo esc_html($debug_data['template_time'] ?? 'unknown'); ?></p>
        <p><strong>URI:</strong> <?php echo esc_html($debug_data['request_uri'] ?? 'unknown'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($has_low_credit): ?>
    <div class="wpwps-notification-bar" style="background: rgba(220, 53, 69, 0.1); color: #842029; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
        <div class="wpwps-notification-content">
            <i class="fas fa-exclamation-triangle"></i>
            <?php esc_html_e('Your API credit balance is low. Please add funds to avoid service interruption.', 'wp-woocommerce-printify-sync'); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="wpwps-content">
    <?php 
    // Direct output of content 
    if (isset($content) && !empty($content)): 
        echo $content;
    elseif (isset($sections['content']) && !empty($sections['content'])): 
        echo $sections['content'];
    elseif (method_exists($this, 'yield')): 
        $this->yield('content');
    else:
        // Emergency content display
        echo '<div class="wpwps-error" style="padding:20px; background-color:#fff; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.1);">';
        echo '<h2 style="color:#dc3545;">' . esc_html__('Content Loading Error', 'wp-woocommerce-printify-sync') . '</h2>';
        echo '<p>' . esc_html__('The main content could not be loaded properly.', 'wp-woocommerce-printify-sync') . '</p>';
        
        if ($debug_mode):
            echo '<div style="background:#f9f9f9; padding:10px; border-left:4px solid #007cba; margin-top:15px;">';
            echo '<h3>' . esc_html__('Debug Information', 'wp-woocommerce-printify-sync') . '</h3>';
            echo '<p><strong>Template:</strong> ' . esc_html(basename(__FILE__)) . '</p>';
            echo '<p><strong>Content length:</strong> ' . (isset($content) ? strlen($content) : 'null') . '</p>';
            echo '<p><strong>Sections:</strong> ' . esc_html(implode(', ', array_keys($sections ?? []))) . '</p>';
            echo '</div>';
        endif;
        
        echo '<button class="button button-primary" id="reload-page" style="margin-top:15px;">' . esc_html__('Reload Page', 'wp-woocommerce-printify-sync') . '</button>';
        echo '</div>';
    endif;
    ?>
    </div>
</div>
<!--- End wpwps-app layout template -->
<script>
    // Small inline script to make the reload button work
    document.getElementById('reload-page')?.addEventListener('click', function() {
        window.location.reload();
    });
</script>
