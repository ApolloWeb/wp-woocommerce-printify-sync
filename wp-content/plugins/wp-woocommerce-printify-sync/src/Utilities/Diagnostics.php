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
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Plugin root directory.
     *
     * @var string
     */
    private $root_dir;

    /**
     * Results of the diagnostics.
     *
     * @var array
     */
    private $results = [];

    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->root_dir = WPWPS_PLUGIN_DIR;
        $this->results = [
            'errors' => [],
            'warnings' => [],
            'notices' => [],
        ];
    }

    /**
     * Run all diagnostics.
     *
     * @return array Diagnostic results.
     */
    public function runAll() {
        try {
            // Ensure the directory exists before running checks
            if (!is_dir($this->root_dir) || !is_readable($this->root_dir)) {
                $this->results['errors'][] = sprintf(
                    'Plugin root directory not found or not readable: %s',
                    $this->root_dir
                );
                return $this->results;
            }

            $this->checkForDuplicateMethods();
            $this->checkMissingClasses();
            $this->checkFileInclusions();
            $this->validateDependencies();

            $this->logger->info('Diagnostics completed', [
                'error_count' => count($this->results['errors']),
                'warning_count' => count($this->results['warnings']),
                'notice_count' => count($this->results['notices']),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Diagnostic error: ' . $e->getMessage());
            $this->results['errors'][] = 'Diagnostic error: ' . $e->getMessage();
        }

        return $this->results;
    }

    /**
     * Check for duplicate method declarations in class files.
     */
    private function checkForDuplicateMethods() {
        $this->logger->info('Checking for duplicate method declarations');
        
        try {
            $php_files = $this->scanDirectory($this->root_dir . 'src', 'php');
            
            foreach ($php_files as $file) {
                if (!file_exists($file) || !is_readable($file)) {
                    $this->results['warnings'][] = sprintf(
                        'File not found or not readable: %s',
                        str_replace($this->root_dir, '', $file)
                    );
                    continue;
                }
                
                $content = file_get_contents($file);
                if ($content === false) {
                    $this->results['warnings'][] = sprintf(
                        'Could not read file: %s',
                        str_replace($this->root_dir, '', $file)
                    );
                    continue;
                }
                
                $methods = $this->extractMethodNames($content);
                $duplicates = $this->findDuplicates($methods);
                
                if (!empty($duplicates)) {
                    foreach ($duplicates as $method) {
                        $this->results['errors'][] = sprintf(
                            'Duplicate method "%s" found in %s',
                            $method,
                            str_replace($this->root_dir, '', $file)
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error checking duplicate methods: ' . $e->getMessage());
            $this->results['errors'][] = 'Error checking duplicate methods: ' . $e->getMessage();
        }
    }

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
                
                $content = file_get_contents($file);
                if ($content === false) {
                    continue;
                }
                
                $referenced_classes = $this->extractClassReferences($content);
                
                foreach ($referenced_classes as $class) {
                    // Skip built-in PHP classes and WordPress classes
                    if (class_exists($class) || interface_exists($class)) {
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
            }
        } catch (\Exception $e) {
            $this->logger->error('Error checking missing classes: ' . $e->getMessage());
            $this->results['errors'][] = 'Error checking missing classes: ' . $e->getMessage();
        }
    }

    /**
     * Check for file inclusion issues.
     */
    private function checkFileInclusions() {
        $this->logger->info('Checking for file inclusion issues');
        
        try {
            $php_files = $this->scanDirectory($this->root_dir, 'php');
            
            foreach ($php_files as $file) {
                if (!file_exists($file) || !is_readable($file)) {
                    continue;
                }
                
                $content = file_get_contents($file);
                if ($content === false) {
                    continue;
                }
                
                $includes = $this->extractFileInclusions($content);
                
                foreach ($includes as $include) {
                    // Try to resolve relative paths
                    if (strpos($include, './') === 0 || strpos($include, '../') === 0) {
                        $resolved = realpath(dirname($file) . '/' . $include);
                        if (!$resolved || !file_exists($resolved)) {
                            $this->results['errors'][] = sprintf(
                                'File inclusion not found: "%s" in %s',
                                $include,
                                str_replace($this->root_dir, '', $file)
                            );
                        }
                    } else if (strpos($include, WPWPS_PLUGIN_DIR) === 0 && !file_exists($include)) {
                        $this->results['errors'][] = sprintf(
                            'File inclusion not found: "%s" in %s',
                            $include,
                            str_replace($this->root_dir, '', $file)
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error checking file inclusions: ' . $e->getMessage());
            $this->results['errors'][] = 'Error checking file inclusions: ' . $e->getMessage();
        }
    }

    /**
     * Validate constructor dependencies.
     */
    private function validateDependencies() {
        $this->logger->info('Validating class dependencies');
        
        try {
            $php_files = $this->scanDirectory($this->root_dir . 'src', 'php');
            
            foreach ($php_files as $file) {
                if (!file_exists($file) || !is_readable($file)) {
                    continue;
                }
                
                $content = file_get_contents($file);
                if ($content === false) {
                    continue;
                }
                
                $class_name = $this->extractClassName($content);
                
                if (!$class_name) {
                    continue;
                }
                
                $dependencies = $this->extractConstructorDependencies($content);
                
                foreach ($dependencies as $dependency) {
                    // Skip built-in PHP classes and WordPress classes
                    if (class_exists($dependency) || interface_exists($dependency)) {
                        continue;
                    }
                    
                    // Check if this is our own class without the full namespace
                    if (strpos($dependency, '\\') === false) {
                        $this->results['notices'][] = sprintf(
                            'Potential non-namespaced dependency: "%s" in %s',
                            $dependency,
                            str_replace($this->root_dir, '', $file)
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error validating dependencies: ' . $e->getMessage());
            $this->results['errors'][] = 'Error validating dependencies: ' . $e->getMessage();
        }
    }

    /**
     * Scan a directory for files with a specific extension.
     *
     * @param string $directory Directory to scan.
     * @param string $extension File extension to look for.
     * @return array Array of file paths.
     */
    private function scanDirectory($directory, $extension) {
        if (!is_dir($directory) || !is_readable($directory)) {
            $this->logger->warning('Directory not found or not readable: ' . $directory);
            return [];
        }

        $results = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === $extension) {
                $results[] = $file->getPathname();
            }
        }

        return $results;
    }

    /**
     * Extract method names from file content.
     *
     * @param string $content File content.
     * @return array Array of method names with their positions.
     */
    private function extractMethodNames($content) {
        $methods = [];
        $pattern = '/\s*(?:public|private|protected)\s+function\s+(\w+)\s*\(/i';
        
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $methods[] = [
                    'name' => $match[0],
                    'position' => $match[1],
                ];
            }
        }
        
        return $methods;
    }

    /**
     * Find duplicate method names.
     *
     * @param array $methods Array of method names with positions.
     * @return array Array of duplicate method names.
     */
    private function findDuplicates($methods) {
        $names = [];
        $duplicates = [];
        
        foreach ($methods as $method) {
            if (isset($names[$method['name']])) {
                $duplicates[] = $method['name'];
            } else {
                $names[$method['name']] = true;
            }
        }
        
        return array_unique($duplicates);
    }

    /**
     * Extract all class names from PHP files.
     *
     * @param array $files Array of file paths.
     * @return array Array of class names.
     */
    private function extractAllClasses($files) {
        $classes = [];
        
        foreach ($files as $file) {
            if (!file_exists($file) || !is_readable($file)) {
                continue;
            }
            
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            
            $namespace = $this->extractNamespace($content);
            $class_name = $this->extractClassName($content);
            
            if ($class_name) {
                if ($namespace) {
                    $classes[] = $namespace . '\\' . $class_name;
                } else {
                    $classes[] = $class_name;
                }
            }
        }
        
        return $classes;
    }

    /**
     * Extract namespace from file content.
     *
     * @param string $content File content.
     * @return string|null Namespace or null.
     */
    private function extractNamespace($content) {
        if (preg_match('/namespace\s+([^;]+);/i', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Extract class name from file content.
     *
     * @param string $content File content.
     * @return string|null Class name or null.
     */
    private function extractClassName($content) {
        if (preg_match('/class\s+(\w+)(?:\s+extends|\s+implements|\s*\{|$)/i', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Extract class references from file content.
     *
     * @param string $content File content.
     * @return array Array of class references.
     */
    private function extractClassReferences($content) {
        $references = [];
        
        // Extract 'use' statements
        if (preg_match_all('/use\s+([^;]+);/i', $content, $matches)) {
            foreach ($matches[1] as $match) {
                // Handle aliased imports (use Class as Alias)
                if (strpos($match, ' as ') !== false) {
                    list($class) = explode(' as ', $match);
                    $references[] = trim($class);
                } else {
                    $references[] = trim($match);
                }
            }
        }
        
        // Extract instantiations (new ClassName())
        if (preg_match_all('/new\s+([a-zA-Z0-9_\\\\]+)(?:\s*\(|\s*;)/i', $content, $matches)) {
            foreach ($matches[1] as $match) {
                $references[] = trim($match);
            }
        }
        
        // Extract type hints in method parameters
        if (preg_match_all('/function\s+\w+\s*\((?:[^)]*?)([a-zA-Z0-9_\\\\]+)\s+\$\w+/i', $content, $matches)) {
            foreach ($matches[1] as $match) {
                $references[] = trim($match);
            }
        }
        
        return array_unique($references);
    }

    /**
     * Extract file inclusions from file content.
     *
     * @param string $content File content.
     * @return array Array of included file paths.
     */
    private function extractFileInclusions($content) {
        $inclusions = [];
        
        // Match require, require_once, include, include_once
        $pattern = '/(require|require_once|include|include_once)\s*\(\s*([\'"])(.*?)\2\s*\)/i';
        
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[3] as $match) {
                $inclusions[] = $match;
            }
        }
        
        return $inclusions;
    }

    /**
     * Extract constructor dependencies from file content.
     *
     * @param string $content File content.
     * @return array Array of constructor dependencies.
     */
    private function extractConstructorDependencies($content) {
        $dependencies = [];
        
        // Match constructor parameters with type hints
        $pattern = '/function\s+__construct\s*\((.*?)\)/s';
        
        if (preg_match($pattern, $content, $matches)) {
            $params = $matches[1];
            
            // Extract type hints
            $param_pattern = '/([a-zA-Z0-9_\\\\]+)\s+\$\w+/';
            
            if (preg_match_all($param_pattern, $params, $param_matches)) {
                foreach ($param_matches[1] as $type) {
                    if ($type !== 'array' && $type !== 'string' && $type !== 'int' && $type !== 'bool' && $type !== 'float') {
                        $dependencies[] = $type;
                    }
                }
            }
        }
        
        return $dependencies;
    }
}
