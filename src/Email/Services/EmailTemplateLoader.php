<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class EmailTemplateLoader {
    private $template_loader;
    private $settings;

    public function __construct(TemplateLoader $template_loader) {
        $this->template_loader = $template_loader;
        $this->settings = get_option('wpwps_email_settings', []);
        
        add_filter('woocommerce_email_styles', [$this, 'addCustomStyles']);
        add_action('woocommerce_email_header', [$this, 'addCustomHeader'], 10, 2);
        add_action('woocommerce_email_footer', [$this, 'addCustomFooter']);
    }

    public function renderEmailTemplate($template, $data = []) {
        $default_data = [
            'header_image' => $this->settings['header_image'] ?? '',
            'footer_text' => $this->settings['footer_text'] ?? '',
            'signature' => $this->settings['signature'] ?? '',
            'company_info' => $this->getCompanyInfo()
        ];

        return $this->template_loader->render(
            'emails/' . $template,
            array_merge($default_data, $data)
        );
    }

    public function addCustomStyles($css) {
        $custom_css = "
            .wpwps-email-header { padding: 20px; }
            .wpwps-signature { margin-top: 20px; }
            .wpwps-footer { text-align: center; padding: 20px; }
        ";
        return $css . $custom_css;
    }

    private function getCompanyInfo() {
        return [
            'name' => get_option('blogname'),
            'email' => get_option('admin_email'),
            'logo' => $this->settings['company_logo'] ?? ''
        ];
    }
}
