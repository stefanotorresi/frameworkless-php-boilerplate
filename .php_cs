<?php declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/test',
        __DIR__.'/bin',
    ])
    ->append([
        __FILE__,
        __DIR__.'/index.php',
    ])
;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@PSR2' => true,
        '@PHP71Migration' => true,
        '@PHP71Migration:risky' => true,
        '@PHP73Migration' => true,
        'heredoc_indentation' => false,
        'array_syntax' => ['syntax' => 'short'],
        'class_attributes_separation' => ['elements' => ['method', 'property']],
        'phpdoc_summary' => false,
        'yoda_style' => null,
        'no_unused_imports' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
    ])
;
