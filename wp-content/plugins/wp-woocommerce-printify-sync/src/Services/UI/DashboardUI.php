<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\UI;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\ThemeInterface;

class DashboardUI {
    private $theme;
    private $config;
    
    public function __construct(array $config = []) {
        $this->config = $config;
        $this->theme = $config['theme'] ?? null;
    }
    
    public function getColors(): array {
        return $this->theme ? $this->theme->getColors() : [];
    }
    
    public function getComponent(string $name): ?array {
        return $this->config['components'][$name] ?? null;
    }
    
    public function getAnimation(string $key): mixed {
        return $this->config['animations'][$key] ?? null;
    }
    
    public function getTemplateData(): array {
        return [
            'theme' => $this->theme,
            'components' => $this->config['components'] ?? [],
            'animations' => $this->config['animations'] ?? []
        ];
    }
}
