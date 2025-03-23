<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class SignatureBuilder {
    private $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
    }
    
    public function generateSignature(): string {
        ob_start();
        
        $company_name = $this->settings->get('company_name', get_bloginfo('name'));
        $logo_url = $this->settings->get('signature_logo', '');
        $social_links = $this->settings->get('social_links', []);
        $contact_info = $this->settings->get('contact_info', []);
        
        include WPPS_PATH . 'templates/email/signature.php';
        
        return ob_get_clean();
    }
    
    public function getPreviewHtml(): string {
        return '<div class="wpwps-signature-preview">' . 
               $this->generateSignature() . 
               '</div>';
    }
}
