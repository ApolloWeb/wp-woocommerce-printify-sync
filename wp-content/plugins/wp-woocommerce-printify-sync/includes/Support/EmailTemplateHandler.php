<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class EmailTemplateHandler {
    private $settings;
    private $mailer;

    public function __construct($settings) {
        $this->settings = $settings;
        add_action('woocommerce_email_header', [$this, 'customizeEmailHeader'], 10, 2);
        add_action('woocommerce_email_footer', [$this, 'customizeEmailFooter'], 10, 2);
        add_filter('woocommerce_email_styles', [$this, 'addCustomStyles']);
    }

    public function getTemplate(string $template_name, array $args = []): string {
        // Get WooCommerce mailer if not already set
        if (!$this->mailer) {
            $this->mailer = WC()->mailer();
        }

        // Convert template args to WooCommerce format
        $email_args = $this->prepareEmailArgs($args);

        ob_start();
        
        // Load WooCommerce email header
        do_action('woocommerce_email_header', $args['heading'] ?? '', null);
        
        // Load our template content
        wc_get_template(
            "emails/{$template_name}.php",
            $email_args,
            'wp-woocommerce-printify-sync',
            WPPS_PATH . 'templates/'
        );
        
        // Load WooCommerce email footer
        do_action('woocommerce_email_footer', null);
        
        return ob_get_clean();
    }

    private function prepareEmailArgs(array $args): array {
        return array_merge([
            'email_heading' => '',
            'sent_to_admin' => false,
            'plain_text' => false,
            'email' => null
        ], $args);
    }

    public function customizeEmailHeader($email_heading, $email): void {
        if ($this->settings->get('use_custom_header', 'no') === 'yes') {
            $logo_url = $this->settings->get('email_logo');
            if ($logo_url) {
                echo '<div class="wpwps-email-logo">';
                echo '<img src="' . esc_url($logo_url) . '" alt="' . get_bloginfo('name') . '">';
                echo '</div>';
            }
        }
    }

    public function customizeEmailFooter($email): void {
        if ($this->settings->get('use_custom_footer', 'no') === 'yes') {
            $social_links = $this->settings->get('social_links', []);
            if (!empty($social_links)) {
                echo '<div class="wpwps-social-links">';
                foreach ($social_links as $network => $url) {
                    echo '<a href="' . esc_url($url) . '">';
                    echo '<img src="' . WPPS_URL . 'assets/images/' . $network . '.png" alt="' . $network . '">';
                    echo '</a>';
                }
                echo '</div>';
            }
        }
    }

    public function addCustomStyles($css): string {
        $custom_css = '
            .wpwps-email-logo { text-align: center; padding: 20px 0; }
            .wpwps-email-logo img { max-width: 200px; height: auto; }
            .wpwps-social-links { text-align: center; padding: 20px 0; }
            .wpwps-social-links a { margin: 0 10px; }
            .wpwps-social-links img { width: 24px; height: 24px; }
        ';

        return $css . $custom_css;
    }
}
