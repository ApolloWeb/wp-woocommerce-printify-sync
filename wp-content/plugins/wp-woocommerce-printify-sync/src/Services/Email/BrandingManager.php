<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Email;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\EmailBrandingInterface;

class BrandingManager implements EmailBrandingInterface {
    private array $config;
    
    public function __construct(array $config = []) 
    {
        $this->config = wp_parse_args($config, [
            'company_name' => get_bloginfo('name'),
            'logo_url' => '',
            'social_media' => [],
            'templates_path' => WPWPS_PLUGIN_PATH . 'templates/emails',
            'images_path' => WPWPS_PLUGIN_PATH . 'assets/images',
            'signature_template' => 'signature.php',
            'auto_text' => [
                'greeting' => __('Hello {customer_name},', 'wp-woocommerce-printify-sync'),
                'footer' => __('Best regards,', 'wp-woocommerce-printify-sync')
            ]
        ]);
    }

    public function getTemplate(string $name): string 
    {
        $file = trailingslashit($this->config['templates_path']) . $name;
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Email template not found: {$name}");
        }
        
        return $file;
    }

    public function getLogo(): ?string 
    {
        return $this->config['logo_url'];
    }

    public function getCompanyName(): string 
    {
        return $this->config['company_name'];
    }

    public function getSocialLinks(): array 
    {
        return $this->config['social_media'];
    }

    public function getSignature(): string 
    {
        ob_start();
        include $this->getTemplate($this->config['signature_template']);
        return ob_get_clean();
    }

    public function getGreeting(string $name = ''): string 
    {
        return str_replace(
            '{customer_name}',
            $name ?: __('there', 'wp-woocommerce-printify-sync'),
            $this->config['auto_text']['greeting']
        );
    }

    public function getFooter(): string 
    {
        return $this->config['auto_text']['footer'];
    }
}
