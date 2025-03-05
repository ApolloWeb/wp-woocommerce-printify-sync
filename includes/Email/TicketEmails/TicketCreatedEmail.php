<?php
/**
 * Ticket Created Email
 *
 * Email sent to customers when a new ticket is created.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Email\TicketEmails
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Email\TicketEmails;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ticket Created Email
 */
class TicketCreatedEmail extends \WC_Email {
    /**
     * Ticket data
     *
     * @var array
     */
    public $ticket_data = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'ticket_created';
        $this->customer_email = true;
        $this->title = __('Ticket Created', 'wp-woocommerce-printify-sync');
        $this->description = __('This email is sent to customers when a new support ticket is created.', 'wp-woocommerce-printify-sync');
        
        $this->template_html = 'emails/ticket-created.php';
        $this->template_plain = 'emails/plain/ticket-created.php';
        $this->template_base = WPWPRINTIFYSYNC_PLUGIN_DIR . 'templates/';
        
        $this->placeholders = array(
            '{ticket_number}' => '',
            '{ticket_subject}' => '',
            '{site_title}' => $this->get_blogname(),
        );
        
        // Call parent constructor
        parent::__construct();
    }
    
    /**
     * Get email subject.
     *
     * @return string
     */
    public function get_default_subject() {
        return __('[{site_title}] Your Support Ticket #{ticket_number} Has Been Created', 'wp-woocommerce-printify-sync');
    }
    
    /**
     * Get email heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return __('Your Support Ticket Has Been Created', 'wp-woocommerce-printify-sync');
    }
    
    /**
     * Trigger the sending of this email.
     *
     * @param array $ticket_data Ticket data.
     * @return bool Success status
     */
    public function trigger($ticket_data) {
        $this->ticket_data = $ticket_data;
        
        if (!$this->ticket_data || !isset($this->ticket_data['customer_email'])) {
            return false;
        }
        
        $this->recipient = $this->ticket_data['customer_email'];
        
        if (!$this->is_enabled() || !$this->get_recipient()) {
            return false;
        }
        
        // Set placeholders
        $this->placeholders['{ticket_number}'] = $this->ticket_data['ticket_number'];
        $this->placeholders['{ticket_subject}'] = $this->ticket_data['subject'];
        
        // Send the email
        return $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
    }
    
    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'ticket' => $this->ticket_data,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
            ),
            '',
            $this->template_base
        );
    }
    
    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'ticket' => $this->ticket_data,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
            ),
            '',
            $this->template_base
        );
    }
}