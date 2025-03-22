<?php
/**
 * Code Diagnostics Utility.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utilities
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;

/**
 * Diagnostics class for scanning the codebase for potential issues.
 */
class Diagnostics {
    // ...existing code...

    /**
     * Check for missing class references.
     */
    private function checkMissingClasses() {
        $this->logger->info('Checking for missing class references');
        
        try {
            $php_files = $this->scanDirectory($this->root_dir . 'src', 'php');
            $all_classes = $this->extractAllClasses($php_files);
            
            foreach ($php_files as $file) {
                if (!file_exists($file) || !is_readable($file)) {
                    continue;
                }
                
                try {
                    $content = file_get_contents($file);
                    if ($content === false) {
                        continue;
                    }
                    
                    $referenced_classes = $this->extractClassReferences($content);
                    
                    foreach ($referenced_classes as $class) {
                        // Skip empty classes
                        if (empty($class)) {
                            continue;
                        }
                        
                        // Make sure the class name is valid
                        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff\\\\]*$/', $class)) {
                            $this->results['warnings'][] = sprintf(
                                'Invalid class name syntax: "%s" in %s',
                                $class,
                                str_replace($this->root_dir, '', $file)
                            );
                            continue;
                        }
                        
                        // Skip built-in PHP classes and WordPress classes using error suppression
                        if (@class_exists($class, false) || @interface_exists($class, false)) {
                            continue;
                        }
                        
                        // Skip classes that are found in our codebase
                        if (in_array($class, $all_classes)) {
                            continue;
                        }
                        
                        // Class not found - report it
                        $this->results['warnings'][] = sprintf(
                            'Potential missing class reference: "%s" in %s',
                            $class,
                            str_replace($this->root_dir, '', $file)
                        );
                    }
                } catch (\Exception $e) {
                    $this->results['warnings'][] = sprintf(
                        'Could not check file: %s - Error: %s',
                        str_replace($this->root_dir, '', $file),
                        $e->getMessage()
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error checking missing classes: ' . $e->getMessage());
            $this->results['errors'][] = 'Error checking missing classes: ' . $e->getMessage();
        }
    }

    // ...existing code...

    /**
     * Extract class references from a PHP file content.
     *
     * @param string $content File content.
     * @return array Array of referenced class names.
     */
    private function extractClassReferences($content) {
        $references = [];
        
        try {
            // Extract use statements
            $use_pattern = '/use\s+([^;]+);/';
            if (preg_match_all($use_pattern, $content, $matches)) {
                foreach ($matches[1] as $use) {
                    // Handle aliased use statements (use Namespace\Class as Alias)
                    if (strpos($use, ' as ') !== false) {
                        list($use, ) = explode(' as ', $use);
                    }
                    
                    $references[] = trim($use);
                }
            }
            
            // Extract type hints
            $typehint_pattern = '/function\s+\w+\s*\(([^)]*)\)/';
            if (preg_match_all($typehint_pattern, $content, $matches)) {
                foreach ($matches[1] as $params) {
                    $param_pattern = '/([\\\\A-Za-z0-9_]+)\s+\$\w+/';
                    if (preg_match_all($param_pattern, $params, $param_matches)) {
                        foreach ($param_matches[1] as $type) {
                            if ($type !== 'array' && $type !== 'string' && $type !== 'int' && $type !== 'bool' && $type !== 'float') {
                                $references[] = $type;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // If we have an exception in regex, just return an empty array
            return [];
        }
        
        return array_unique($references);
    }

    // ...existing code...
}
