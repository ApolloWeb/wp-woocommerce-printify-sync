<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/../../') // Adjust path if necessary
    ->exclude('vendor')
    ->exclude('node_modules')
    ->exclude('.github');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        // Add or modify rules as needed
    ]);
