<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        'declare_strict_types' => true,
        'final_class' => true,
        'readonly_public_property' => false,
        'single_line_empty_body' => false,
        'phpdoc_to_param_type' => true,
        'phpdoc_to_property_type' => true,
        'no_unset_on_property' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
