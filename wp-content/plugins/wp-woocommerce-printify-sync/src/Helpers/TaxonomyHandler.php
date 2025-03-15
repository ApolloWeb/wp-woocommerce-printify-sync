<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\TaxonomyHandlerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Exceptions\TaxonomyException;

class TaxonomyHandler implements TaxonomyHandlerInterface
{
    private string $currentTime;
    private string $currentUser;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:09:19
        $this->currentUser = $currentUser; // ApolloWeb
    }

    public function createTerm(string $name, string $taxonomy, array $args = []): int
    {
        $term = wp_insert_term($name, $taxonomy, $args);
        
        if (is_wp_error($term)) {
            throw new TaxonomyException(
                "Failed to create term: {$term->get_error_message()}"
            );
        }

        add_term_meta($term['term_id'], '_wpwps_created_at', $this->currentTime);
        add_term_meta($term['term_id'], '_wpwps_created_by', $this->currentUser);

        return $term['term_id'];
    }

    public function assignTerms(int $productId, array $terms, string $taxonomy): bool
    {
        $result = wp_set_object_terms($productId, $terms, $taxonomy);
        
        if (is_wp_error($result)) {
            throw new TaxonomyException(
                "Failed to assign terms: {$result->get_error_message()}"
            );
        }

        return true;
    }

    public function getOrCreateTerm(string $name, string $taxonomy, array $args = []): int
    {
        $term = get_term_by('name', $name, $taxonomy);
        
        if ($term) {
            return $term->term_id;
        }

        return $this->createTerm($name, $taxonomy, $args);
    }
}