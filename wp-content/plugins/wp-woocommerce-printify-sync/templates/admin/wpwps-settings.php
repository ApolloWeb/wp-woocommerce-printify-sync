<?php 
defined('ABSPATH') || exit;
$this->extend('wpwps-app');
?>

<?php $this->section('content'); ?>
<div class="wpwps-container">
    <!-- Page Header -->
    <div class="wpwps-page-header">
        <h1 class="wpwps-page-title">
            <i class="fas fa-cogs"></i>
            <?php esc_html_e('Printify Settings', 'wp-woocommerce-printify-sync'); ?>
        </h1>
        
        <div class="wpwps-user-profile">
            <img src="<?php echo esc_url($user_avatar); ?>" 
                 alt="<?php echo esc_attr($user_name); ?>" 
                 class="wpwps-user-avatar">
            <div class="wpwps-user-info">
                <span class="wpwps-user-name"><?php echo esc_html($user_name); ?></span>
            </div>
        </div>
    </div>

    <!-- Settings Content -->
    <div class="row">
        <!-- Printify API Settings -->
        <div class="col-md-6 mb-4">
            <?php $this->component('wpwps-settings-card', [
                'icon' => 'tshirt',
                'title' => __('Printify API Configuration', 'wp-woocommerce-printify-sync'),
                'content' => dirname(__DIR__) . '/partials/wpwps-printify-settings.php'
            ]); ?>
        </div>
        
        <!-- OpenAI API Settings -->
        <div class="col-md-6 mb-4">
            <?php $this->component('wpwps-settings-card', [
                'icon' => 'robot',
                'title' => __('OpenAI Configuration', 'wp-woocommerce-printify-sync'),
                'content' => dirname(__DIR__) . '/partials/wpwps-openai-settings.php'
            ]); ?>
        </div>
    </div>
    
    <!-- Save Button -->
    <div class="d-flex justify-content-end mb-4">
        <button type="button" id="saveSettings" class="btn btn-primary">
            <i class="fas fa-save me-2"></i> <?php esc_html_e('Save All Settings', 'wp-woocommerce-printify-sync'); ?>
        </button>
    </div>
</div>
<?php $this->endSection('content'); ?>
