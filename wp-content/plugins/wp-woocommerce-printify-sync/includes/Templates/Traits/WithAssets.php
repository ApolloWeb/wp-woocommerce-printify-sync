<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Templates\Traits;

trait WithAssets {
    protected $scripts = [];
    protected $styles = [];

    protected function addScript(string $handle, string $src, array $deps = [], $ver = null): self {
        $this->scripts[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver ?? WPPS_VERSION
        ];
        return $this;
    }

    protected function addStyle(string $handle, string $src, array $deps = [], $ver = null): self {
        $this->styles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver ?? WPPS_VERSION
        ];
        return $this;
    }

    protected function enqueueAssets(): void {
        foreach ($this->scripts as $handle => $script) {
            wp_enqueue_script(
                "wpps-{$handle}",
                $script['src'],
                $script['deps'],
                $script['ver'],
                true
            );
        }

        foreach ($this->styles as $handle => $style) {
            wp_enqueue_style(
                "wpps-{$handle}",
                $style['src'],
                $style['deps'],
                $style['ver']
            );
        }
    }
}
