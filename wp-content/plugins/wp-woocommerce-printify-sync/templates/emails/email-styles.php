<?php
/**
 * Email Styles
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load colors
$bg = get_option('woocommerce_email_background_color');
$body = get_option('woocommerce_email_body_background_color');
$text = get_option('woocommerce_email_text_color');

// Pick a contrasting color for links
$link_color = wc_light_or_dark($body, '#1c1e21', '#ffffff');

?>

#template_container {
    box-shadow: 0 1px 4px rgba(0,0,0,0.1) !important;
    background-color: <?php echo esc_attr($body); ?>;
    border: 1px solid #dedede;
    border-radius: 3px !important;
}

#template_header {
    background-color: <?php echo esc_attr(get_option('woocommerce_email_header_background_color')); ?>;
    border-radius: 3px 3px 0 0 !important;
    color: <?php echo esc_attr(get_option('woocommerce_email_header_text_color')); ?>;
    border-bottom: 0;
    font-weight: bold;
    line-height: 100%;
    vertical-align: middle;
    font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
}

#template_footer td {
    padding: 0;
    border-radius: 6px;
}

#body_content {
    background-color: <?php echo esc_attr($body); ?>;
}

#body_content table td {
    padding: 48px;
}

#body_content table td td {
    padding: 12px;
}

#body_content table td th {
    padding: 12px;
}

#body_content p {
    margin: 0 0 16px;
}

.ticket-details {
    background-color: <?php echo esc_attr(wc_hex_lighter($body, 3)); ?>;
    border-radius: 4px;
    margin-bottom: 40px;
}

.ticket-message {
    background-color: #ffffff;
    padding: 24px;
    border-radius: 4px;
    margin-top: 24px;
}

.ticket-footer {
    text-align: center;
    padding: 24px;
    color: <?php echo esc_attr(wc_hex_darker($text, 20)); ?>;
}

.button {
    background-color: <?php echo esc_attr(get_option('woocommerce_email_base_color')); ?>;
    border-radius: 3px;
    color: #ffffff;
    padding: 12px 24px;
    text-decoration: none;
    display: inline-block;
    margin: 16px 0;
}

.ticket-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.status-open {
    background-color: #dff0d8;
    color: #3c763d;
}

.status-pending {
    background-color: #fcf8e3;
    color: #8a6d3b;
}

.status-resolved {
    background-color: #d9edf7;
    color: #31708f;
}

Would you like me to continue with:
1. The automatic email template registration?
2. Template customization options in WooCommerce?
3. Additional email templates (escalation, internal notes)?
4. Template preview functionality?