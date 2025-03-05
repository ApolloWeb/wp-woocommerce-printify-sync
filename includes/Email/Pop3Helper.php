<?php
/**
 * POP3 Helper
 *
 * Helper utilities for POP3 email handling.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Email
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Orders\OrderProcessor;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Pop3Helper {
    /**
     * Email mime parser
     *
     * @var object
     */
    private $parser = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize Email Parser if needed
        if (!class_exists('Email_Parser')) {
            require_once WPWPRINTIFYSYNC_PLUGIN_DIR . 'includes/Libraries/EmailParser.php';
        }
        
        $this->parser = new \Email_Parser();
    }
    
    /**
     * Parse email content and extract data
     *
     * @param string $raw_email Raw email content
     * @return array Parsed email data
     */
    public function parse_email($raw_email) {
        $email_data = array(
            'headers' => array(),
            'subject' => '',
            'from' => '',
            'to' => '',
            'date' => '',
            'content_type' => '',
            'body' => '',
            'html_body' => '',
            'text_body' => '',
            'attachments' => array(),
            'printify_data' => array(),
        );
        
        try {
            // Parse email using the parser library
            $parsed = $this->parser->parse($raw_email);
            
            // Extract basic headers
            $email_data['headers'] = $parsed->getHeaders();
            $email_data['subject'] = $parsed->getHeader('subject');
            $email_data['from'] = $parsed->getHeader('from');
            $email_data['to'] = $parsed->getHeader('to');
            $email_data['date'] = $parsed->getHeader('date');
            $email_data['content_type'] = $parsed->getHeader('content-type');
            
            // Get body parts
            if ($parsed->isMultipart()) {
                foreach ($parsed->getParts() as $part) {
                    $content_type = $part->getHeader('content-type');
                    
                    if (strpos($content_type, 'text/plain') !== false) {
                        $email_data['text_body'] = $part->getContent();
                    } elseif (strpos($content_type, 'text/html') !== false) {
                        $email_data['html_body'] = $part->getContent();
                    } elseif (strpos($content_type, 'multipart/alternative') !== false) {
                        // Handle nested multipart/alternative
                        foreach ($part->getParts() as $subpart) {
                            $sub_content_type = $subpart->getHeader('content-type');
                            
                            if (strpos($sub_content_type, 'text/plain') !== false) {
                                $email_data['text_body'] = $subpart->getContent();
                            } elseif (strpos($sub_content_type, 'text/html') !== false) {
                                $email_data['html_body'] = $subpart->getContent();
                            }
                        }
                    } else {
                        // Handle attachments
                        $filename = $part->getHeader('content-disposition');
                        if (!empty($filename) && preg_match('/filename="([^"]+)"/', $filename, $matches)) {
                            $attachment_name = $matches[1];
                            $email_data['attachments'][] = array(
                                'name' => $attachment_name,
                                'content' => $part->getContent(),
                                'content_type' => $content_type
                            );
                        }
                    }
                }
            } else {
                // Single part email
                $email_data['body'] = $parsed->getContent();
                
                if (strpos($email_data['content_type'], 'text/html') !== false) {
                    $email_data['html_body'] = $email_data['body'];
                } else {
                    $email_data['text_body'] = $email_data['body'];
                }
            }
            
            // Extract Printify data
            $email_data['printify_data'] = $this->extract_printify_data($email_data);
            
            return $email_data;
            
        } catch (\Exception $e) {
            Logger::get_instance()->error('Failed to parse email', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            return $email_data;
        }
    }
    
    /**
     * Extract Printify-specific data from email
     *
     * @param array $email_data Parsed email data
     * @return array Printify data
     */
    public function extract_printify_data($email_data) {
        $printify_data = array(
            'order_id' => '',
            'status' => '',
            'tracking_number' => '',
            'tracking_url' => '',
            'shipping_carrier' => '',
            'shipping_method' => '',
            'estimated_delivery' => '',
            'line_items' => array(),
            'shipping_address' => array(),
        );
        
        // Use HTML content first if available, fall back to text
        $content = !empty($email_data['html_body']) ? $email_data['html_body'] : $email_data['text_body'];
        
        if (empty($content)) {
            return $printify_data;
        }
        
        // Extract order ID
        if (preg_match('/Order\s*(?:#|ID|Number|:)?\s*([A-Za-z0-9-]+)/i', $content, $matches)) {
            $printify_data['order_id'] = trim($matches[1]);
        }
        
        // Extract order status
        $statuses = array('fulfilled', 'shipped', 'processing', 'canceled', 'on hold');
        foreach ($statuses as $status) {
            if (stripos($content, $status) !== false) {
                $printify_data['status'] = strtolower($status);
                break;
            }
        }
        
        // Extract tracking information
        if (preg_match('/Tracking\s*(?:Number|#|:)?\s*([A-Za-z0-9-]+)/i', $content, $matches)) {
            $printify_data['tracking_number'] = trim($matches[1]);
        }
        
        if (preg_match('/Track your order:\s*<a[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches)) {
            $printify_data['tracking_url'] = trim($matches[1]);
        }
        
        // Extract shipping carrier
        $carriers = array('USPS', 'FedEx', 'UPS', 'DHL', 'Royal Mail', 'Canada Post');
        foreach ($carriers as $carrier) {
            if (stripos($content, $carrier) !== false) {
                $printify_data['shipping_carrier'] = $carrier;
                break;
            }
        }
        
        // Extract shipping method
        if (preg_match('/Shipping Method:\s*([^<\r\n]+)/i', $content, $matches)) {
            $printify_data['shipping_method'] = trim($matches[1]);
        }
        
        // Extract estimated delivery
        if (preg_match('/Estimated Delivery:\s*([^<\r\n]+)/i', $content, $matches)) {
            $printify_data['estimated_delivery'] = trim($matches[1]);
        }
        
        // Extract shipping address (more complex)
        if (preg_match('/Shipping Address:?.*?<td[^>]*>(.*?)<\/td>/is', $content, $address_match)) {
            $address_html = $address_match[1];
            $address_text = strip_tags($address_html);
            $address_parts = array_filter(array_map('trim', explode("\n", $address_text)));
            
            if (!empty($address_parts)) {
                // First line is usually name
                $printify_data['shipping_address']['name'] = $address_parts[0] ?? '';
                
                // Try to extract other parts
                foreach ($address_parts as $part) {
                    if (preg_match('/^(.+),\s*([A-Z]{2})\s*(\d{5}(?:-\d{4})?)$/', $part, $location_match)) {
                        // City, State ZIP pattern
                        $printify_data['shipping_address']['city'] = $location_match[1];
                        $printify_data['shipping_address']['state'] = $location_match[2];
                        $printify_data['shipping_address']['zip'] = $location_match[3];
                    }
                }
            }
        }
        
        return $printify_data;
    }
    
    /**
     * Process extracted Printify data and update orders
     *
     * @param array $printify_data Extracted Printify data
     * @return bool|int False on failure, order ID on success
     */
    public function process_printify_data($printify_data) {
        // Skip if no order ID found
        if (empty($printify_data['order_id'])) {
            Logger::get_instance()->debug('No Printify order ID found in email');
            return false;
        }
        
        // Find corresponding WooCommerce order
        $order_id = $this->find_woocommerce_order_id($printify_data['order_id']);
        
        if (!$order_id) {
            Logger::get_instance()->notice('No matching WooCommerce order found for Printify order', array(
                'printify_order_id' => $printify_data['order_id']
            ));
            return false;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            Logger::get_instance()->error('Failed to load WooCommerce order', array(
                'order_id' => $order_id,
                'printify_order_id' => $printify_data['order_id']
            ));
            return false;
        }
        
        // Update order meta with Printify data
        update_post_meta($order_id, '_printify_last_update', current_time('mysql'));
        
        if (!empty($printify_data['tracking_number'])) {
            update_post_meta($order_id, '_printify_tracking_number', $printify_data['tracking_number']);
        }
        
        if (!empty($printify_data['tracking_url'])) {
            update_post_meta($order_id, '_printify_tracking_url', $printify_data['tracking_url']);
        }
        
        if (!empty($printify_data['shipping_carrier'])) {
            update_post_meta($order_id, '_printify_shipping_carrier', $printify_data['shipping_carrier']);
        }
        
        if (!empty($printify_data['estimated_delivery'])) {
            update_post_meta($order_id, '_printify_estimated_delivery', $printify_data['estimated_delivery']);
        }
        
        // Update order status if needed
        if (!empty($printify_data['status'])) {
            $status_mapping = array(
                'shipped' => 'wc-completed',
                'fulfilled' => 'wc-completed',
                'processing' => 'wc-processing',
                'canceled' => 'wc-cancelled',
                'on hold' => 'wc-on-hold'
            );
            
            $wc_status = isset($status_mapping[$printify_data['status']]) ? $status_mapping[$printify_data['status']] : '';
            
            if (!empty($wc_status) && $order->get_status() !== $wc_status) {
                // Add order note
                $note = sprintf(
                    __('Printify status updated to "%s". Tracking number: %s', 'wp-woocommerce-printify-sync'),
                    $printify_data['status'],
                    !empty($printify_data['tracking_number']) ? $printify_data['tracking_number'] : __('Not available', 'wp-woocommerce-printify-sync')
                );
                
                $order->update_status($wc_status, $note);
                
                Logger::get_instance()->info('Updated order status from Printify email', array(
                    'order_id' => $order_id,
                    'printify_order_id' => $printify_data['order_id'],
                    'new_status' => $wc_status,
                    'printify_status' => $printify_data['status']
                ));
                
                // Trigger action for other integrations
                do_action('wpwprintifysync_order_status_updated_by_email', $order_id, $printify_data);
            }
        }
        
        return $order_id;
    }
    
    /**
     * Find WooCommerce order ID from Printify order ID
     *
     * @param string $printify_order_id Printify order ID
     * @return int|bool WooCommerce order ID or false if not found
     */
    public function find_woocommerce_order_id($printify_order_id) {
        global $wpdb;
        
        // Try direct meta query first
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_order_id' AND meta_value = %s LIMIT 1",
            $printify_order_id
        ));
        
        if ($order_id) {
            return (int) $order_id;
        }
        
        // If not found, try to find by external order ID which might be stored differently
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_external_id' AND meta_value = %s LIMIT 1",
            $printify_order_id
        ));
        
        if ($order_id) {
            return (int) $order_id;
        }
        
        return false;
    }
    
    /**
     * Check if an email is relevant for processing
     *
     * @param array $email_data Parsed email data
     * @param array $settings Plugin settings
     * @return bool True if email should be processed
     */
    public function is_relevant_email($email_data, $settings) {
        // Check from address filter if configured
        if (!empty($settings['from_filter'])) {
            if (stripos($email_data['from'], $settings['from_filter']) === false) {
                return false;
            }
        }
        
        // Check subject filter if configured
        if (!empty($settings['subject_filter'])) {
            if (stripos($email_data['subject'], $settings['subject_filter']) === false) {
                return false;
            }
        }
        
        // Check if it appears to be a Printify email
        $is_printify_email = false;
        
        // Check for Printify signature in content
        $content = !empty($email_data['