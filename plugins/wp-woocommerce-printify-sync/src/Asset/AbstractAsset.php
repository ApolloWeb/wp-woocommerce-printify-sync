<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Asset;

abstract class AbstractAsset implements AssetInterface
{
    protected string $handle;
    protected string $source;
    protected array $dependencies;
    protected string $version;
    protected bool $inFooter;
    protected ?array $localization;

    public function __construct(
        string $handle,
        string $source,
        array $dependencies = [],
        string $version = '',
        bool $inFooter = true,
        ?array $localization = null
    ) {
        $this->handle = $handle;
        $this->source = $source;
        $this->dependencies = $dependencies;
        $this->version = $version ?: WPWPS_VERSION;
        $this->inFooter = $inFooter;
        $this->localization = $localization;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function shouldLoadInFooter(): bool
    {
        return $this->inFooter;
    }

    public function getLocalization(): ?array
    {
        return $this->localization;
    }
}