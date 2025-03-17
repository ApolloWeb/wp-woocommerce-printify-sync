<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Asset\Collection;

use ApolloWeb\WPWooCommercePrintifySync\Asset\Style;

class StyleCollection extends AbstractAssetCollection
{
    public function register(): void
    {
        foreach ($this->assets as $asset) {
            if (!($asset instanceof Style)) {
                continue;
            }

            wp_register_style(
                $asset->getHandle(),
                $asset->getSource(),
                $asset->getDependencies(),
                $asset->getVersion()
            );
        }
    }

    public function enqueue(string $handle): void
    {
        if ($asset = $this->get($handle)) {
            wp_enqueue_style($asset->getHandle());
        }
    }

    public function enqueueAll(): void
    {
        foreach ($this->assets as $asset) {
            wp_enqueue_style($asset->getHandle());
        }
    }
}