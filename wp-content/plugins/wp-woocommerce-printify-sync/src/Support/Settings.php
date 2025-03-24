<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class Settings {
    public function register(): void {
        // Add settings page under WooCommerce
        add_filter('woocommerce_get_settings_pages', [$this, 'addSettingsPage']);
    }

    public function addSettingsPage($settings): array {
        $settings[] = new SettingsPage();
        return $settings;
    }

    public function get($key, $default = '') {
        return get_option('wpwps_' . $key, $default);
    }

    public function getEmailSignature(): string {
        $signature = $this->get('email_signature');
        
        // Add social media links if configured
        $social_links = [];
        foreach (['facebook', 'twitter', 'instagram'] as $platform) {
            $url = $this->get('social_' . $platform);
            if ($url) {
                $social_links[] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url($url),
                    ucfirst($platform)
                );
            }
        }
        
        if ($social_links) {
            $signature .= '<p class="social-links">' . implode(' | ', $social_links) . '</p>';
        }
        
        return $signature;
    }

    public function registerSettings(): void {
        register_setting('wpwps_settings', 'wpwps_email_settings', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizeSettings']
        ]);
    }

    public function getEmailTemplate($type): string {
        $templates = WC()->mailer()->get_emails();
        $template = $templates[$type] ?? null;
        
        if (!$template) {
            return '';
        }

        ob_start();
        wc_get_template(
            $template->template_html,
            [
                'email_heading' => '{heading}',
                'email' => $template,
                'content' => '{content}'
            ],
            '',
            WC()->mailer()->template_base
        );
        
        return ob_get_clean();
    }
}
