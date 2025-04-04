<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface ThemeInterface {
    /**
     * Get theme color palette
     */
    public function getColors(): array;
    
    /**
     * Get spacing values
     */
    public function getSpacing(string $key): ?string;
    
    /**
     * Get typography settings
     */
    public function getTypography(): array;
    
    /**
     * Get border styles
     */
    public function getBorders(): array;
    
    /**
     * Get shadow styles
     */
    public function getShadows(): array;
    
    /**
     * Get CSS custom properties
     */
    public function getCssVariables(): array;
}
