<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface TaxonomyHandlerInterface
{
    public function createTerm(string $name, string $taxonomy, array $args = []): int;
    public function assignTerms(int $productId, array $terms, string $taxonomy): bool;
    public function getOrCreateTerm(string $name, string $taxonomy, array $args = []): int;
}