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

namespace Tuoluojiang\Open\services\ai;

class OpenAiCompatibleOption extends BaseOption
{
    /**
     * 请求基础地址.
     */
    public $baseUrl = '';

    /**
     * 请求路径.
     */
    public $url = '/chat/completions';

    /**
     * 模型名称.
     */
    public $model = '';

    /**
     * 是否流式响应.
     */
    public $stream = true;

    /**
     * 流式附加选项；聊天默认由 AiModelService 注入 include_usage，以回收 token 统计.
     */
    public $streamOptions = [];

    /**
     * 转换请求参数.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if ($this->stream && $this->streamOptions !== []) {
            $data['stream_options'] = $this->streamOptions;
        }

        return $data;
    }
}
