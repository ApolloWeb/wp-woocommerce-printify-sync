<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Context\SyncContext;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class TaxonomyHelper
{
    private LoggerInterface $logger;
    private SyncContext $context;

    public function __construct(LoggerInterface $logger, SyncContext $context)
    {
        $this->logger = $logger;
        $this->context = $context;
    }

    public function syncCategories(\WC_Product $product, array $categories): void
    {
        $categoryIds = [];
        
        foreach ($categories as $category) {
            try {
                $termId = $this->getOrCreateTerm($category, 'product_cat');
                if ($termId) {
                    $categoryIds[] = $termId;
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to sync category', [
                    'product_id' => $product->get_id(),
                    'category' => $category,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!empty($categoryIds)) {
            wp_set_object_terms($product->get_id(), $categoryIds, 'product_cat');
        }
    }

    public function syncTags(\WC_Product $product, array $tags): void
    {
        $tagIds = [];
        
        foreach ($tags as $tag) {
            try {
                $termId = $this->getOrCreateTerm($tag, 'product_tag');
                if ($termId) {
                    $tagIds[] = $termId;
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to sync tag', [
                    'product_id' => $product->get_id(),
                    'tag' => $tag,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!empty($tagIds)) {
            wp_set_object_terms($product->get_id(), $tagIds, 'product_tag');
        }
    }

    private function getOrCreateTerm(string $name, string $taxonomy): ?int
    {
        $term = get_term_by('name', $name, $taxonomy);
        
        if ($term) {
            return (int)$term->term_id;
        }

        $result = wp_insert_term($name, $taxonomy);
        
        if (is_wp_error($result)) {
            $this->logger->error('Failed to create term', [
                'name' => $name,
                'taxonomy' => $taxonomy,
                'error' => $result->get_error_message()
            ]);
            return null;
        }

        return $result['term_id'];
    }
}