<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Exception;

class TemplateLoader {
    private $plugin_path;
    private $template_path;
    private $cache = [];
    private $logger;

    public function __construct(Logger $logger = null) {
        $this->plugin_path = WPWPS_PLUGIN_DIR;
        $this->template_path = 'wp-woocommerce-printify-sync/';
        $this->logger = $logger;
    }

    public function locateTemplate($template_name) {
        // Check cache first
        if (isset($this->cache[$template_name])) {
            return $this->cache[$template_name];
        }

        // Allow theme override
        $template = locate_template([
            $this->template_path . $template_name,
            'printify/' . $template_name,
            $template_name
        ]);
        
        if (!$template) {
            $template = $this->plugin_path . 'templates/' . $template_name;
        }

        // Cache the result
        $this->cache[$template_name] = $template;
        
        if ($this->logger) {
            $this->logger->debug(sprintf('Template located: %s -> %s', $template_name, $template));
        }

        return $template;
    }

    /**
     * Validate template file
     */
    private function validateTemplate($template) {
        if (!file_exists($template)) {
            throw new Exception(sprintf('Template file not found: %s', $template));
        }

        if (!is_readable($template)) {
            throw new Exception(sprintf('Template file not readable: %s', $template));
        }

        return true;
    }

    /**
     * Get template directory URL
     */
    public function getTemplateUrl($path = '') {
        return plugins_url('templates/' . ltrim($path, '/'), WPWPS_PLUGIN_FILE);
    }

    /**
     * Check if template exists
     */
    public function templateExists($template_name) {
        return file_exists($this->locateTemplate($template_name));
    }

    /**
     * Set custom template path
     */
    public function setTemplatePath($path) {
        $this->template_path = rtrim($path, '/') . '/';
    }

    /**
     * Apply template filters
     */
    public function applyFilters($content, $template_name, $data = []) {
        return apply_filters('wpwps_template_content', $content, $template_name, $data);
    }

    public function render($template_name, $data = []) {
        $template = $this->locateTemplate($template_name);
        
        $this->validateTemplate($template);

        if (!empty($data)) {
            extract($data, EXTR_SKIP);
        }

        try {
            ob_start();
            include $template;
            $content = ob_get_clean();
            return $this->applyFilters($content, $template_name, $data);
        } catch (Exception $e) {
            ob_end_clean();
            if ($this->logger) {
                $this->logger->error(sprintf('Template render error: %s', $e->getMessage()));
            }
            throw $e;
        }
    }

    public function renderPartial($partial_name, $data = []) {
        return $this->render('partials/' . $partial_name, $data);
    }

    public function clearCache() {
        $this->cache = [];
    }
}
