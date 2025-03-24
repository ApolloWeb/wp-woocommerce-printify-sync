<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class SettingsPage extends \WC_Settings_Page {
    public function __construct() {
        $this->id = 'wpwps_support';
        $this->label = __('Support System', 'wp-woocommerce-printify-sync');
        
        parent::__construct();
    }
    
    public function get_settings(): array {
        $settings = [
            [
                'title' => __('Support System Settings', 'wp-woocommerce-printify-sync'),
                'type' => 'title',
                'id' => 'wpwps_support_settings'
            ],
            
            // Email Server Settings
            [
                'title' => __('POP3 Settings', 'wp-woocommerce-printify-sync'),
                'type' => 'section',
            ],
            [
                'title' => __('Server Host', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'id' => 'wpwps_pop3_host',
                'default' => '',
            ],
            [
                'title' => __('Server Port', 'wp-woocommerce-printify-sync'),
                'type' => 'number',
                'id' => 'wpwps_pop3_port',
                'default' => '995',
            ],
            [
                'title' => __('Username', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'id' => 'wpwps_pop3_username',
            ],
            [
                'title' => __('Password', 'wp-woocommerce-printify-sync'),
                'type' => 'password',
                'id' => 'wpwps_pop3_password',
            ],
            
            // OpenAI Settings
            [
                'title' => __('OpenAI Settings', 'wp-woocommerce-printify-sync'),
                'type' => 'section',
            ],
            [
                'title' => __('API Key', 'wp-woocommerce-printify-sync'),
                'type' => 'password',
                'id' => 'wpwps_openai_key',
            ],
            [
                'title' => __('Model', 'wp-woocommerce-printify-sync'),
                'type' => 'select',
                'id' => 'wpwps_openai_model',
                'options' => [
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-4' => 'GPT-4',
                ],
                'default' => 'gpt-3.5-turbo'
            ],
            
            // Email Template Settings
            [
                'title' => __('Email Settings', 'wp-woocommerce-printify-sync'),
                'type' => 'section',
            ],
            [
                'title' => __('From Name', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'id' => 'wpwps_email_from_name',
                'default' => get_bloginfo('name') . ' ' . __('Support', 'wp-woocommerce-printify-sync'),
            ],
            [
                'title' => __('From Email', 'wp-woocommerce-printify-sync'),
                'type' => 'email',
                'id' => 'wpwps_email_from_address',
                'default' => get_option('admin_email'),
            ],
            [
                'title' => __('Email Signature', 'wp-woocommerce-printify-sync'),
                'type' => 'wysiwyg',
                'id' => 'wpwps_email_signature',
                'default' => $this->getDefaultSignature(),
            ],
            
            // Social Media Links
            [
                'title' => __('Social Media', 'wp-woocommerce-printify-sync'),
                'type' => 'section',
            ],
            [
                'title' => __('Facebook', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'id' => 'wpwps_social_facebook',
            ],
            [
                'title' => __('Twitter', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'id' => 'wpwps_social_twitter',
            ],
            [
                'title' => __('Instagram', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'id' => 'wpwps_social_instagram',
            ],
            
            // Queue Settings
            [
                'title' => __('Queue Settings', 'wp-woocommerce-printify-sync'),
                'type' => 'section',
            ],
            [
                'title' => __('Batch Size', 'wp-woocommerce-printify-sync'),
                'type' => 'number',
                'id' => 'wpwps_queue_batch_size',
                'default' => '50',
                'desc' => __('Number of emails to process per batch', 'wp-woocommerce-printify-sync'),
            ],
            [
                'title' => __('Max Retries', 'wp-woocommerce-printify-sync'),
                'type' => 'number',
                'id' => 'wpwps_queue_max_retries',
                'default' => '3',
            ],
            
            ['type' => 'sectionend', 'id' => 'wpwps_support_settings'],
        ];
        
        return apply_filters('wpwps_support_settings', $settings);
    }
    
    private function getDefaultSignature(): string {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        $logo_url = get_option('woocommerce_email_header_image');
        
        ob_start();
        ?>
        <p>Best regards,<br>
        <?php echo esc_html($site_name); ?> Support Team</p>
        
        <?php if ($logo_url): ?>
        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" style="max-width:200px;">
        <?php endif; ?>
        
        <p><a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_url); ?></a></p>
        <?php
        return ob_get_clean();
    }
}
