<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductValidator
{
    private string $currentTime = '2025-03-15 20:00:16';
    private string $currentUser = 'ApolloWeb';

    public function validate(array $data): array
    {
        $errors = [];

        // Required fields
        $required = ['id', 'title', 'description', 'variants'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = sprintf('Missing required field: %s', $field);
            }
        }

        // Validate variants
        if (!empty($data['variants'])) {
            foreach ($data['variants'] as $index => $variant) {
                if (empty($variant['price']) || $variant['price'] <= 0) {
                    $errors[] = sprintf('Invalid price for variant %d', $index + 1);
                }
                if (empty($variant['sku'])) {
                    $errors[] = sprintf('Missing SKU for variant %d', $index + 1);
                }
            }
        }

        // Validate images
        if (!empty($data['images'])) {
            foreach ($data['images'] as $index => $image) {
                if (empty($image['src']) || !filter_var($image['src'], FILTER_VALIDATE_URL)) {
                    $errors[] = sprintf('Invalid image URL at index %d', $index);
                }
            }
        }

        return $errors;
    }
}