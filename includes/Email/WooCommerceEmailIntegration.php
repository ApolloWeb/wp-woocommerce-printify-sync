<?php
/**
 * WooCommerce Email Integration
 *
 * Integrates ticket system emails with WooCommerce email templates
 * for brand consistency across all customer communications.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Email
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Tickets\TicketManager;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WooCommerceEmailIntegration {
    /**
     * Singleton instance
     *
     * @var WooCommerceEmailIntegration
     */
    private static $instance = null;
    
    /**
     * WooCommerce Email instance
     *
     * @var \WC_Emails
     */
    private $wc_mailer = null;
    
    /**
     * Get singleton instance
     *
     * @return WooCommerceEmailIntegration
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize WC_Emails instance
        $this->wc_mailer = function_exists('WC') ? WC()->mailer() : null;
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Register custom email types
        add_action('woocommerce_email_classes', array($this, 'register_custom_emails'));
        
        // Apply WooCommerce template to our emails
        add_filter('wpwprintifysync_ticket_email_html', array($this, 'apply_woocommerce_template'), 10, 2);
        
        // Add our settings to WooCommerce email settings
        add_filter('woocommerce_email_settings', array($this, 'add_email_settings'));
    }
    
    /**
     * Register custom email classes with WooCommerce
     *
     * @param array $email_classes WooCommerce email classes
     * @return array Modified email classes
     */
    public function register_custom_emails($email_classes) {
        // Include our custom email classes
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/Email/TicketEmails/TicketCreatedEmail.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/Email/TicketEmails/TicketReplyEmail.php';
        require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/Email/TicketEmails/TicketResolvedEmail.php';
        
        // Add our email classes
        $email_classes['WC_Email_Ticket_Created'] = new TicketEmails\TicketCreatedEmail();
        $email_classes['WC_Email_Ticket_Reply'] = new TicketEmails\TicketReplyEmail();
        $email_classes['WC_Email_Ticket_Resolved'] = new TicketEmails\TicketResolvedEmail();
        
        return $email_classes;
    }
    
    /**
     * Apply WooCommerce template to ticket emails
     *
     * @param string $content Email content
     * @param array $email_data Email data including ticket details
     * @return string Formatted email content
     */
    public function apply_woocommerce_template($content, $email_data) {
        if (!$this->wc_mailer) {
            return $content;
        }
        
        // Get WooCommerce email template
        ob_start();
        
        $email_heading = isset($email_data['heading']) ? $email_data['heading'] : '';
        $ticket = isset($email_data['ticket']) ? $email_data['ticket'] : null;
        
        // Get WooCommerce email template path
        $template = 'emails/email-header.php';
        
        // Load header
        wc_get_template(
            $template,
            array(
                'email_heading' => $email_heading,
                'plain_text' => false,
                'email' => $this->wc_mailer,
            )
        );
        
        // Email body
        echo wp_kses_post(wpautop(wptexturize($content)));
        
        // Load footer
        wc_get_template(
            'emails/email-footer.php',
            array(
                'plain_text' => false,
                'email' => $this->wc_mailer,
            )
        );
        
        $final_content = ob_get_clean();
        
        return $final_content;
    }
    
    /**
     * Add our email settings to WooCommerce email settings
     *
     * @param array $settings WooCommerce email settings
     * @return array Modified settings
     */
    public function add_email_settings($settings) {
        $ticket_settings = array(
            array(
                'title' => __('Ticket Emails', 'wp-woocommerce-printify-sync'),
                'type' => 'title',
                'desc' => __('Email settings for the ticketing system.', 'wp-woocommerce-printify-sync'),
                'id' => 'ticket_email_options',
            ),
            array(
                'title' => __('Ticket Email Branding', 'wp-woocommerce-printify-sync'),
                'desc' => __('Use WooCommerce email branding for ticket emails', 'wp-woocommerce-printify-sync'),
                'id' => 'wpwprintifysync_use_wc_branding',
                'default' => 'yes',
                'type' => 'checkbox',
                'autoload' => false,
            ),
            array(
                'type' => 'sectionend',
                'id' => 'ticket_email_options',
            ),
        );
        
        // Find position to insert our settings
        $position = array_search(
            array(
                'type' => 'sectionend',
                'id' => 'email_template_options',
            ),
            $settings
        );
        
        if ($position !== false) {
            // Insert our settings after email template options
            array_splice($settings, $position + 1, 0, $ticket_settings);
        } else {
            // Append to the end if section not found
            $settings = array_merge($settings, $ticket_settings);
        }
        
        return $settings;
    }
    
    /**
     * Send ticket notification email using WooCommerce template
     *
     * @param string $type Email type (created, reply, resolved)
     * @param array $data Email data
     * @return bool Success status
     */
    public function send_ticket_email($type, $data) {
        if (!$this->wc_mailer) {
            return false;
        }
        
        $recipient = $data['customer_email'];
        
        switch ($type) {
            case 'created':
                $email_key = 'WC_Email_Ticket_Created';
                break;
                
            case 'reply':
                $email_key = 'WC_Email_Ticket_Reply';
                break;
                
            case 'resolved':
                $email_key = 'WC_Email_Ticket_Resolved';
                break;
                
            default:
                return false;
        }
        
        // Get the email object
        $emails = $this->wc_mailer->get_emails();
        if (!isset($emails[$email_key])) {
            return false;
        }
        
        $email = $emails[$email_key];
        
        // Send the email
        return $email->trigger($data);
    }
}