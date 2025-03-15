<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface ProductInterface
{
    public function toWooCommerceProduct(): array;
    public function getId(): int;
    public function getTitle(): string;
    public function getDescription(): string;
    public function getPrice(): float;
    public function getImages(): array;
    public function getVariants(): array;
}