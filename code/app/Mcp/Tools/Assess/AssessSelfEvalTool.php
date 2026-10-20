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

namespace App\Mcp\Tools\Assess;

use App\Http\Service\Assess\AssessService;
use App\Mcp\Tools\Abstract\BaseTool;

class AssessSelfEvalTool extends BaseTool
{
    public function getAction(): string
    {
        return 'write';
    }

    public function getDescription(): string
    {
        return '绩效自评，操作人固定为当前 MCP 用户';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['id', 'data'],
            'properties' => [
                'id'         => ['type' => 'integer', 'description' => '绩效考核 ID'],
                'data'       => ['type' => 'array', 'description' => '各考核维度得分数据'],
                'is_submit'  => ['type' => 'integer', 'description' => '是否提交：0=保存草稿，1=提交', 'default' => 0],
                'self_reply' => ['type' => 'string', 'description' => '自评说明/备注'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $id = (int) ($arguments['id'] ?? 0);
        if ($id <= 0) {
            return ['error' => true, 'message' => '缺少绩效考核 ID'];
        }

        app(AssessService::class)->setSelfAssess(
            $id,
            (array) ($arguments['data'] ?? []),
            (bool) ((int) ($arguments['is_submit'] ?? 0)),
            (string) ($arguments['self_reply'] ?? '')
        );

        return ['success' => true, 'id' => $id];
    }
}
