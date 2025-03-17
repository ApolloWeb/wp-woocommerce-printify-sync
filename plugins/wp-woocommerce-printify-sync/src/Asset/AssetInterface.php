<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Asset;

interface AssetInterface
{
    public function getHandle(): string;
    public function getSource(): string;
    public function getDependencies(): array;
    public function getVersion(): string;
    public function shouldLoadInFooter(): bool;
    public function getLocalization(): ?array;
}