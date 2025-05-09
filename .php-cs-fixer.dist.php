<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in(__DIR__.'/src')
    ->name('*.php')
    ->ignoreDotFiles(true)
;

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        // 'strict_param' => true,
        // 'strict_comparison' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'combine_consecutive_unsets' => true,

        'header_comment' => [
            'header' => '
@since 0.0.1
@link https://nethttp.net
@Author seb@nethttp.net

',
            'comment_type' => 'PHPDoc',
            'location' => 'after_open',
            'separate' => 'none',
        ],
    ])
    ->setFinder($finder)
;
