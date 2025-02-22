<?php

 = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->notPath('wp-config.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'align_single_space'],
        'blank_line_after_opening_tag' => true,
        'braces' => true,
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setFinder();
