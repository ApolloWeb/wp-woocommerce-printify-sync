<?php
defined('ABSPATH') || exit;
?>
<div class="wpwps-ticket-details">
    <div class="ticket-info">
        <p>
            <label><?php esc_html_e('Customer:', 'wp-woocommerce-printify-sync'); ?></label>
            <?php
            $customer = new WC_Customer($customer_id);
            echo esc_html($customer->get_first_name() . ' ' . $customer->get_last_name());
            ?>
        </p>
        
        <?php if ($order_id): ?>
        <p>
            <label><?php esc_html_e('Order:', 'wp-woocommerce-printify-sync'); ?></label>
            <a href="<?php echo esc_url(get_edit_post_link($order_id)); ?>">
                #<?php echo esc_html($order_id); ?>
            </a>
        </p>
        <?php endif; ?>
        
        <p>
            <label><?php esc_html_e('Urgency:', 'wp-woocommerce-printify-sync'); ?></label>
            <select name="ticket_urgency">
                <option value="low" <?php selected($urgency, 'low'); ?>>
                    <?php esc_html_e('Low', 'wp-woocommerce-printify-sync'); ?>
                </option>
                <option value="medium" <?php selected($urgency, 'medium'); ?>>
                    <?php esc_html_e('Medium', 'wp-woocommerce-printify-sync'); ?>
                </option>
                <option value="high" <?php selected($urgency, 'high'); ?>>
                    <?php esc_html_e('High', 'wp-woocommerce-printify-sync'); ?>
                </option>
            </select>
        </p>
    </div>
    
    <div class="ticket-reply">
        <h3><?php esc_html_e('Reply', 'wp-woocommerce-printify-sync'); ?></h3>
        <div class="ai-suggestion">
            <button type="button" class="button" id="get-ai-suggestion">
                <?php esc_html_e('Get AI Suggestion', 'wp-woocommerce-printify-sync'); ?>
            </button>
            <div id="ai-suggestion-content"></div>
        </div>
        <textarea name="ticket_reply" rows="10" class="large-text"></textarea>
        <button type="submit" class="button button-primary">
            <?php esc_html_e('Send Reply', 'wp-woocommerce-printify-sync'); ?>
        </button>
    </div>
</div>
