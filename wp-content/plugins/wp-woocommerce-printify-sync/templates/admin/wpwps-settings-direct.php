<?php
/**
 * Direct settings rendering (without template engine)
 * This template deliberately avoids using the template engine's features
 */
defined('ABSPATH') || exit;

// Variables are provided from the parent scope:
// $current_user, $settings, $credit_balance, $has_low_credit, $page_title
?>
<div class="wrap wpwps-admin">
    <?php if (defined('WP_DEBUG') && WP_DEBUG && !defined('WPWPS_HIDE_DEBUG_INFO')): ?>
    <div class="wpwps-layout-debug" style="background:#f8f9fa; padding:10px; margin-bottom:20px; border-left:4px solid #007cba;">
        <h4>Direct Include Mode</h4>
        <p>The settings page is being rendered using direct PHP includes instead of the template engine.</p>
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
        <div class="wpwps-container">
            <!-- Page Header -->
            <div class="wpwps-page-header">
                <h1 class="wpwps-page-title">
                    <i class="fas fa-cogs"></i>
                    <?php echo esc_html($page_title); ?>
                </h1>
                
                <div class="wpwps-user-profile">
                    <img src="<?php echo esc_url(get_avatar_url($current_user->ID, ['size' => 40])); ?>" 
                         alt="<?php echo esc_attr($current_user->display_name); ?>" 
                         class="wpwps-user-avatar">
                    <div class="wpwps-user-info">
                        <span class="wpwps-user-name">
                            <a href="<?php echo esc_url(get_edit_profile_url($current_user->ID)); ?>" title="<?php esc_attr_e('Edit your profile', 'wp-woocommerce-printify-sync'); ?>">
                                <?php echo esc_html($current_user->display_name); ?>
                            </a>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="row">
                <!-- Printify API Settings -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 settings-card" role="region" aria-label="<?php esc_attr_e('Printify API Configuration', 'wp-woocommerce-printify-sync'); ?>">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-tshirt me-2" aria-hidden="true"></i>
                            <h2 class="h5 mb-0"><?php esc_html_e('Printify API Configuration', 'wp-woocommerce-printify-sync'); ?></h2>
                        </div>
                        <div class="card-body">
                            <?php include dirname(__DIR__) . '/partials/wpwps-printify-settings.php'; ?>
                        </div>
                    </div>
                </div>
                
                <!-- OpenAI API Settings -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 settings-card" role="region" aria-label="<?php esc_attr_e('OpenAI Configuration', 'wp-woocommerce-printify-sync'); ?>">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-robot me-2" aria-hidden="true"></i>
                            <h2 class="h5 mb-0"><?php esc_html_e('OpenAI Configuration', 'wp-woocommerce-printify-sync'); ?></h2>
                        </div>
                        <div class="card-body">
                            <?php include dirname(__DIR__) . '/partials/wpwps-openai-settings.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="d-flex justify-content-end mb-4">
                <button type="button" id="saveSettings" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> <?php esc_html_e('Save All Settings', 'wp-woocommerce-printify-sync'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
