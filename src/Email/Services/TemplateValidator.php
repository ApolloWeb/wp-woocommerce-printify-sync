<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Email\Services;

class TemplateValidator {
    private $required_variables = [
        '{customer_name}',
        '{company_name}',
        '{signature}'
    ];

    public function validateTemplate($content) {
        $errors = [];
        
        // Check required variables
        foreach ($this->required_variables as $var) {
            if (!str_contains($content, $var)) {
                $errors[] = sprintf('Missing required variable: %s', $var);
            }
        }

        // Check HTML structure
        if (!$this->isValidHTML($content)) {
            $errors[] = 'Invalid HTML structure';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function isValidHTML($content) {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($content);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        
        return empty($errors);
    }
}
