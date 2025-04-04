<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\UI;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\ThemeInterface;

class DashboardTheme implements ThemeInterface {
    private const THEME = [
        'colors' => [
            'primary' => '#96588a',     // WooCommerce Purple
            'secondary' => '#0077b6',   // Deep Blue
            'accent' => '#00b4d8',      // Bright Blue
            'dark' => '#0f1a20',        // Almost Black
            'light' => '#ffffff',       // White
            'glass' => 'rgba(255, 255, 255, 0.95)'
        ],
        'typography' => [
            'family' => 'Inter',
            'weights' => [400, 500, 600, 700],
            'sizes' => [
                'xs' => '0.75rem',
                'sm' => '0.875rem',
                'base' => '1rem',
                'lg' => '1.125rem',
                'xl' => '1.25rem',
                '2xl' => '1.5rem',
                '3xl' => '1.875rem'
            ]
        ],
        'spacing' => [
            'base' => '1rem',
            'section' => '2rem',
            'container' => '3rem'
        ],
        'borders' => [
            'radius' => '0.75rem',
            'glass' => '1px solid rgba(255, 255, 255, 0.125)'
        ],
        'shadows' => [
            'sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            'base' => '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
            'md' => '0 8px 12px -3px rgba(0, 0, 0, 0.15)',
            'glass' => '0 8px 32px rgba(0, 0, 0, 0.08)',
            'glow' => '0 0 15px rgba(150, 88, 138, 0.15)'
        ],
        'components' => [
            'button' => [
                'padding' => '0.75rem 1.5rem',
                'transition' => 'all 0.2s ease-in-out'
            ],
            'card' => [
                'background' => 'rgba(255, 255, 255, 0.95)',
                'backdrop-filter' => 'blur(8px)',
                'transition' => 'transform 0.2s ease, box-shadow 0.2s ease'
            ],
            'navbar' => [
                'height' => '4rem',
                'padding' => '1rem 2rem'
            ],
            'toast' => [
                'z-index' => 1050,
                'margin' => '1rem',
                'backdrop-filter' => 'blur(8px)'
            ]
        ]
    ];

    public function getColors(): array {
        return self::THEME['colors'];
    }
    
    public function getSpacing(string $key): ?string {
        return self::THEME['spacing'][$key] ?? null;
    }
    
    public function getTypography(): array {
        return self::THEME['typography'];
    }
    
    public function getBorders(): array {
        return self::THEME['borders'];
    }
    
    public function getShadows(): array {
        return self::THEME['shadows'];
    }
    
    public function getCssVariables(): array {
        $vars = [];
        
        // Colors
        foreach ($this->getColors() as $name => $value) {
            $vars["--wpwps-color-{$name}"] = $value;
        }
        
        // Typography
        $vars['--wpwps-font-family'] = '"Inter", system-ui, -apple-system, sans-serif';
        foreach ($this->getTypography()['sizes'] as $name => $value) {
            $vars["--wpwps-font-size-{$name}"] = $value;
        }
        
        // Borders & Shadows
        $vars['--wpwps-border-radius'] = $this->getBorders()['radius'];
        $vars['--wpwps-border-radius-pill'] = '50rem';
        foreach ($this->getShadows() as $name => $value) {
            $vars["--wpwps-shadow-{$name}"] = $value;
        }
        
        // Components
        foreach (self::THEME['components'] as $component => $props) {
            foreach ($props as $prop => $value) {
                $vars["--wpwps-{$component}-{$prop}"] = $value;
            }
        }
        
        return $vars;
    }
}
