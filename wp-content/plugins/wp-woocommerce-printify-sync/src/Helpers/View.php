<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

use eftec\bladeone\BladeOne;

class View {
    private BladeOne $blade;

    public function __construct(string $viewsPath, string $cachePath) {
        $this->blade = new BladeOne(
            $viewsPath,
            $cachePath,
            BladeOne::MODE_DEBUG
        );
    }

    public function render(string $view, array $data = []): string {
        try {
            return $this->blade->run($view, $data);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return sprintf(
                'Error rendering view: %s',
                esc_html($e->getMessage())
            );
        }
    }

    public function addData(array $data): void {
        $this->blade->share($data);
    }
}