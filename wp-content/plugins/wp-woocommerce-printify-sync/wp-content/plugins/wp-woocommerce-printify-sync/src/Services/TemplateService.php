<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class TemplateService
{
    public function getEmailHeaders(): array
    {
        $from_name = get_option('wpwps_email_from_name', get_bloginfo('name'));
        $from_email = get_option('wpwps_email_from_email', get_bloginfo('admin_email'));
        
        return [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];
    }
    
    public function getEmailSignature(): string
    {
        $store_name = get_option('blogname', '');
        $store_url = get_option('home', '');
        $logo_url = get_option('wpwps_email_logo', '');
        $socials = [
            'facebook' => get_option('wpwps_social_facebook', ''),
            'twitter' => get_option('wpwps_social_twitter', ''),
            'instagram' => get_option('wpwps_social_instagram', ''),
        ];
        
        return $this->renderTemplate('emails.signature', [
            'store_name' => $store_name,
            'store_url' => $store_url,
            'logo_url' => $logo_url,
            'socials' => array_filter($socials),
        ]);
    }

    private function renderTemplate(string $name, array $data = []): string
    {
        ob_start();
        extract($data);
        include WPWPS_PLUGIN_DIR . 'templates/' . str_replace('.', '/', $name) . '.blade.php';
        return ob_get_clean();
    }
}
