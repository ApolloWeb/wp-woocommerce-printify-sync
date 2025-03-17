<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Asset\Collection;

use ApolloWeb\WPWooCommercePrintifySync\Asset\Script;

class ScriptCollection extends AbstractAssetCollection
{
    public function register(): void
    {
        foreach ($this->assets as $asset) {
            if (!($asset instanceof Script)) {
                continue;
            }

            wp_register_script(
                $asset->getHandle(),
                $asset->getSource(),
                $asset->getDependencies(),
                $asset->getVersion(),
                $asset->shouldLoadInFooter()
            );

            if ($asset->getLocalization()) {
                wp_localize_script(
                    $asset->getHandle(),
                    str_replace('-', '_', $asset->getHandle()),
                    $asset->getLocalization()
                );
            }
        }
    }

    public function enqueue(string $handle): void
    {
        if ($asset = $this->get($handle)) {
            wp_enqueue_script($asset->getHandle());
        }
    }

    public function enqueueAll(): void
    {
        foreach ($this->assets as $asset) {
            wp_enqueue_script($asset->getHandle());
        }
    }
}