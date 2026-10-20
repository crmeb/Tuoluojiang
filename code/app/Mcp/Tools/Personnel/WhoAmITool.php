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

namespace App\Mcp\Tools\Personnel;

use App\Http\Service\Frame\FrameAssistService;
use App\Mcp\Tools\Abstract\BaseTool;

/**
 * 当前登录用户身份工具
 * Class WhoAmITool.
 */
class WhoAmITool extends BaseTool
{
    public function getDescription(): string
    {
        return '获取当前 MCP 登录用户的身份信息（id/姓名/部门/是否管理员）。查询或写入任何业务数据前应首先调用本工具确认“我是谁”，再基于返回身份与数据权限选择后续工具';
    }

    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => new \stdClass(),
        ];
    }

    public function execute(array $arguments): array
    {
        $userInfo = $this->getUserInfo();
        $frames   = $this->getSafeFrames((string) ($userInfo['uid'] ?? ''));

        return [
            'id'             => $this->getUserDbId(),
            'uid'            => $userInfo['uid'] ?? '',
            'name'           => $userInfo['name'] ?? '',
            'avatar'         => $userInfo['avatar'] ?? '',
            'phone'          => $userInfo['phone'] ?? '',
            'job_id'         => (int) ($userInfo['job'] ?? 0),
            'is_admin'       => $this->isAdmin(),
            'main_frame_id'  => $this->getMainFrameId($frames),
            'frame_ids'      => array_column($frames, 'id'),
            'frames'         => $frames,
        ];
    }

    private function getSafeFrames(string $uuid): array
    {
        if ($uuid === '') {
            return [];
        }

        $frames = app(FrameAssistService::class)->getUserFrames($uuid);
        $frames = $frames ? (is_array($frames) ? $frames : $frames->toArray()) : [];

        return array_values(array_map(function (array $item) {
            $frame = $item['frame'] ?? [];

            return [
                'id'          => (int) ($frame['id'] ?? $item['frame_id'] ?? 0),
                'name'        => $frame['name'] ?? '',
                'is_main'     => (bool) ($item['is_mastart'] ?? false),
                'is_manager'  => (bool) ($item['is_admin'] ?? false),
            ];
        }, $frames));
    }

    private function getMainFrameId(array $frames): int
    {
        foreach ($frames as $frame) {
            if ($frame['is_main'] ?? false) {
                return (int) $frame['id'];
            }
        }

        return (int) ($frames[0]['id'] ?? 0);
    }
}
