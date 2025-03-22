<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Services\BladeService;

class MarkupSettings {
    private $blade;
    private $template_service;

    public function __construct(BladeService $blade) {
        $this->blade = $blade;
    }

    public function render() {
        $data = [
            'categories' => $this->getCategories(),
            'providers' => $this->getProviders(),
            'markups' => $this->getMarkups()
        ];

        return $this->blade->render('markup.settings', $data);
    }

    private function getCategories() {
        return get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ]);
    }

    private function getProviders() {
        return get_option('wpwps_providers', []);
    }

    private function getMarkups() {
        return get_option('wpwps_markups', []);
    }
}
