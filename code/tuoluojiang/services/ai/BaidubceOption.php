<?php

namespace Tuoluojiang\Open\services\ai;

/**
 * 百度智能AI
 */
class BaidubceOption extends BaseOption
{

    /**
     * @var string
     */
    public $baseUrl = 'https://qianfan.baidubce.com';

    /**
     * @var string
     */
    public $url = '/v2/chat/completions';

    /**
     * @var int
     */
    public $maxCompletionTokens = 2048;

    /**
     * 默认模型
     * @var string
     */
    public $model = 'ernie-4.0-8k-latest';

    /**
     * 流式输出
     * @var bool
     */
    public $stream = true;

    /**
     * @var true[]
     */
    public $streamOptions = [
        'include_usage' => true
    ];

    /**
     * 模型选项
     */
    const MODEL_OPTIONS = [
        'ernie-4.0-8k-latest'                   => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-4.0-8k-preview'                  => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-4.0-8k'                          => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-4.0-turbo-8k-latest'             => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-4.0-turbo-8k-preview'            => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-4.0-turbo-8k'                    => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-4.0-turbo-128k'                  => [
            'min' => 2,
            'max' => 4096
        ],
        'ernie-3.5-8k-preview'                  => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-3.5-8k'                          => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-3.5-128k'                        => [
            'min' => 2,
            'max' => 8192
        ],
        'ernie-speed-8k'                        => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-speed-128k'                      => [
            'min' => 2,
            'max' => 4096
        ],
        'ernie-speed-pro-128k'                  => [
            'min' => 2,
            'max' => 4096
        ],
        'ernie-lite-8k'                         => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-lite-128k'                       => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-lite-pro-128k'                   => [
            'min' => 2,
            'max' => 4096
        ],
        'ernie-tiny-8k'                         => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-char-8k'                         => [
            'min' => 2,
            'max' => 2048
        ],
        'ernie-char-fiction-8k'                 => [
            'min' => 2,
            'max' => 8196
        ],
        'ernie-novel-8k'                        => [
            'min' => 2,
            'max' => 2048
        ],
        'deepseek-v3'                           => [
            'min' => 1,
            'max' => 8192
        ],
        'deepseek-r1'                           => [
            'min' => 1,
            'max' => 8192
        ],
        'deepseek-r1-distill-qwen-32b'          => [
            'min' => 1,
            'max' => 8192
        ],
        'deepseek-r1-distill-qwen-14b'          => [
            'min' => 1,
            'max' => 8192
        ],
        'deepseek-r1-distill-qwen-7b'           => [
            'min' => 1,
            'max' => 8192
        ],
        'deepseek-r1-distill-qwen-1.5b'         => [
            'min' => 1,
            'max' => 8192
        ],
        'deepseek-r1-distill-llama-70b'         => [
            'min' => 1,
            'max' => 8192
        ],
        'deepseek-r1-distill-llama-8b'          => [
            'min' => 1,
            'max' => 8192
        ],
        'deepseek-r1-distill-qianfan-llama-70b' => [
            'min' => 1,
            'max' => 4096
        ],
        'deepseek-r1-distill-qianfan-llama-8b'  => [
            'min' => 1,
            'max' => 4096
        ],
    ];

    /**
     * @return array|array[]
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['max_completion_tokens'] = $this->maxCompletionTokens;
        $data['stream_options'] = $this->streamOptions;

        if (in_array($this->model, ['deepseek-v3', 'deepseek-r1', 'deepseek-r1-distill-qwen-32b',
                                    'deepseek-r1-distill-qwen-14b', 'deepseek-r1-distill-qwen-7b',
                                    'deepseek-r1-distill-qwen-1.5b', 'deepseek-r1-distill-llama-70b',
                                    'deepseek-r1-distill-llama-8b', 'deepseek-r1-distill-qianfan-llama-70b',
                                    'deepseek-r1-distill-qianfan-llama-8b'])) {
            if ($this->temperature !== null && $this->temperature > 2) {
                $this->temperature = 2;
            }
            unset($data['penalty_score']);
        } else {
            if ($this->temperature !== null && $this->temperature > 1) {
                $this->temperature = 1;
            }
        }

        if (in_array($this->model, ['deepseek-r1', 'deepseek-r1-distill-qwen-32b',
                                    'deepseek-r1-distill-qwen-14b', 'deepseek-r1-distill-qwen-7b',
                                    'deepseek-r1-distill-qwen-1.5b', 'deepseek-r1-distill-llama-70b',
                                    'deepseek-r1-distill-llama-8b', 'deepseek-r1-distill-qianfan-llama-70b',
                                    'deepseek-r1-distill-qianfan-llama-8b'])) {
            unset($data['top_p']);
        }

        if (!in_array($this->model, ['ernie-speed-8k', 'ernie-speed-128k', 'ernie-speed-pro-128k', 'ernie-lite-8k', 'ernie-lite-pro-128k', 'ernie-tiny-8k', 'ernie-char-8k', 'ernie-char-fiction-8k'])) {
            unset($data['frequency_penalty'], $data['presence_penalty']);
        }

        if (!in_array($this->model, ['ernie-4.0-8k-latest', 'ernie-4.0-8k-preview', 'ernie-4.0-8k',
                                     'ernie-4.0-turbo-8k-latest', 'ernie-4.0-turbo-8k-preview', 'ernie-4.0-turbo-8k',
                                     'ernie-4.0-turbo-128k', 'ernie-3.5-8k-preview', 'ernie-3.5-8k', 'ernie-3.5-128k'])) {
            unset($data['web_search']);
        }

        return $data;
    }
}
