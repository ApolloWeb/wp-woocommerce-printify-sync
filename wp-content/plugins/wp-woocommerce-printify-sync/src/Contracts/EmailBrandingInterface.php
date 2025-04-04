<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface EmailBrandingInterface {
    public function getTemplate(string $name): string;
    public function getLogo(): ?string;
    public function getCompanyName(): string;
    public function getSocialLinks(): array;
    public function getSignature(): string;
    public function getGreeting(string $name = ''): string;
    public function getFooter(): string;
}
