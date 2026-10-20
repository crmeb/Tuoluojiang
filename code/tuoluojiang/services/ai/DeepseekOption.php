<?php

namespace Tuoluojiang\Open\services\ai;

/**
 * 深度搜索
 */
class DeepseekOption extends BaseOption
{

    public $baseUrl = 'https://api.deepseek.com';

    /**
     * @var string
     */
    public $url = '/chat/completions';

    /**
     * 模型名称
     * @var string
     */
    public $model = 'deepseek-chat';

    public $stream = true;

    /**
     * @var true[]
     */
    public $streamOptions = [
        'include_usage' => true
    ];

    /**
     *
     */
    const MODEL_OPTIONS = [
        'deepseek-chat'     => [
            'min' => 1,
            'max' => 8192
        ],
        'deepseek-reasoner' => [
            'min' => 1,
            'max' => 8192
        ]
    ];
}
