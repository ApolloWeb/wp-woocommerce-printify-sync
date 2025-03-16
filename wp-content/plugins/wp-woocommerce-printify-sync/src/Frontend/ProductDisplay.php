<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Frontend;

class ProductDisplay
{
    public function register(): void
    {
        add_filter('woocommerce_get_price_html', [$this, 'addCurrencyInfo'], 10, 2);
        add_action('woocommerce_before_add_to_cart_form', [$this, 'addProviderInfo']);
        add_action('woocommerce_before_cart', [$this, 'addMultiProviderNotice']);
        add_filter('woocommerce_cart_shipping_method_full_label', [$this, 'modifyShippingLabel'], 10, 2);
    }

    public function addCurrencyInfo(string $priceHtml, \WC_Product $product): string
    {
        if (!$this->isPrintifyProduct($product)) {
            return $priceHtml;
        }

        $baseCurrency = get_post_meta($product->get_id(), '_printify_base_currency', true);
        if (!$baseCurrency) {
            return $priceHtml;
        }

        return sprintf(
            '<div class="printify-price">%s<small class="base-price">(%s %s)</small></div>',
            $priceHtml,
            $this->getOriginalPrice($product),
            $baseCurrency
        );
    }

    public function addProviderInfo(): void
    {
        global $product;
        if (!$this->isPrintifyProduct($product)) {
            return;
        }

        $providerId = get_post_meta($product->get_id(), '_printify_provider_id', true);
        if (!$providerId) {
            return;
        }

        $provider = get_option("printify_provider_{$providerId}");
        if (!$provider) {
            return;
        }

        ?>
        <div class="printify-provider-info">
            <span class="provider-label"><?php _e('Print Provider:', 'wp-woocommerce-printify-sync'); ?></span>
            <span class="provider-name"><?php echo esc_html($provider['name']); ?></span>
            <?php if (!empty($provider['location'])): ?>
                <span class="provider-location">
                    <?php echo $this->getCountryFlag($provider['location']); ?>
                    <?php echo esc_html($provider['location']); ?>
                </span>
            <?php endif; ?>
        </div>
        <?php
    }

    public function addMultiProviderNotice(): void
    {
        $providers = $this->getCartProviders();
        if (count($providers) > 1) {
            wc_print_notice(
                sprintf(
                    __('Your cart contains items from %d different print providers. Shipping costs will be calculated separately for each provider.', 'wp-woocommerce-printify-sync'),
                    count($providers)
                ),
                'notice'
            );
        }
    }

    public function modifyShippingLabel(string $label, \WC_Shipping_Rate $method): string
    {
        if (strpos($method->get_id(), 'printify_provider_shipping') === false) {
            return $label;
        }

        $methodSettings = $method->get_meta_data();
        if (empty($methodSettings['provider_name'])) {
            return $label;
        }

        return sprintf(
            '%s <small class="provider-shipping">%s</small>',
            $label,
            sprintf(
                __('via %s', 'wp-woocommerce-printify-sync'),
                $methodSettings['provider_name']
            )
        );
    }
}