<?php
/**
 * POP3 Helper for WP WooCommerce Printify Sync
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Email;

class Pop3Helper {
    /**
     * Connect to POP3 server
     *
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @return resource|false
     */
    public static function connect($host, $port, $username, $password) {
        $connection = imap_open("{{$host}:{$port}/pop3/ssl/novalidate-cert}", $username, $password);
        if ($connection === false) {
            return false;
        }
        return $connection;
    }

    /**
     * Disconnect from POP3 server
     *
     * @param resource $connection
     * @return void
     */
    public static function disconnect($connection) {
        imap_close($connection);
    }

    /**
     * Fetch emails from POP3 server
     *
     * @param resource $connection
     * @return array
     */
    public static function fetch_emails($connection) {
        $emails = array();

        $num_messages = imap_num_msg($connection);
        for ($i = 1; $i <= $num_messages; $i++) {
            $header = imap_headerinfo($connection, $i);
            $body = imap_body($connection, $i);

            $emails[] = array(
                'subject' => $header->subject,
                'from' => $header->fromaddress,
                'date' => $header->date,
                'body' => $body
            );
        }

        return $emails;
    }

    /**
     * Process email content and extract Printify data
     *
     * @param string $content
     * @return array
     */
    public static function extract_printify_data($content) {
        $printify_data = array();

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
                $printify_data['shipping_address