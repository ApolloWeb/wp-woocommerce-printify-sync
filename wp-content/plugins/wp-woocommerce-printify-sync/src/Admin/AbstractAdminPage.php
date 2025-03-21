<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

abstract class AbstractAdminPage
{
    protected string $template;
    protected string $pageSlug;
    protected array $data = [];

    abstract public function render(): void;

    protected function enqueueAssets(): void
    {
        $cssFile = "wpwps-{$this->pageSlug}.css";
        $jsFile = "wpwps-{$this->pageSlug}.js";

        wp_enqueue_style(
            "wpwps-{$this->pageSlug}",
            WPWS_PLUGIN_URL . "assets/css/{$cssFile}",
            [],
            WPWS_VERSION
        );

        wp_enqueue_script(
            "wpwps-{$this->pageSlug}",
            WPWS_PLUGIN_URL . "assets/js/{$jsFile}",
            ['jquery'],
            WPWS_VERSION,
            true
        );
    }

    protected function renderTemplate(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $this->enqueueAssets();
        
        $templateFile = WPWS_PLUGIN_DIR . "templates/{$this->template}.php";
        if (!file_exists($templateFile)) {
            throw new \RuntimeException(
                sprintf('Template file not found: %s', $templateFile)
            );
        }

        extract($this->data);
        include $templateFile;
    }
}
