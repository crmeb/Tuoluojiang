<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2'               => true,
        '@Symfony'            => true,
        '@DoctrineAnnotation' => true,
        '@PhpCsFixer'         => true,
        'phpdoc_scalar'       => true,
        'phpdoc_types'        => true,
        'header_comment'      => [
            'comment_type' => 'PHPDoc',
            'header'       => <<<'EOF'
 +----------------------------------------------------------------------
 | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 +----------------------------------------------------------------------
 | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 +----------------------------------------------------------------------
 | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 +----------------------------------------------------------------------
 | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 +----------------------------------------------------------------------
EOF,
            'separate' => 'none',
            'location' => 'after_declare_strict',
        ],
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'list_syntax' => [
            'syntax' => 'short',
        ],
        'concat_space' => [
            'spacing' => 'one',
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'declare',
            ],
        ],
        'general_phpdoc_annotation_remove' => [
            'annotations' => [
                'author',
            ],
        ],
        'ordered_imports' => [
            'imports_order' => [
                'class', 'function', 'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'single_line_comment_style' => [
            'comment_types' => [
            ],
        ],
        'yoda_style' => [
            'always_move_variable' => false,
            'equal'                => false,
            'identical'            => false,
        ],
        'phpdoc_align' => [
            'align' => 'left',
        ],
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'constant_case' => [
            'case' => 'lower',
        ],
        'class_attributes_separation'       => true,
        'combine_consecutive_unsets'        => true,
        'declare_strict_types'              => true,
        'linebreak_after_opening_tag'       => true,
        'lowercase_static_reference'        => true,
        'no_useless_else'                   => true,
        'no_unused_imports'                 => true,
        'not_operator_with_successor_space' => true,
        'not_operator_with_space'           => false,
        'ordered_class_elements'            => true,
        'php_unit_strict'                   => false,
        'phpdoc_separation'                 => false,
        'single_quote'                      => true,
        'standardize_not_equals'            => true,
        'multiline_comment_opening_closing' => true,
        'binary_operator_spaces'            => [
            'operators' => [
                '=>' => 'align_single_space_minimal',
                '='  => 'align_single_space_minimal',
            ],
        ],
    ])
    ->setFinder(
        Finder::create()
            ->exclude('public')
            ->exclude('runtime')
            ->exclude('vendor')
            ->in(__DIR__)
    )
    ->setUsingCache(false);
