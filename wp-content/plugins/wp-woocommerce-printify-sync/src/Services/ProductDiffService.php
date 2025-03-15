<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductDiffService
{
    private string $currentTime = '2025-03-15 20:00:16';
    private string $currentUser = 'ApolloWeb';

    public function trackChanges(int $productId, array $oldData, array $newData): void
    {
        $changes = [];

        // Track basic field changes
        foreach (['title', 'description', 'price'] as $field) {
            if (isset($oldData[$field], $newData[$field]) && $oldData[$field] !== $newData[$field]) {
                $changes[$field] = [
                    'from' => $oldData[$field],
                    'to' => $newData[$field]
                ];
            }
        }

        // Track variant changes
        $changes['variants'] = $this->compareVariants($oldData['variants'] ?? [], $newData['variants'] ?? []);

        // Track image changes
        $changes['images'] = $this->compareImages($oldData['images'] ?? [], $newData['images'] ?? []);

        if (!empty($changes)) {
            update_post_meta($productId, '_printify_last_changes', [
                'changes' => $changes,
                'date' => $this->currentTime,
                'user' => $this->currentUser
            ]);
        }
    }

    private function compareVariants(array $old, array $new): array
    {
        $changes = [];
        
        // Track removed variants
        foreach ($old as $variant) {
            if (!$this->findVariantById($variant['id'], $new)) {
                $changes['removed'][] = $variant['id'];
            }
        }

        // Track added/modified variants
        foreach ($new as $variant) {
            $oldVariant = $this->findVariantById($variant['id'], $old);
            if (!$oldVariant) {
                $changes['added'][] = $variant['id'];
            } elseif ($this->variantChanged($oldVariant, $variant)) {
                $changes['modified'][] = [
                    'id' => $variant['id'],
                    'changes' => $this->getVariantChanges($oldVariant, $variant)
                ];
            }
        }

        return $changes;
    }

    private function compareImages(array $old, array $new): array
    {
        return [
            'added' => array_values(array_diff(array_column($new, 'src'), array_column($old, 'src'))),
            'removed' => array_values(array_diff(array_column($old, 'src'), array_column($new, 'src')))
        ];
    }
}