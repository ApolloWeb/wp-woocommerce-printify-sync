<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class TaxonomyManager extends AbstractService
{
    private array $cachedTerms = [];

    public function createProductCategory(string $name, ?int $parentId = null): ?int
    {
        try {
            $args = [
                'description' => '',
                'parent' => $parentId ?? 0
            ];

            $term = term_exists($name, 'product_cat');
            if (!$term) {
                $term = wp_insert_term($name, 'product_cat', $args);
            }

            return is_wp_error($term) ? null : $term['term_id'];

        } catch (\Exception $e) {
            $this->logError('createProductCategory', $e, [
                'name' => $name,
                'parent_id' => $parentId
            ]);
            return null;
        }
    }

    public function createProductTag(string $name): ?int
    {
        try {
            $term = term_exists($name, 'product_tag');
            if (!$term) {
                $term = wp_insert_term($name, 'product_tag');
            }

            return is_wp_error($term) ? null : $term['term_id'];

        } catch (\Exception $e) {
            $this->logError('createProductTag', $e, ['name' => $name]);
            return null;
        }
    }

    public function ensureAttributeExists(string $name, array $terms = []): bool
    {
        try {
            $attributeName = wc_sanitize_taxonomy_name($name);
            $attributeLabel = ucfirst(str_replace('-', ' ', $attributeName));

            // Create attribute if it doesn't exist
            if (!taxonomy_exists('pa_' . $attributeName)) {
                $result = wc_create_attribute([
                    'name' => $attributeLabel,
                    'slug' => $attributeName,
                    'type' => 'select',
                    'order_by' => 'menu_order',
                    'has_archives' => false
                ]);

                if (is_wp_error($result)) {
                    throw new \Exception($result->get_error_message());
                }
            }

            // Create terms
            foreach ($terms as $term) {
                $this->ensureAttributeTermExists($attributeName, $term);
            }

            return true;

        } catch (\Exception $e) {
            $this->logError('ensureAttributeExists', $e, [
                'name' => $name,
                'terms' => $terms
            ]);
            return false;
        }
    }

    private function ensureAttributeTermExists(string $attribute, string $term): void
    {
        $taxonomy = 'pa_' . $attribute;
        $cacheKey = $taxonomy . '_' . md5($term);

        if (!isset($this->cachedTerms[$cacheKey])) {
            if (!term_exists($term, $taxonomy)) {
                wp_insert_term($term, $taxonomy);
            }
            $this->cachedTerms[$cacheKey] = true;
        }
    }
}