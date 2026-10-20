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

namespace App\Http\Requests\Ai;

use App\Http\Requests\ApiValidate;

/**
 * MCP 服务配置表单校验（单条 server 扁平 JSON 或标准 mcpServers JSON）.
 *
 * 名称与简介支持从标准 MCP 配置 JSON 自动提取，故不做必填校验.
 */
class AiMcpServiceRequest extends ApiValidate
{
    protected $scene = [
        'store'  => ['name', 'config_json'],
        'update' => ['name', 'status', 'sort', 'config_json'],
    ];

    public function message(): array
    {
        return [
            'name.max'         => '服务名称不能超过100个字符',
            'status.integer'   => '状态参数错误',
            'sort.integer'     => '排序参数错误',
            'info.max'         => '简介不能超过200个字符',
            'config_json.required' => 'MCP配置JSON必填',
        ];
    }

    protected function rules(): array
    {
        return [
            'name'        => ['nullable', 'max:100'],
            'info'        => ['nullable', 'max:200'],
            'config_json' => [$this->currentScene === 'store' ? 'required' : 'nullable'],
            'status'      => ['integer', 'in:0,1'],
            'sort'        => ['integer', 'min:0'],
        ];
    }
}
