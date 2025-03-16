<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class ProductFieldMapper extends AbstractService
{
    use TimeStampTrait;

    private array $cachedAttributes = [];

    public function mapPrintifyToWooCommerce(array $printifyProduct): array
    {
        try {
            // Basic product data
            $productData = [
                'post_title'    => $printifyProduct['title'],
                'post_content'  => $this->formatDescription($printifyProduct['description']),
                'post_excerpt'  => $printifyProduct['short_description'] ?? '',
                'post_status'   => 'publish',
                'post_type'     => 'product',
                'meta_input'    => $this->getMetaInput($printifyProduct),
            ];

            // Handle variants
            if (!empty($printifyProduct['variants'])) {
                $productData['attributes'] = $this->mapAttributes($printifyProduct['variants']);
                $productData['variations'] = $this->mapVariants($printifyProduct['variants']);
            }

            return $productData;

        } catch (\Exception $e) {
            $this->logError('mapPrintifyToWooCommerce', $e, [
                'printify_id' => $printifyProduct['id']
            ]);
            throw $e;
        }
    }

    private function getMetaInput(array $printifyProduct): array
    {
        return [
            // WooCommerce fields
            '_regular_price' => $this->getBasePrice($printifyProduct),
            '_price'        => $this->getCurrentPrice($printifyProduct),
            '_sku'          => $printifyProduct['sku'] ?? '',
            '_stock_status' => $this->getStockStatus($printifyProduct),
            '_tax_status'   => 'taxable',
            '_tax_class'    => 'standard',
            
            // Printify specific fields
            '_printify_id'           => $printifyProduct['id'],
            '_printify_shop_id'      => $printifyProduct['shop_id'],
            '_printify_blueprint_id' => $printifyProduct['blueprint_id'],
            '_printify_provider_id'  => $printifyProduct['provider_id'],
            '_printify_variants'     => $this->formatVariantData($printifyProduct['variants']),
            '_printify_sync_data'    => $this->getSyncData(),
            '_printify_shipping_profile' => $printifyProduct['shipping'] ?? [],
            '_printify_production'   => [
                'print_areas' => $printifyProduct['print_areas'] ?? [],
                'templates'   => $printifyProduct['templates'] ?? [],
                'mockups'     => $printifyProduct['mockups'] ?? []
            ],
            '_printify_quality'      => $this->extractQualityData($printifyProduct)
        ];
    }

    private function mapAttributes(array $variants): array
    {
        $attributes = [];
        
        foreach ($variants as $variant) {
            foreach ($variant['options'] as $name => $value) {
                $attributeName = wc_sanitize_taxonomy_name('pa_' . $name);
                $attributes[$attributeName][] = $value;
            }
        }

        // Create or update attributes
        foreach ($attributes as $taxonomy => $terms) {
            $this->ensureAttributeExists($taxonomy, array_unique($terms));
        }

        return $attributes;
    }

    private function mapVariants(array $variants): array
    {
        $mappedVariants = [];

        foreach ($variants as $variant) {
            $mappedVariants[] = [
                'sku'           => $variant['sku'],
                'regular_price' => $this->formatPrice($variant['price']),
                'sale_price'    => $this->formatPrice($variant['sale_price'] ?? ''),
                'stock'         => $variant['quantity'] ?? null,
                'dimensions'    => $this->formatDimensions($variant),
                'weight'        => $variant['weight'] ?? '',
                'image_id'      => $variant['image_id'] ?? '',
                'attributes'    => $this->formatVariantAttributes($variant['options'])
            ];
        }

        return $mappedVariants;
    }

    private function ensureAttributeExists(string $taxonomy, array $terms): void
    {
        if (isset($this->cachedAttributes[$taxonomy])) {
            return;
        }

        if (!taxonomy_exists($taxonomy)) {
            wc_create_attribute([
                'name'         => ucfirst(str_replace('pa_', '', $taxonomy)),
                'slug'         => $taxonomy,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);
        }

        foreach ($terms as $term) {
            if (!term_exists($term, $taxonomy)) {
                wp_insert_term($term, $taxonomy);
            }
        }

        $this->cachedAttributes[$taxonomy] = true;
    }

    private function formatDescription(string $description): string
    {
        // Convert Printify formatting to WordPress
        $description = wp_kses_post($description);
        
        // Add size chart if available
        if (isset($this->config->get('size_chart_html'))) {
            $description .= $this->config->get('size_chart_html');
        }

        return $description;
    }

    private function getBasePrice(array $product): string
    {
        $basePrice = $product['price'] ?? 0;
        $markup = $this->config->get('default_markup', 1.5);
        return $this->formatPrice($basePrice * $markup);
    }

    private function getCurrentPrice(array $product): string
    {
        return isset($product['sale_price']) 
            ? $this->formatPrice($product['sale_price'])
            : $this->getBasePrice($product);
    }

    private function formatPrice($price): string
    {
        return number_format((float)$price, 2, '.', '');
    }

    private function getStockStatus(array $product): string
    {
        return ($product['quantity'] ?? 0) > 0 ? 'instock' : 'outofstock';
    }

    private function formatVariantData(array $variants): array
    {
        return array_map(function ($variant) {
            return [
                'variant_id'     => $variant['id'],
                'print_areas'    => $variant['print_areas'] ?? [],
                'print_details'  => $variant['print_details'] ?? [],
                'shipping_info'  => $variant['shipping'] ?? [],
                'cost'          => $variant['cost'],
                'markup'        => $this->calculateMarkup($variant)
            ];
        }, $variants);
    }

    private function getSyncData(): array
    {
        return [
            'last_sync'     => $this->getCurrentTime(),
            'sync_status'   => 'success',
            'error_log'     => []
        ];
    }

    private function extractQualityData(array $product): array
    {
        return [
            'dpi'        => $product['print_quality']['dpi'] ?? null,
            'dimensions' => $product['print_quality']['dimensions'] ?? null,
            'colors'     => $product['print_quality']['colors'] ?? null
        ];
    }

    private function formatDimensions(array $variant): array
    {
        return [
            'length' => $variant['dimensions']['length'] ?? '',
            'width'  => $variant['dimensions']['width'] ?? '',
            'height' => $variant['dimensions']['height'] ?? ''
        ];
    }

    private function formatVariantAttributes(array $options): array
    {
        $attributes = [];
        foreach ($options as $name => $value) {
            $attributes['pa_' . wc_sanitize_taxonomy_name($name)] = $value;
        }
        return $attributes;
    }

    private function calculateMarkup(array $variant): float
    {
        $cost = $variant['cost'];
        $price = $variant['price'];
        return $cost > 0 ? ($price - $cost) / $cost : 0;
    }
}